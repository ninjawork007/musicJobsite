<?php

namespace App\Object;

use App\Service\MembershipSourceHelper;

/**
 * Class MembershipSourceObject
 *
 * @package App\Object
 */
class MembershipSourceObject
{
    const STATUS_MEMBERSHIP_PAGE = 1;

    const STATUS_UPGRADE_PAGE = 2;

    const STATUS_START_PAYING = 3;

    /**
     * @var string
     */
    private $source = MembershipSourceHelper::SUB_SOURCE_DIRECT;

    /**
     * @var int
     */
    private $status = self::STATUS_MEMBERSHIP_PAGE;

    /**
     * @var bool
     */
    private $needReturn = false;

    /**
     * @var string|null
     */
    private $returnUrl;

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     *
     * @return MembershipSourceObject
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     *
     * @return MembershipSourceObject
     */
    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNeedReturn()
    {
        return $this->needReturn;
    }

    /**
     * @param bool $needReturn
     *
     * @return MembershipSourceObject
     */
    public function setNeedReturn($needReturn)
    {
        $this->needReturn = $needReturn;
        return $this;
    }

    /**
     * @return string
     */
    public function getReturnUrl()
    {
        return $this->returnUrl;
    }

    /**
     * @param string $returnUrl
     *
     * @return MembershipSourceObject
     */
    public function setReturnUrl($returnUrl)
    {
        $this->returnUrl = $returnUrl;
        return $this;
    }
}