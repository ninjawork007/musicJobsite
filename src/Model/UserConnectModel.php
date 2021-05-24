<?php

namespace App\Model;

use App\Entity\Counter;
use App\Entity\UserBlock;
use App\Entity\UserConnect;
use App\Entity\UserConnectInvite;
use App\Entity\UserInfo;
use App\Exception\CounterLimitReachedException;
use App\Exception\UserConnectionNotAllowedException;
use App\Repository\UserConnectInviteRepository;

/**
 * Class UserConnectModel
 * @package App\Model
 */
class UserConnectModel extends Model
{
    const CONNECTION_STATUS_NOT_CONNECTED   = 'not_connected';
    const CONNECTION_STATUS_REQUEST_SENT    = 'request_sent';
    const CONNECTION_STATUS_AWAITING_ANSWER = 'await_answer';
    const CONNECTION_STATUS_CONNECTED       = 'connected';
    const CONNECTION_STATUS_UNACCEPTABLE    = 'unacceptable';

    const CONSTRAINT_NOT_SUBSCRIBED  = 'not_subscribed';
    const CONSTRAINT_SELF_CONNECTION = 'self_connection';
    const CONSTRAINT_BLOCKED         = 'blocked';
    const CONSTRAINT_HAVE_REQUEST    = 'have_connection_request';
    const CONSTRAINT_CONNECTED       = 'connected';
    const CONSTRAINT_REQUEST_SENT    = 'request_sent';
    const CONSTRAINT_LIMIT_REACHED   = 'limit_reached';

    /**
     * @param UserInfo $me
     * @param UserInfo $otherUser
     * @param UserConnect[] $othersConnections
     *
     * @return string[] - statuses indexed by user ids.
     */
    public function getConnectionStatusByOthersConnections(UserInfo $me, UserInfo $otherUser, $othersConnections)
    {
        $statuses = [];
        $users    = [];

        foreach ($othersConnections as $connection) {
            $users[] = $user = $this->getOtherParty($otherUser, $connection);
            if ($me !== $user) {
                $statuses[$user->getId()] = self::CONNECTION_STATUS_NOT_CONNECTED;
            } else {
                $statuses[$user->getId()] = self::CONNECTION_STATUS_UNACCEPTABLE;
            }
        }

        $myInvites = $this->getConnectionInvites($me, $users);

        foreach ($myInvites as $uid => $invite) {
            $statuses[$uid] = $this->getConnectionInviteStatus($me, $invite);
        }

        return $statuses;
    }

    /**
     * @param UserInfo $from
     * @param UserInfo $toUser
     * @param string|null $message - custom message for connection, if required
     * @throws UserConnectionNotAllowedException
     */
    public function requestConnection(UserInfo $from, UserInfo $toUser, $message = null)
    {
        $violations = $this->validateConnectionAttempt($from, $toUser);

        if ($violations) {
            throw new UserConnectionNotAllowedException($violations);
        }

        $invite = new UserConnectInvite();
        $invite
            ->setFrom($from)
            ->setTo($toUser)
            ->setMessage($this->processMessage($message))
        ;

        $this->updateObject($invite);

        $counterModel = $this->container->get('vocalizr_app.model.counter');

        $counterModel->incrementCounter($from, CounterModel::COUNTER_GROUP_CONNECT, [
            $from->getSubscriptionPlan()->getConnectMonthLimit(),
        ]);
    }

    /**
     * @param UserInfo $forUser
     * @param UserConnectInvite $invite
     * @return string
     */
    public function getConnectionInviteStatus(UserInfo $forUser, UserConnectInvite $invite)
    {
        // Outgoing connection
        if ($forUser === $invite->getFrom()) {
            if ($invite->getStatus()) {
                return self::CONNECTION_STATUS_CONNECTED;
            } else {
                return self::CONNECTION_STATUS_REQUEST_SENT;
            }
        } elseif ($forUser === $invite->getTo()) {
            if ($invite->getStatus()) {
                return self::CONNECTION_STATUS_CONNECTED;
            } else {
                return self::CONNECTION_STATUS_AWAITING_ANSWER;
            }
        } else {
            throw new \LogicException('Invite is not related to user.');
        }
    }

    /**
     * @param UserInfo $fromUser
     * @param UserInfo[] $toUsers
     * @return UserConnectInvite[] - connection invites (or nulls if connection not found) indexed by other party's ids.
     */
    public function getConnectionInvites(UserInfo $fromUser, $toUsers)
    {
        $toUserIds = [];

        if (!$toUsers) {
            return [];
        }

        foreach ($toUsers as $toUser) {
            if (!is_object($toUser)) {
                $toUserIds[] = $toUser;
            }
            $toUserIds[] = $toUser->getId();
        }

        $connections = $this->getInviteRepo()->getUserConnectionsByIds($fromUser, $toUserIds);

        $indexedInvites = [];

        foreach ($connections as $connection) {
            $indexedInvites[$this->getOtherParty($fromUser, $connection)->getId()] = $connection;
        }

        return $indexedInvites;
    }

    /**
     * @param UserInfo $user1
     * @param UserInfo $user2
     * @return null|UserConnectInvite
     */
    public function getConnectionInviteBetweenUsers(UserInfo $user1, UserInfo $user2)
    {
        return $this->getInviteRepo()->getConnectionInviteBetweenUsers($user1, $user2);
    }

    /**
     * @param UserInfo $user
     * @return int
     */
    public function getConnectionsLeft(UserInfo $user)
    {

        $counterModel = $this->container->get('vocalizr_app.model.counter');

        /** @var Counter[] $counters */
        $counters = $counterModel->getCounters($user, CounterModel::COUNTER_GROUP_CONNECT);

        $totalLeft = 0;

        foreach ($counters as $counter) {
            if ($counter->getType() === Counter::TYPE_CONNECT) {
                $limit = $user->getSubscriptionPlan()->getConnectMonthLimit();
            } else {
                $limit = $counter->getLimit();
            }

            if (!$limit) {
                continue;
            }

            $totalLeft += $counter->getLimit() - $counter->getCount();
        }

        if (!$counters) {
            return $user->getSubscriptionPlan()->getConnectMonthLimit();
        }

        return $totalLeft;
    }

    /**
     * @param UserInfo $fromUser
     * @param UserInfo $toUser
     * @return array - array of connection  violations.
     */
    public function validateConnectionAttempt(UserInfo $fromUser, UserInfo $toUser)
    {
        $violations = [];

        if ($fromUser === $toUser) {
            $violations[] = self::CONSTRAINT_SELF_CONNECTION;
        }

        if ($this->em->getRepository(UserBlock::class)->findOneBy([
            'user_info'  => $toUser,
            'block_user' => $fromUser,
        ])) {
            $violations[] = self::CONSTRAINT_BLOCKED;
        }

        $invite = $this->getConnectionInviteBetweenUsers($fromUser, $toUser);

        if ($invite) {
            $violations[] = self::CONSTRAINT_HAVE_REQUEST;
            $violations[] = $invite->getConnectedAt() ? self::CONSTRAINT_CONNECTED : self::CONSTRAINT_REQUEST_SENT;
        }

        $connectionsLeft = $this->getConnectionsLeft($fromUser);

        if ($connectionsLeft == 0) {
            $violations[] = self::CONSTRAINT_LIMIT_REACHED;
        }

        return $violations;
    }

    protected function getEntityName()
    {
        return UserConnect::class;
    }

    /**
     * @param UserInfo $user
     * @param UserConnectInvite|UserConnect $inviteOrConnection
     * @return UserInfo
     */
    private function getOtherParty(UserInfo $user, $inviteOrConnection)
    {
        if ($user === $inviteOrConnection->getFrom()) {
            return $inviteOrConnection->getTo();
        } elseif ($user === $inviteOrConnection->getTo()) {
            return $inviteOrConnection->getFrom();
        } else {
            throw new \LogicException('Connection is not related to user.');
        }
    }

    /**
     * @return UserConnectInviteRepository
     */
    private function getInviteRepo()
    {
        return $this->em->getRepository(UserConnectInvite::class);
    }

    /**
     * @param string|null $message
     * @return string
     */
    private function processMessage($message)
    {
        $messageRemoveMatch = '/[^\s]*(http(s)*:\/\/|@|\.(com|net|me))[\w\/\-\.=?&]*/miu';

        if (!$message) {
            return $this->container->getParameter('connect_default_msg');
        }

        $message = substr($message, 0, 200);

        return preg_replace($messageRemoveMatch, '', $message);
    }
}