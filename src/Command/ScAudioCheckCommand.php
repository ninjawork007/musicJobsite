<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScAudioCheckCommand extends Command
{
    protected function configure()
    {
        // How often do we run this script
        $this->_timeAgo = '1 month';

        $this
                ->setName('vocalizr:sc-audio-check')
                ->setDescription('Check soundcloud audio to see if it has been removed [Cronjob: Every ' . $this->_timeAgo . ']')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->container  = $container  = $this->getContainer();
        $doctrine         = $container->get('doctrine');
        $em               = $doctrine->getManager();
        $this->dispatcher = $container->get('hip_mandrill.dispatcher');

        $q = $em->getRepository('App:UserAudio')
                ->createQueryBuilder('ua')
                ->select('ua, ui')
                ->innerJoin('ua.user_info', 'ui')
                ->where('ua.sc_id IS NOT NULL AND ui.is_active = 1');

        $results = $q->getQuery()->execute();

        echo 'Total SoundCloud Audio files: ' . count($results) . "\n\n";

        // Get list of bids that were placed in the last 5 minutes
        if ($results) {
            $scAccountRemoved = [];
            $i                = 0;
            foreach ($results as $userAudio) {
                $i++;
                $userInfo = $userAudio->getUserInfo();

                $url = 'http://api.soundcloud.com/tracks/' . $userAudio->getScId() . '.json?client_id=' . $container->getParameter('soundcloud_client_id');
                $ch  = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_URL            => $url,
                ]);

                $result = curl_exec($ch);
                if ($result) {
                    $json = json_decode($result, true);
                    if (isset($json['id'])) {
                        continue; // Continue as we can access the audio
                    }
                }

                if ($notification = $em->getRepository('App:Notification')->findOneBy(['user_audio' => $userAudio->getId()])) {
                    echo 'REMOVE NOTIFICATION';
                    $em->remove($notification);
                    $em->flush();
                }

                usleep(500000);

                // Remove audio file
                $em->remove($userAudio);
                $em->flush();

                echo 'Audio removed: ' . $userInfo->getUsername() . ' - ' . $userAudio->getTitle() . "\n";

                // If soundcloud account has been fully removed already,
                // continue on as we don't need to notify the user more than once
                if (in_array($userInfo->getId(), $scAccountRemoved)) {
                    $em->flush();
                    continue;
                }

                // If we got this far, let's check there soundcloud account as well
                $clientId     = $container->getParameter('soundcloud_client_id');
                $clientSecret = $container->getParameter('soundcloud_client_secret');
                $client       = new \Services_Soundcloud($clientId, $clientSecret);
                $client->setAccessToken($userInfo->getSoundcloudAccessToken());

                // Try access profile information
                try {
                    $me = json_decode($client->get('me'));
                } catch (\Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
                    $userInfo->setSoundcloudId(null);
                    $userInfo->setSoundcloudAccessToken(null);
                    $userInfo->setSoundcloudUsername(null);
                    $userInfo->setSoundcloudRegister(0);

                    $em->persist($userInfo);
                    $em->flush();

                    $scAccountRemoved[] = $userInfo->getId();

                    echo 'Soundcloud access gone: ' . $userInfo->getId() . ' - ' . $userInfo->getUsername() . "\n";

                    $this->sendScNotConnectedEmail($userAudio);
                    continue;
                }

                $message = new \Hip\MandrillBundle\Message();
                $message->setSubject('Soundcloud audio no longer playable: ' . $userAudio->getTitle());
                $message->setFromEmail('noreply@vocalizr.com');
                $message->setFromName('Vocalizr');
                $message
                    ->setTrackOpens(true)
                    ->setTrackClicks(true);

                $message->addTo($userInfo->getEmail());
                $body = $container->get('twig')->render('Mail:soundcloudAudioRemoved.html.twig', [
                    'userAudio' => $userAudio,
                ]);
                $message->addGlobalMergeVar('BODY', $body);
                $this->dispatcher->send($message, 'default');

                $em->flush();
            }
        }
    }

    public function sendScNotConnectedEmail($userAudio)
    {
        $message = new \Hip\MandrillBundle\Message();
        $message->setSubject('Soundcloud account disconnected');
        $message->setFromEmail('noreply@vocalizr.com');
        $message->setFromName('Vocalizr');
        $message
            ->setTrackOpens(true)
            ->setTrackClicks(true);

        $message->addTo($userAudio->getUserInfo()->getEmail());
        $body = $this->container->get('twig')->render('Mail:soundcloudDisconnected.html.twig', [
            'userAudio' => $userAudio,
        ]);
        $message->addGlobalMergeVar('BODY', $body);
        $this->dispatcher->send($message, 'default');
    }
}