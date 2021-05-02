<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SoundcloudController extends AbstractController
{
    /**
     * @Route("/soundcloud/connect", name="soundcloud_connect")
     */
    public function connectAction()
    {
        $request = $this->getRequest();

        $serverName = $_SERVER['SERVER_NAME'];
        $client     = $this->__createClient();
        $client->setRedirectUri('http://' . $serverName . $this->generateUrl('soundcloud_auth'));

        if (!$referer = $request->headers->get('referer', false)) {
            $referer = $this->generateUrl('user_home');
        }
        $this->get('session')->set('sc_referer', $referer);

        // redirect user to authorize URL
        header('Location: ' . $client->getAuthorizeUrl() . '&scope=non-expiring');
        exit;
    }

    /**
     * @Route("/soundcloud/disconnect", name="soundcloud_disconnect")
     */
    public function disconnectAction()
    {
        $em = $this->getDoctrine()->getManager();

        $user = $this->getUser();
        $user->setSoundcloudId(null);
        $user->setSoundcloudAccessToken(null);
        $em->flush();

        $this->get('session')->getFlashBag()->add('notice', 'Successfully disconnected your account from SoundCloud');

        return $this->redirect($this->generateUrl('user_edit') . '#audio');
    }

    /**
     * @Route("/soundcloud/auth", name="soundcloud_auth")
     */
    public function authAction()
    {
        $em      = $this->getDoctrine()->getManager();
        $request = $this->getRequest();
        $user    = $this->getUser();

        if (!$code = $request->get('code')) {
            throw $this->createNotFoundException('Invalid soundcloud auth request');
        }

        // create client object with app credentials
        $client     = $this->__createClient();
        $serverName = $_SERVER['SERVER_NAME'];
        $client->setRedirectUri('http://' . $serverName . $this->generateUrl('soundcloud_auth'));

        try {
            $response = $client->accessToken($code);
        } catch (\Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
            throw $this->createNotFoundException($e->getMessage());
        }
        $accessToken = $response['access_token'];
        $client->setAccessToken($accessToken);

        // Try access profile information
        try {
            $me = json_decode($client->get('me'));
        } catch (Exception $e) {
            throw $this->createNotFoundException('Unable to connect to sound cloud. Please try connect again');
        }

        // If user isn't logged in, then redirect
        if (!$user) {
            if (!$referer = $this->get('session')->get('sc_referer', false)) {
                $referer = $this->generateUrl('user_edit');
            }
            $session = $this->getRequest()->getSession();
            $session->set('scId', $me->id);
            $session->set('scAccessToken', $accessToken);

            //$this->get('session')->getFlashBag()->add('notice', 'Successfully connect SoundCloud');

            return $this->redirect($referer);
        }

        // Update user with access token
        $user->setSoundcloudId($me->id);
        $user->setSoundcloudAccessToken($accessToken);
        $em->persist($user);
        $em->flush();

        if (!$referer = $this->get('session')->get('sc_referer', false)) {
            $referer = $this->generateUrl('user_edit');
        }

        $this->get('session')->getFlashBag()->add('notice', 'Successfully connected your account to SoundCloud');

        return $this->redirect($referer);
    }

    /**
     * @Route("/soundcloud/sync-status/{id}", name="soundcloud_sync_status")
     */
    public function syncStatusAction()
    {
        $id = $this->getRequest()->get('id');

        $client = $this->__createClient();
        $user   = $this->getUser();
        $em     = $this->getDoctrine()->getEntityManager();

        // Make sure user audio exists
        $userAudioRepo = $em->getRepository('App:UserAudio');
        $userAudio     = $userAudioRepo->findOneBy(['sc_id' => $id, 'user_info' => $user->getId()]);

        $response = new \Symfony\Component\HttpFoundation\JsonResponse();

        if (!$userAudio) {
            return $response;
        }

        if ($userAudio->getScSynced()) {
            $response->setData(['synced' => true]);
            return $response;
        }

        try {
            $result = $client->get('tracks', ['ids' => $id, 'filter' => 'public']);
            $json   = json_decode($result);
            $json   = current($json);

            $synced = false;
            if (strtoupper($json->state) == 'FINISHED') {
                $synced = true;
                // Update database
                $userAudio->setScSynced(true);
                $userAudio->setScSyncFinished(new \DateTime());
                $userAudio->setScRaw(json_encode($json));
                $userAudio->setLength($json->duration);
                $em->persist($userAudio);
                $em->flush();
            }
            if (strtoupper($json->state) == 'FAILED') {
                $synced = false;
                $userAudio->setScSynced(false);
                $userAudio->setScSyncFinished(new \DateTime());
                $userAudio->setScRaw(json_encode($json));
                $em->persist($userAudio);
                $em->flush();
            }
            $response->setData(['synced' => $synced]);
            return $response;
        } catch (\Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
            echo $e->getMessage();
            return $response;
        }
    }

    /**
     * @Route("/soundcloud/fetch/tracks", name="soundcloud_fetch_tracks")
     * @Template()
     */
    public function fetchTracksAction()
    {
        $request         = $this->getRequest();
        $user            = $this->getUser();
        $em              = $this->getDoctrine()->getManager();
        $userScTrackRepo = $em->getRepository('App:UserScTrack');

        // Record last fetch track import
        // Only allow refresh every 5 minutes, this will stop abuse.

        $client = $this->__createClient();
        try {
            $result = $client->get('users/' . $user->getSoundcloudId() . '/tracks');
            $tracks = json_decode($result);
        } catch (\Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
            return ['error' => $e->getMessage()];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }

        $tracks = $userScTrackRepo->saveFromSoundcloud($user->getId(), $tracks);

        $helper = $this->get('service.helper');

        $jsonA = [];
        // Otherwise return json of tracks
        if ($tracks) {
            foreach ($tracks as $track) {
                $jsonA[] = [
                    'sc_id'           => $track->id,
                    'title'           => $track->title,
                    'duration'        => $track->duration,
                    'duration_string' => $helper->millisecondsToTime($track->duration),
                    'permalink_url'   => $track->permalink_url,
                    'stream_url'      => $track->stream_url,
                ];
            }
        }

        return ['scTracks' => $jsonA];
    }

    /**
     * @Route("/soundcloud/tracks", name="soundcloud_tracks")
     * @Template()
     */
    public function displayTracksAction()
    {
        $em   = $this->getDoctrine()->getEntityManager();
        $user = $this->getUser();

        // Get cached soundcloud tracks
        $scTracks = false;
        if ($user) {
            $scTracks = $em->getRepository('App:UserScTrack')->getTracksByUserInfoId($user->getId());
        }

        return ['scTracks' => $scTracks];
    }

    /**
     * Create client object for Soundcloud
     *
     * @return \Services_Soundcloud
     */
    private function __createClient()
    {
        $scService = $this->get('service.sc');
        return $scService->getClient();
    }
}
