<?php

namespace App\Twig;

use App\Entity\Project;
use App\Service\UserRestrictionService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class UserRestrictionsExtension
 *
 * @package App\Twig
 */
class UserRestrictionsExtension extends AbstractExtension
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
            new TwigFunction('can_bid', [$this, 'canBid']),
            new TwigFunction('can_hire_now', [$this, 'canHireNow']),
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