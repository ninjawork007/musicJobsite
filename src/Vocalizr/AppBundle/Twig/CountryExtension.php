<?php

namespace Vocalizr\AppBundle\Twig;

use Vocalizr\AppBundle\Model\CountryModel;

/**
 * Class CountryExtension
 *
 * @package Vocalizr\AppBundle\Twig
 */
class CountryExtension extends \Twig_Extension
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
            'get_country_list' => new \Twig_Function_Method($this, 'getAllCountries'),
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