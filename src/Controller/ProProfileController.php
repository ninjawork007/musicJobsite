<?php

namespace App\Controller;

use Knp\Component\Pager\Pagination\PaginationInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\UserConnect;
use App\Entity\UserInfo;
use App\Entity\UserReview;
use App\Entity\UserVideo;
use App\Model\UserConnectModel;

/**
 * Class ProProfileController
 * @package App\Controller
 */
class ProProfileController extends AbstractController
{
    /**
     * @Route(name="user_pro_profile_index", path="/pro/{username}")
     * @ParamConverter("user", class="VocalizrAppBundle:UserInfo")
     * @param UserInfo $profileUser
     * @return Response
     */
    public function indexAction(UserInfo $profileUser)
    {
        if (
            !$this->container->getParameter('pro_profile_enabled') ||
            !$profileUser->isProProfileEnabled() ||
            !$profileUser->getProProfile()
        ) {
            return $this->redirect($this->generateUrl('user_view', ['username' => $profileUser->getUsername()]));
        }

        $em               = $this->getDoctrine()->getManager();
        $me               = $this->getUser();
        $userModel        = $this->get('vocalizr_app.model.user_info');
        $audioModel       = $this->get('vocalizr_app.model.user_audio');
        $userConnectModel = $this->get('vocalizr_app.model.user_connect');
        $paginator        = $this->get('knp_paginator');

        $this->get('vocalizr_app.service.statistics')->recordProfileViewStat($profileUser, $this->getUser());

        $isUserFavorite = $me ? $userModel->isInFavorites($me, $profileUser) : false;
        $isUserBlocked  = $me ? $userModel->isUserBlocked($me, $profileUser) : false;
        $userConnect    = $me ? $userConnectModel->getConnectionInviteBetweenUsers($me, $profileUser) : null;
        $topTracks      = $audioModel->getTopTracks($profileUser);
        $audioLikes     = $me ? $audioModel->getAudioLikes($me, $topTracks) : [];
        $topVideos      = $this->get('vocalizr_app.model.user_video')->getUserVideos($profileUser, 0, 5);
        $userTags       = $userModel->getTags($profileUser, $me);

        if ($userConnect && $me) {
            $connectionStatus = $userConnectModel->getConnectionInviteStatus($me, $userConnect);
        } else {
            $connectionStatus = UserConnectModel::CONNECTION_STATUS_NOT_CONNECTED;
        }

        /** @var PaginationInterface|UserReview[] $userReviews */
        $userReviews = $paginator->paginate(
            $em->getRepository('App:UserReview')->getUserReviewsQb($profileUser),
            1,
            50
        );

        /** @var PaginationInterface|UserConnect[] $topConnections */
        $topConnections = $paginator->paginate(
            $em->getRepository('App:UserConnect')
                ->findUserConnectionsQb($profileUser, 'rating'),
            1,
            15
        );

        $myStatusWithTopConnections = $me ?
            $userConnectModel->getConnectionStatusByOthersConnections($me, $profileUser, $topConnections) :
            []
        ;

        return $this->render('ProProfile/index.html.twig', [
            'user'             => $profileUser,
            'isUserFavorite'   => $isUserFavorite,
            'isUserBlocked'    => $isUserBlocked,
            'userConnect'      => $userConnect,
            'userReviews'      => $userReviews,
            'topTracks'        => $topTracks,
            'topVideos'        => $topVideos,
            'audioLikes'       => $audioLikes,
            'topConnections'   => $topConnections,
            'connectionStatus' => $connectionStatus,
            'userTags'         => $userTags,

            'myStatusWithTopConnections' => $myStatusWithTopConnections,
        ]);
    }

    public function getVideosViewStats(Request $request)
    {
        // TODO: implement
    }

    /**
     * @param UserVideo $video
     * @return JsonResponse
     */
    public function recordVideoView(UserVideo $video)
    {
        $currentViewsCount = $video->getViewsCount();
        $video->setViewsCount($currentViewsCount + 1);

        $this->get('vocalizr_app.model.user_video')->updateObject($video);

        return new JsonResponse([
            'success' => true,
            'views_count' => $video->getViewsCount(),
        ]);
    }
}