<?php

namespace App\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use App\Entity\Country;
use App\Repository\CountryRepository;

class UserSearchType extends AbstractType
{
    public $fees = [];
    public $lockCertified = null;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if(isset($options['data']) && isset($options['data']['budget'])) {
            foreach ($options['data']['budget'] as $key => $price) {
                $this->fees[$price] = $key;
            }
        }
        $this->lockCertified = isset($options['data']) && isset($options['data']['lockCertified']) ? $options['data']['lockCertified'] : [];

        $builder->add('gender', ChoiceType::class, [
            'label'             => 'Gender',
            'attr'              => ['class' => 'select2'],
            'preferred_choices' => [''],
            'choices'           => [
                ''   => 'Either',
                'f'  => 'Female',
                'm'  => 'Male',
                ],
            ]);

        $builder->add('genre', EntityType::class, [
            'label'          => 'GENRES',
            'class'          => 'App:Genre',
            'multiple'       => true,
            'choice_label'       => 'title',
//            'property'       => 'title',
            'placeholder'    => 'Choose a genre',
//            'empty_value'    => 'Choose a genre',
            'attr'           => ['class' => 'select2'],
            'query_builder'  => function (EntityRepository $er) {
                return $er->createQueryBuilder('g')
                    ->orderBy('g.title', 'ASC');
            },
        ]);
        $builder->add('studio_access', CheckboxType::class);
        $builder->add('audio', CheckboxType::class);

        $certifiedAttrs = [];
        if ($this->lockCertified) {
            $certifiedAttrs['disabled'] = 'disabled';
        }
        if (!$this->lockCertified) {
            $builder->add('certified', CheckboxType::class, [
                'attr' => $certifiedAttrs,
            ]);
        }

        $builder->add('vocal_characteristic', EntityType::class, [
            'label'         => 'Vocal Characteristics',
            'class'         => 'App:VocalCharacteristic',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('vc')
                        ->orderBy('vc.title', 'ASC');
            },
            'multiple' => true,
            'mapped'   => true,
            'attr'     => [
                    'class' => 'select2'
                ],
        ]);
        $builder->add('vocal_style', EntityType::class, [
            'label'         => 'Vocal Styles',
            'class'         => 'App:VocalStyle',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('vs')
                                ->orderBy('vs.title', 'ASC');
            },
            'multiple' => true,
            'mapped'   => true,
            'attr'     => [
                'class' => 'select2'
            ],
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

        $builder->add('country', EntityType::class, [
            'class'         => Country::class,
            'choice_label'  => 'title',
//            'property'      => 'title',
            'query_builder' => function (CountryRepository $repo) {
                return $repo->findAllSort();
            },
            'placeholder' => 'Start typing the country...',
//            'empty_value' => 'Start typing the country...',
            'empty_data'  => null,
            'attr'        => [
                'class' => 'country select2',
            ], ]);

        /* $builder->add('country', 'country', array(
            'preferred_choices' => array('US', 'GB', 'AU', 'CA'),
            'required' => true,
            'empty_value' => 'Choose country',
            'empty_data' => null,
            'attr' => array(
                'class' => 'country select2',
        ))); */

        $builder->add('city', TextType::class, [
            'attr' => [
                'class'    => 'geo hide',
                'data-geo' => 'locality',
                'placeholder' => 'Start typing the city...'
            ],
            'label' => false,
        ]);

        if ($this->fees) {
            $feeChoices = ['' => 'Please select'] + $this->fees;

            $builder->add('fees', ChoiceType::class, [
                'label'             => 'Fee',
                'attr'              => ['class' => 'select2'],
                'preferred_choices' => [''],
                'choices'           => $feeChoices,
                ]);
        }

        $builder->add('languages', EntityType::class, [
            'label' => 'Language',
            'attr'  => [
                'class' => 'select2',
            ],
            'multiple' => true,
            'class'    => 'App:Language',
        ]);
    }

    public function getName()
    {
        return 'user_search';
    }
}
