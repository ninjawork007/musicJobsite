<?php

namespace Vocalizr\AppBundle\Twig;

use Vocalizr\AppBundle\Entity\Project;
use Vocalizr\AppBundle\Service\UserRestrictionService;

/**
 * Class UserRestrictionsExtension
 *
 * @package Vocalizr\AppBundle\Twig
 */
class UserRestrictionsExtension extends \Twig_Extension
{
    /**
     * @var UserRestrictionService
     */
    private $service;

    public function __construct(UserRestrictionService $service)
    {
        $this->service = $service;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'user_restriction_extension';
    }

    public function getFunctions()
    {
        return [
            'can_bid'      => new \Twig_SimpleFunction('can_bid', [$this, 'canBid']),
            'can_hire_now' => new \Twig_SimpleFunction('can_hire_now', [$this, 'canHireNow']),
        ];
    }

    /**
     * @return bool
     */
    public function canBid()
    {
        return $this->service->canBid();
    }

    /**
     * @return bool
     */
    public function canHireNow()
    {
        return $this->service->canHireNow();
    }

    /**
     * @param Project $project
     *
     * @return bool
     */
    public function canReviewUserOnProject(Project $project)
    {
        return $this->service->canReviewUserOnProject($project);
    }
}