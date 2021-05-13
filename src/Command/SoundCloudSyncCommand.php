<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SoundCloudSyncCommand extends Command
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container = $container;
    }

    protected function configure()
    {
        $this
                ->setName('vocalizr:sc-sync')
                ->setDescription('Start sync commands for newly uploaded files')
                ->addArgument('id', InputArgument::REQUIRED, 'Primary ID for UserAudio file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container        = $this->container;
        $doctrine      = $container->get('doctrine');
        $em            = $doctrine->getManager();
        $userAudioRepo = $doctrine->getRepository('App:UserAudio');

        $id = $input->getArgument('id');

        // Get audio file
        if (!$userAudio = $userAudioRepo->findOneById($id)) {
            die('Invalid user audio id');
        }

        // If file has been synced or already started then die
        if ($userAudio['sc_synced'] || !is_null($userAudio['sc_sync_start'])) {
            die('File has already been synced');
        }

        $userInfo = $userAudio['user_info'];

        $audio = $em->getReference('App:UserAudio', $userAudio);

        // create client object with app credentials
        $clientId     = $container->getParameter('soundcloud_client_id');
        $clientSecret = $container->getParameter('soundcloud_client_secret');
        $client       = new \Services_Soundcloud($clientId, $clientSecret);

        $client->setAccessToken($userInfo['soundcloud_access_token']);

        $audio->setScSyncStart(new \DateTime());
        $em->persist($audio);
        $em->flush();

        // upload audio file
        try {
            $result = $client->post('tracks', [
                'track[title]'      => $userAudio['title'],
                'track[asset_data]' => '@' . $audio->getAbsolutePath(),
            ]);

            $track = json_decode($result);

            $audio->setScId($track->id);
            $audio->setScStreamUrl($track->stream_url);
            $audio->setScDownloadUrl($track->download_url);
            $audio->setScPermalinkUrl($track->permalink_url);
            $audio->setScRaw($result);
            $audio->setScUploadResult(true);

            /*
            if (!empty($userInfo['soundcloud_set_id'])) {
                try {
                    $client->updatePlaylist($userInfo['soundcloud_set_id'], array($track->id));
                } catch (\Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
                    // do nothing
                }
            }
             *
             */

            $em->persist($audio);
            $em->flush();
        }
        // Catch upload error
        catch (\Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
            $audio->setScSynced(false);
            $audio->setScSyncFinished(new \DateTime());
            $audio->setScUploadResult(false);
            echo $e->getMessage();
        }
        $audio->setScUploadQueued(false);

        $em->persist($audio);
        $em->flush();
    }
}