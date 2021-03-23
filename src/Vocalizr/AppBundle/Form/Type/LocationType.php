<?php

namespace Vocalizr\AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LocationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('city', 'text', [
            'attr' => [
                'class'    => 'geo hide',
                'data-geo' => 'locality',
            ], ])
                ->add('state', 'text', [
                    'attr' => [
                        'class'    => 'geo hide',
                        'data-geo' => 'administrative_area_level_1',
                    ], ])
                ->add('location_lat', 'text', [
                    'attr' => [
                        'class'    => 'geo hide',
                        'data-geo' => 'lat',
                    ], ])
                ->add('location_lng', 'text', [
                    'attr' => [
                        'class'    => 'geo hide',
                        'data-geo' => 'lng',
                    ], ])
                ->add('country', 'text', [
                    'attr' => [
                        'class'    => 'geo hide',
                        'data-geo' => 'country_short',
                    ], ]);
    }

    public function getName()
    {
        return 'geo';
    }
}