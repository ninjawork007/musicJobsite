<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class LocationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('city', TextType::class, [
                    'attr' => [
                        'class'    => 'geo hide',
                        'data-geo' => 'locality',
                    ], ])
                ->add('state', TextType::class, [
                    'attr' => [
                        'class'    => 'geo hide',
                        'data-geo' => 'administrative_area_level_1',
                    ], ])
                ->add('location_lat', TextType::class, [
                    'attr' => [
                        'class'    => 'geo hide',
                        'data-geo' => 'lat',
                    ], ])
                ->add('location_lng', TextType::class, [
                    'attr' => [
                        'class'    => 'geo hide',
                        'data-geo' => 'lng',
                    ], ])
                ->add('country', TextType::class, [
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