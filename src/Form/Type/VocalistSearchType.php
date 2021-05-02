<?php

namespace App\Form\Type;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use App\Entity\Genre;

class VocalistSearchType extends AbstractType
{
    /** @var EntityManager $em */
    private $em;

    private $fees;

    public function __construct($fees = null, $em)
    {
        $this->fees = $fees;
        $this->em   = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var EntityManager $em */
        $em = $this->em;

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

        $builder->add('gender', 'choice', ['label' => 'Gender',
            'attr'                                 => ['class' => 'select2'],
            'preferred_choices'                    => [''],
            'choices'                              => ['' => 'Either',
                'f'                                       => 'Female',
                'm'                                       => 'Male', ], ]);

        $builder->add('genre', 'choice', [
            'label'       => 'GENRES',
            'multiple'    => true,
            'empty_value' => 'Choose a genre',
            'attr'        => ['class' => 'select2'],
            'choices'     => $genres,
        ]);
        $builder->add('studio_access', 'checkbox');
        $builder->add('audio', 'checkbox');
        $builder->add('certified', 'checkbox');

        $builder->add('vocal_characteristic', 'choice', [
            'label'    => 'Vocal Characteristics',
            'multiple' => true,
            'mapped'   => false,
            'attr'     => ['class' => 'select2'],
            'choices'  => $vocalChars,
        ]);
        $builder->add('vocal_style', 'choice', [
            'label'    => 'Vocal Styles',
            'multiple' => true,
            'attr'     => ['class' => 'select2'],
            'choices'  => $vocalStyles,
        ]);
        $builder->add('sounds_like', 'text', [
            'attr' => [
                'class' => 'form-control',
            ],
            'label' => 'SOUNDS LIKE',
        ]);
        $builder->add('username', 'text', [
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

        if ($this->fees) {
            $feeChoices = ['' => 'Please select'] + $this->fees;
            $builder->add('fees', 'choice', ['label' => 'Fee',
                'attr'                               => ['class' => 'select2'],
                'preferred_choices'                  => [''],
                'choices'                            => $feeChoices, ]);
        }
    }

    public function getName()
    {
        return 'us';
    }
}
