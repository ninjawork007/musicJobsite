<?php

namespace Vocalizr\AppBundle\Twig;

use Vocalizr\AppBundle\Entity\UserInfo;
use Vocalizr\AppBundle\Model\UserConnectModel;

class ProProfileExtension extends \Twig_Extension
{
    /**
     * @var UserConnectModel
     */
    private $connectModel;

    public function __construct(UserConnectModel $connectModel)
    {
        $this->connectModel = $connectModel;
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('user_connection_status', [$this, 'getUserConnectionStatus'], ['needs_environment' => true]),
        ];
    }

    /**
     * @param \Twig_Environment $environment
     * @param UserInfo $other
     * @return string
     */
    public function getUserConnectionStatus(\Twig_Environment $environment, $other)
    {
        $me = $environment->getGlobals()['app']->getUser();
        $invite = $this->connectModel->getConnectionInviteBetweenUsers($me, $other);
        if (!$invite) {
            return UserConnectModel::CONNECTION_STATUS_NOT_CONNECTED;
        }

        return $this->connectModel->getConnectionInviteStatus($me, $invite);
    }

    public function getName()
    {
        return 'pro_profile_extension';
    }
}