<?php

namespace App\Twig;

use App\Model\CountryModel;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class CountryExtension
 *
 * @package App\Twig
 */
class CountryExtension extends AbstractExtension
{
    /**
     * @var CountryModel
     */
    private $countryModel;

    /**
     * CountryExtension constructor.
     *
     * @param CountryModel $countryModel
     */
    public function __construct(CountryModel $countryModel)
    {
        $this->countryModel = $countryModel;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            'get_country_list' => new TwigFunction( 'get_country_list', [$this, 'getAllCountries']),
        ];
    }

    public function getName()
    {
        return 'country_extension';
    }

    public function getAllCountries()
    {
        return $this->countryModel->getAll();
    }
}