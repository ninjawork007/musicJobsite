<?php

namespace Vocalizr\AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpFoundation\Request;

use Vocalizr\AppBundle\Entity\Notification;
use Vocalizr\AppBundle\Entity\UserAudio;

class AudioController extends Controller
{
    /**
     * Record play count for user
     *
     * @Route("/audio/{slug}/likes", name="audio_likes")
     * @Template()
     */
    public function likesAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $dm = $this->get('doctrine_mongodb')->getManager();

        $likeUsers = [];

        $userAudio = $em->getRepository('VocalizrAppBundle:UserAudio')->findOneBy([
            'slug' => $request->get('slug'),
        ]);
        if ($userAudio) {
            $audioLikes = $dm->getRepository('VocalizrAppBundle:AudioLike')->findBy([
                'audio_id' => $userAudio->getId(),
            ], ['date', 'ASC']);

            /*
            $audioLikes = $dm->createQueryBuilder('VocalizrAppBundle:AudioLike')
             ->field('audio_id')->equals($userAudio->getId())
             ->sort('date', 'DESC')
             ->getQuery()
             ->execute();
             *
             */

            $qb = $dm->createQueryBuilder('VocalizrAppBundle:AudioLike')
                        ->field('audio_id')->equals($userAudio->getId());
            //->sort('date', 'DESC');
            $results = $qb->getQuery()->execute();

            //echo "<!-- ".print_r($results, true)."-->";

            // Get user ids
            $userIds = [];
            foreach ($audioLikes as $audioLike) {
                $userIds[] = $audioLike->getFromUserId();
            }

            if ($userIds) {
                $qb = $em->getRepository('VocalizrAppBundle:UserInfo')
                        ->createQueryBuilder('u')
                        ->select('u')
                        ->where('u.active = 1');
                $qb->add('where', $qb->expr()->in('u.id', $userIds));
                $users = $qb->getQuery()->execute();

                foreach ($userIds as $userId) {
                    foreach ($users as $userLike) {
                        if ($userId == $userLike->getId()) {
                            $likeUsers[] = $userLike;
                            break;
                        }
                    }
                }
            }
        }

        return [
            'likeUsers' => $likeUsers,
        ];
    }

    /**
     * Record play count for user
     *
     * @Route("/audio/{slug}/record", name="record_audio_play")
     */
    public function recordAudioPlayAction(Request $request)
    {
        $em   = $this->getDoctrine()->getEntityManager();
        $user = $this->getUser();

        $userAudio = $em->getRepository('VocalizrAppBundle:UserAudio')->findOneBy([
            'slug' => $request->get('slug'),
        ]);
        if (!$userAudio) {
            return new JsonResponse(['success' => false]);
        }

        $count = $userAudio->getPlayCount();
        $userAudio->setPlayCount($count + 1);
        $em->persist($userAudio);

        $dm = $this->get('doctrine_mongodb')->getManager();

        $audioPlayRepo = $dm->getRepository('VocalizrAppBundle:AudioPlay');
        if (!$audioPlay = $audioPlayRepo->findOneBy([
            'audio_id' => $userAudio->getId(),
            'date' => date('Y-m-d'),
        ])) {
            $audioPlay = new \Vocalizr\AppBundle\Document\AudioPlay();
            $audioPlay->setAudioId($userAudio->getId());
            $audioPlay->setUserId($userAudio->getUserInfo()->getId());
            $audioPlay->setDate(date('Y-m-d'));
        }
        $count = $audioPlay->getCount();
        $audioPlay->setCount($count + 1);
        $dm->persist($audioPlay);

        // If user is logged in, record audio play by usre
        if ($user && $user->getId() != $userAudio->getUserInfo()->getId()) {
            $audioPlayUser = new \Vocalizr\AppBundle\Document\AudioPlayUser();
            $audioPlayUser->setAudioId($userAudio->getId());
            $audioPlayUser->setUserId($userAudio->getUserInfo()->getId());
            $audioPlayUser->setFromUserId($user->getId());
            $audioPlayUser->setDate(date('Y-m-d'));
            $audioPlayUser->setCreatedAt(date('Y-m-d H:i:s'));
            $dm->persist($audioPlayUser);
        }

        $dm->flush();
        $em->flush();

        return new JsonResponse(['success' => true, 'count' => $userAudio->getPlayCount()]);
    }

    /**
     * Audio like
     *
     * @Route("/audio/{slug}/like/{status}", name="audio_like")
     */
    public function likeAudioAction(Request $request)
    {
        $em   = $this->getDoctrine()->getEntityManager();
        $dm   = $this->get('doctrine_mongodb')->getManager();
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['success' => false]);
        }

        /** @var UserAudio $userAudio */
        $userAudio = $em->getRepository('VocalizrAppBundle:UserAudio')->findOneBy([
            'slug' => $request->get('slug'),
        ]);
        if (!$userAudio) {
            return new JsonResponse(['success' => false]);
        }

        $audioUser = $userAudio->getUserInfo();

        $audioLikeRepo = $dm->getRepository('VocalizrAppBundle:AudioLike');
        $audioLike     = $audioLikeRepo->findOneBy([
            'from_user_id' => $user->getId(),
            'audio_id'     => $userAudio->getId(),
        ]);

        $changed = false;

        $status = $request->get('status');
        // If they are liking the audio file
        if ($status) {
            // Make sure it doesn't already exist
            if (!$audioLike) {
                $audioLike = new \Vocalizr\AppBundle\Document\AudioLike();
                $audioLike->setUserId($userAudio->getUserInfo()->getId());
                $audioLike->setAudioId($userAudio->getId());
                $audioLike->setDate(date('Y-m-d H:i:s'));
                $audioLike->setFromUserId($user->getId());
                $dm->persist($audioLike);

                $userAudio->setTotalLikes($userAudio->getTotalLikes() + 1);
                $em->persist($userAudio);

                // Create new notification
                $data = [
                    'title' => $userAudio->getTitle(),
                ];
                $notify = new Notification();
                $notify->setUserInfo($userAudio->getUserInfo());
                $notify->setUserAudio($userAudio);
                $notify->setActionedUserInfo($user);
                $notify->setNotifyType(Notification::NOTIFY_TYPE_LIKE);
                $notify->setData(json_encode($data));
                $em->persist($notify);

                $count = $audioUser->getNumNotifications();
                $audioUser->setNumNotifications($count + 1);
                $em->persist($audioUser);

                $em->flush();
                $dm->flush();
                $changed = true;
            }
        } else {
            // if audio like exists, remove
            if ($audioLike) {
                $dm->remove($audioLike);

                if ($userAudio->getTotalLikes() > 0) {
                    $userAudio->setTotalLikes($userAudio->getTotalLikes() - 1);
                    $em->persist($userAudio);
                }

                // See if there are any notifications for this user audio
                if ($notify = $em->getRepository('VocalizrAppBundle:Notification')
                        ->findOneBy([
                            'user_audio' => $userAudio->getId(),
                            'actioned_user_info' => $user,
                        ])) {
                    $em->remove($notify);
                }

                $count = $audioUser->getNumNotifications();
                if ($count > 0) {
                    $audioUser->setNumNotifications($count - 1);
                    $em->persist($audioUser);
                }

                $em->flush();
                $dm->flush();
                $changed = true;
            }
        }

        return new JsonResponse(['success' => true, 'count' => $userAudio->getTotalLikes(), 'changed' => $changed]);
    }

    /**
     * @Route("/audio/{type}/waveform/{id}", name="audio_waveform")
     *
     * @param Request $request
     * @param string  $type
     * @param int     $id
     *
     * @return JsonResponse
     */
    public function getWaveformAction(Request $request, $type, $id)
    {
        $status = 200;
        $data    = ['peaks' => []];
        $service = $this->get('vocalizr_app.service.waveform');

        $audio = $service->findAudioByDeferredData($type, $id);

        if ($audio) {
            $waveform      = $service->findOrGenerateWaveform($audio);
            $data['peaks'] = $waveform->getPeaks();
        } else {
            $data['error'] = 'Audio was not found.';
            $status = 404;
        }

        return new JsonResponse($data, $status);
    }

    /**
     * @Route("/audio/{id}/title-edit", name="title_edit")
     *
     * @param $id
     * @param Request $request
     * @return JsonResponse
     */
    public function changeTitleAudioAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $userAudio = $em->getRepository('VocalizrAppBundle:UserAudio')->find($id);

        if (!$userAudio || $userAudio->getUserInfo() !== $this->getUser()) {
            return new JsonResponse(['success' => false]);
        }

        if ($request->get('title')) {
            $userAudio->setTitle($request->get('title'));
        } else {
            return new JsonResponse(['success' => false]);
        }

        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/audio/rearrange", name="audios_rearrange")
     *
     * @param $id
     * @param Request $request
     * @return JsonResponse
     */
    public function rearrangeUserAudios(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $order    = $request->get('order');
        $newOrder = $request->get('new_order');

        foreach ($newOrder as $id => $place) {
            if ($order[$id] === $place) {
                continue;
            }
            $userAudio = $em->getRepository(UserAudio::class)->find($id);
            $userAudio->setSortOrder($place);
        }

        $em->flush();
        return new JsonResponse(['success' => true]);
    }
}