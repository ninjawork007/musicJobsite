<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use App\Entity\Notification;
use App\Entity\UserInfo;
use App\Model\HintModel;

/**
 * Class HintController
 *
 * @package App\Controller
 */
class HintController extends AbstractController
{
    const SHOW_CONFIRM_EMAIL_MODAL = 'show_confirm_email_modal';

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function showConfirmEmailModalAction(Request $request)
    {
        $openDelay = 60;
        /** @var UserInfo $user */
        $user     = $this->getUser();
        $response = new Response();
        if (!$user) {
            return $response;
        }
        if ($user->getEmailConfirmed()) {
            return $response;
        }

        if ($modalOptions = $request->getSession()->get(self::SHOW_CONFIRM_EMAIL_MODAL, false)) {
            if (is_array($modalOptions)) {
                $openDelay = $modalOptions['open_delay'];
            } else {
                $openDelay = 0;
            }
            $request->getSession()->remove(self::SHOW_CONFIRM_EMAIL_MODAL);

            return $this->render('include/verify_email_popup.html.twig', [
                'open_delay' => ($openDelay + 1) * 1000,
            ]);
        } else {
            return $this->render('include/verify_email_popup.html.twig');
        }
    }

    /**
     * Render view for specified parameters. If hint type is not specified, use 'hint' bag from session.
     *
     * @return Response
     */
    public function showAction()
    {
        //return $this->render('VocalizrAppBundle:Hint:hint.html.twig', ['amount' => 12313, 'type'=>1]);
        /** @var Session $session */
        $session = $this->get('session');
        $bag     = $session->getFlashBag();

        // Try to fetch hint type from session.
        $hintDatas = $bag->get('hint');

        if (!$hintDatas || !is_array($hintDatas) || count($hintDatas) === 0) {
            return new Response();
        }

        $hintData = json_decode($hintDatas[0]);

        if (!$hintData) {
            return new Response();
        }

        $hintType = $hintData->type;
        $amount   = $hintData->amount;
        $target   = $hintData->target;
        if ($amount === null) {
            $amount = 0;
        }

        $session->remove('hint');

        if (!in_array($hintType, HintModel::$hintIds)) {
            return new Response();
        }

        if ($this->get('vocalizr_app.model.hint')->isSkipped($hintType)) {
            return new Response();
        }

        $parameters = [
            'amount' => $amount,
            'type'   => $hintType,
            'target' => $target,
        ];

        switch ($hintType) {
            case HintModel::HINT_GIG:
                return $this->render('App:Hint:gig_hint.html.twig', $parameters);
            case HintModel::HINT_CONTEST:
                return $this->render('App:Hint:contest_hint.html.twig', $parameters);
            default:
                return new Response();
        }
    }

    /**
     * @Route("/hint/{type}/{action}", name="hint_visibility")
     *
     * @param string $action
     * @param int    $type
     *
     * @return JsonResponse
     */
    public function hintVisibilityAction($type, $action)
    {
        $success = $this->get('vocalizr_app.model.hint')->setVisibility($type, $action);

        return new JsonResponse(['success' => $success]);
    }

    /**
     * @return Response
     */
    public function showLastDepositRefundedModalAction()
    {
        if (!$user = $this->getUser()) {
            return new Response();
        }

        $em = $this->getDoctrine()->getManager();

        /** @var Notification[] $depositFailedNotifications */
        $depositFailedNotifications = $em->getRepository('App:Notification')
            ->findUnreadByUserAndType($user, Notification::NOTIFY_TYPE_WALLET_DEPOSIT_FAILED, false);

        if (empty($depositFailedNotifications)) {
            return new Response();
        }

        foreach ($depositFailedNotifications as $notification) {
            $notification->setNotifyRead(true);
        }

        $em->flush();

        return $this->render('Financial/deposit_failed_modal.html.twig');
    }
}