<?php

namespace App\Twig;

use App\Entity\UserInfo;
use App\Model\UserConnectModel;
use Symfony\Bundle\TwigBundle\DependencyInjection\TwigExtension;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ProProfileExtension extends AbstractExtension
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
            new TwigFilter('user_connection_status', [$this, 'getUserConnectionStatus'], ['needs_environment' => true]),
        ];
    }

    /**
     * @param TokenInterface $token
     * @param UserInfo $other
     * @return string
     */
    public function getUserConnectionStatus(TokenInterface $token, $other)
    {
        $me = $token->getUser();
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