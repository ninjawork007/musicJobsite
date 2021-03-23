<?php

namespace Vocalizr\AppBundle\Model;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Vocalizr\AppBundle\Entity\Project;
use Vocalizr\AppBundle\Entity\UserActionAudit;
use Vocalizr\AppBundle\Entity\UserInfo;
use Vocalizr\AppBundle\Entity\UserWithdraw;
use Vocalizr\AppBundle\Repository\UserActionAuditRepository;

/**
 * Class UserAuditModel
 *
 * @package Vocalizr\AppBundle\Model
 * @method UserActionAuditRepository repo()
 */
class UserActionAuditModel extends Model
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var UserInfo|null
     */
    private $user;

    /**
     * @param string        $action
     * @param UserInfo|null $user
     * @param array         $data
     * @param Project       $project
     */
    public function logAction($action, $user = null, $data = [], Project $project = null)
    {
        $this->updateObject($this->createAudit($action, $user, $data, $project));
    }

    /**
     * @param string        $action
     * @param UserInfo|null $user
     * @param array         $data
     * @param Project       $project
     *
     * @return UserActionAudit
     */
    public function createAudit($action, $user = null, $data = [], Project $project = null)
    {
        if (!$user) {
            $user = $this->getUser();
        }

        $audit = new UserActionAudit();

        if ($this->container->isScopeActive('request')) {
            $request = $this->container->get('request');
            $ip      = '';
            foreach (['x-forwarded-for', 'x-real-ip'] as $headerName) {
                if ($ip && $ip !== '127.0.0.1') {
                    break;
                }
                $ip = $request->headers->get($headerName, $ip);
            }

            if (!$ip) {
                $ip = $request->getClientIp();
            }
        } else {
            $ip = '';
        }

        $audit
            ->setUser($user)
            ->setAction($action)
            ->setIpAddress($ip)
            ->setData($data)
            ->setProject($project)
        ;

        return $audit;
    }

    /**
     * @param Project $project
     *
     * @return UserActionAudit
     */
    public function createProjectReleaseEscrowAudit(Project $project)
    {
        return $this->createAudit(
            UserActionAudit::ACTION_PROJECT_RELEASE_ESCROW
        )->setProject($project);
    }

    /**
     * @param UserWithdraw $withdraw
     *
     * @return UserActionAudit
     */
    public function createWithdrawAudit(UserWithdraw $withdraw)
    {
        return $this->createAudit(
            UserActionAudit::ACTION_WITHDRAW,
            null,
            [
                'id'            => $withdraw->getId(),
                'amount'        => $withdraw->getAmount(),
                'status'        => $withdraw->getStatus(),
                'paypal_status' => $withdraw->getPaypalStatus(),
                'status_reason' => $withdraw->getStatusReason(),
            ]
        )->setWithdraw($withdraw);
    }

    /**
     * @param string            $action
     * @param UserInfo          $user
     * @param Project|null      $project
     * @param UserWithdraw|null $withdraw
     *
     * @return UserActionAudit|null
     */
    public function getLastAudit($action, UserInfo $user, Project $project = null, UserWithdraw $withdraw = null)
    {
        return $this->repo()->findLatestMatchingAuditRecord($action, $user, $project, $withdraw);
    }

    /**
     * @return UserInfo|null
     */
    private function getUser()
    {
        if (!$this->user) {
            if (!$this->container->isScopeActive('request')) {
                return null;
            }

            $token = $this->container->get('security.context')->getToken();
            if (!$token) {
                return null;
            }

            $this->user = $token->getUser();
        }

        return $this->user;
    }

    protected function getEntityName()
    {
        return 'VocalizrAppBundle:UserActionAudit';
    }
}