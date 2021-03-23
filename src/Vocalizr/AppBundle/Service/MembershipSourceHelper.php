<?php

namespace Vocalizr\AppBundle\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Vocalizr\AppBundle\Object\MembershipSourceObject;

/**
 * Class MembershipSourceHelper
 *
 * @package Vocalizr\AppBundle\Helper
 */
class MembershipSourceHelper
{
    const SUB_SOURCE_MAKE_YOUR_BID_PRO_BUTTON = 'probid';

    const SUB_SOURCE_CONNECTIONS_MODAL = 'unlimited_connections';

    const SUB_SOURCE_HAMBURGER_MENU = 'h_menu';

    const SUB_SOURCE_ONBOARD = 'onboard';

    const SUB_SOURCE_BANNER = 'banner';

    const SUB_SOURCE_DIRECT = 'direct';

    const SUB_SOURCE_HEADER_BUTTON = 'hbtn';

    const SUB_SOURCE_BID_LIMIT_MODAL = 'bid_limit';

    const SUB_SOURCE_BID_MESSAGING_MODAL = 'bid_messaging';

    const SUB_SOURCE_HIRE_NOW_MODAL = 'hire_now';

    const SUB_SOURCE_INSTANT_WITHDRAWALS_MODAL = 'instant_withdrawals';

    const SUB_SOURCE_UNLIMITED_CONNECTIONS_MODAL = 'unlimited_connections';

    const SUB_SOURCE_STAYHOME_PROMO = 'stayhome_promo';

    const SUB_SOURCE_ADMIN = 'admin';

    public static $sources = [
        self::SUB_SOURCE_MAKE_YOUR_BID_PRO_BUTTON,
        self::SUB_SOURCE_CONNECTIONS_MODAL,
        self::SUB_SOURCE_HAMBURGER_MENU,
        self::SUB_SOURCE_ONBOARD,
        self::SUB_SOURCE_BANNER,
        self::SUB_SOURCE_DIRECT,
        self::SUB_SOURCE_HEADER_BUTTON,
        self::SUB_SOURCE_BID_LIMIT_MODAL,
        self::SUB_SOURCE_BID_MESSAGING_MODAL,
        self::SUB_SOURCE_HIRE_NOW_MODAL,
        self::SUB_SOURCE_INSTANT_WITHDRAWALS_MODAL,
        self::SUB_SOURCE_UNLIMITED_CONNECTIONS_MODAL,
        self::SUB_SOURCE_STAYHOME_PROMO,
    ];

    const SESSION_KEY = 'membership_source';

    /**
     * @var Session
     */
    private $session;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * UserMembershipSourceHelper constructor.
     *
     * @param Session         $session
     * @param RouterInterface $router
     */
    public function __construct(Session $session, RouterInterface $router)
    {
        $this->session = $session;
        $this->router  = $router;
    }

    /**
     * @param Request $request
     * @param int     $status
     *
     * @return MembershipSourceObject
     */
    public function handleRequest(Request $request, $status = MembershipSourceObject::STATUS_MEMBERSHIP_PAGE)
    {
        if ($status === MembershipSourceObject::STATUS_MEMBERSHIP_PAGE && !$this->isRefererValid($request)) {
            $this->setSource(new MembershipSourceObject());
            return $this->getSource();
        }

        $source = $request->get('source');

        if ($source === 'membership' || !$this->isRefererValid($request) || ($this->getSource()->getStatus() === $status && !$source)) {
            $sourceObject = $this->getSource()->setStatus($status);
            $this->setSource($sourceObject);
            return $sourceObject;
        }

        if (!$source) {
            $source = self::SUB_SOURCE_DIRECT;
        }

        $referer = $request->headers->get('referer');

        $sourceObject = new MembershipSourceObject();
        $sourceObject
            ->setSource($source)
            ->setNeedReturn(!is_null($request->get('return')))
            ->setStatus($status)
            ->setReturnUrl($referer)
        ;

        $this->setSource($sourceObject);

        return $this->getSource();
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function isRefererValid(Request $request)
    {
        $referer = $request->headers->get('referer');
        $refPath = null;
        if ($referer) {
            $refPath = parse_url($referer)['path'];
        }
        if ($request->isMethod('GET') && $referer && $refPath !== $request->getRequestUri()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param int $newStatus
     */
    public function setStatus($newStatus)
    {
        $source = $this->getSource();
        $source->setStatus($newStatus);
        $this->setSource($source);
    }

    /**
     * @param MembershipSourceObject $sourceObject
     */
    public function setSource(MembershipSourceObject $sourceObject)
    {
        $this->session->set(self::SESSION_KEY, $sourceObject);
    }

    public function delete()
    {
        $this->session->remove(self::SESSION_KEY);
    }

    /**
     * @param bool $forceCreation
     *
     * @return MembershipSourceObject
     */
    public function getSource($forceCreation = true)
    {
        $source = $this->getFromSession();
        if ($source instanceof MembershipSourceObject) {
            return $source;
        }

        if ($forceCreation) {
            return new MembershipSourceObject();
        } else {
            return null;
        }
    }

    /**
     * @return MembershipSourceObject|null
     */
    private function getFromSession()
    {
        return $this->session->get(self::SESSION_KEY);
    }

    /**
     * @param string $source
     *
     * @return bool
     */
    private function isValidSource($source)
    {
        if (in_array($source, self::$sources)) {
            return true;
        }

        return false;
    }
}