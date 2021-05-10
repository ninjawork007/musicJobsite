<?php

namespace App\Form\Type;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

use App\Entity\Genre;

class VocalistSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $budgets         = [];

        foreach ($options['budget'] as $value => $label) {
            $budgets[$label] = $value;
        }

        /** @var EntityManager $em */
        $em = $options['data']['em'];

        /**
         * @var Genre[] $results
         *              Get genres
         */
        $results = $em->getRepository('App:Genre')
                ->getAll();
        $genres = [];
        if ($results) {
            foreach ($results as $result) {
                $genres[$result->getId()] = $result->getTitle();
            }
        }

        // Get vocal chars
        $results = $em->getRepository('App:VocalCharacteristic')
                ->getAll();
        $vocalChars = [];
        if ($results) {
            foreach ($results as $result) {
                $vocalChars[$result->getId()] = $result->getTitle();
            }
        }

        // Get vocal styles
        $results = $em->getRepository('App:VocalStyle')
                ->getAll();
        $vocalStyles = [];
        if ($results) {
            foreach ($results as $result) {
                $vocalStyles[$result->getId()] = $result->getTitle();
            }
        }

        $builder->add('gender', ChoiceType::class, [
            'label' => 'Gender',
            'attr'                                 => ['class' => 'select2'],
            'preferred_choices'                    => [''],
            'choices'                              => ['' => 'Either',
                'f'                                       => 'Female',
                'm'                                       => 'Male', ], ]);

        $builder->add('genre', ChoiceType::class, [
            'label'       => 'GENRES',
            'multiple'    => true,
            'empty_value' => 'Choose a genre',
            'attr'        => ['class' => 'select2'],
            'choices'     => $genres,
        ]);
        $builder->add('studio_access', CheckboxType::class);
        $builder->add('audio', 'checkbox');
        $builder->add('certified', 'checkbox');

        $builder->add('vocal_characteristic', ChoiceType::class, [
            'label'    => 'Vocal Characteristics',
            'multiple' => true,
            'mapped'   => false,
            'attr'     => ['class' => 'select2'],
            'choices'  => $vocalChars,
        ]);
        $builder->add('vocal_style', ChoiceType::class, [
            'label'    => 'Vocal Styles',
            'multiple' => true,
            'attr'     => ['class' => 'select2'],
            'choices'  => $vocalStyles,
        ]);
        $builder->add('sounds_like', TextType::class, [
            'attr' => [
                'class' => 'form-control',
            ],
            'label' => 'SOUNDS LIKE',
        ]);
        $builder->add('username', TextType::class, [
            'attr' => [
                'class' => 'form-control',
            ],
            'label' => 'USERNAME',
        ]);
        $builder->add('city', null, [
            'attr' => [
                'class'    => 'geo hide',
                'data-geo' => 'locality',
            ],
            'label' => false,
        ]);

        if ($budgets) {
//            $feeChoices = ['' => 'Please select'] + $budgets;
            $builder->add('fees', ChoiceType::class, [
                    'label'             => 'Fee',
                    'attr'              => ['class' => 'select2'],
                    'preferred_choices' => [''],
                    'choices'           => $budgets,
                ]);
        }
    }

    public function getName()
    {
        return 'us';
    }
}
