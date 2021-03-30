<?php

namespace Vocalizr\AppBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Vocalizr\AppBundle\Entity\UserInfo;

class UserInfoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $languages = $options['languages'];

        $builder->add('display_name', null, [
            'attr' => [
                'class' => 'form-control',
            ],
        ]);

        $builder->add('gender', 'choice', [
            'label'             => 'Gender',
            'attr'              => ['class' => 'select2'],
            'preferred_choices' => [''],
            'choices'           => ['' => 'Either',
                'f'                    => 'Female',
                'm'                    => 'Male', ],
        ]);

        $builder->add('profile', null, [
            'attr' => [
                'class' => 'form-control',
                'rows'  => '4',
            ],
        ]);

        $builder->add('vocalist_fee', null, [
            'attr' => [
                'class' => 'form-control',
            ],
        ]);

        $builder->add('producer_fee', null, [
            'attr' => [
                'class' => 'form-control',
            ],
        ]);

        $nameOptions = [
            'attr' => [
                'class' => 'form-control',
            ],
        ];
        if ($options['data'] instanceof UserInfo && $options['data']->isVerified()) {
            $nameOptions = [
                'attr' => [
                    'class' => 'form-control',
                    'title' => 'Name editing disabled from the moment when Your identity was verified by Stripe.'
                ],
                'disabled' => 'disabled',
            ];
        }
        $builder->add('first_name', null, array_merge([
            'label' => 'First Name: (for agreements only)'
        ], $nameOptions));
        $builder->add('last_name', null, array_merge([
            'label' => 'Last Name: (for agreements only)'
        ], $nameOptions));
        $builder->add('is_producer', null, [
            'label'    => 'Producer',
            'required' => false,
        ]);
        $builder->add('is_vocalist', null, [
            'label'    => 'Vocalist',
            'required' => false,
        ]);
        $builder->add('microphone', null, [
            'attr' => [
                'class' => 'form-control',
            ],
        ]);
        $builder->add('sounds_like', null, [
            'attr' => [
                'class'       => 'tag-input',
                'placeholder' => 'e.g. Beyonce or Justin Timberlake',
            ],
            'label' => 'Ask your friends - who do you sound similar to? (choose 3 or 4 only)',
            'mapped' => false,
        ]);

        $builder->add('genres', null, [
            'label' => 'Genres',
            'attr'  => [
                'class' => 'select2',
            ],
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('g')
                            ->orderBy('g.title', 'ASC');
            },
        ]);
        $builder->add('city', null, [
            'attr' => [
                'class' => 'form-control',
            ],
        ]);
        $builder->add('country', 'country', [
            'preferred_choices' => ['US', 'GB', 'AU', 'CA'],
            'required'          => true,
            'empty_value'       => 'Choose your country',
            'empty_data'        => null,
            'attr'              => [
                'class' => 'country select2',
            ], ]);
        /*
        $builder->add('userCountry', 'entity', array(
            'class' => Country::class,
            'label' => 'Country',
            'property' => 'title',
            'query_builder' => function(CountryRepository $repo) {
                return $repo->findAllSort();
            },
            'empty_value' => 'Choose your country',
            'empty_data' => null,
            'attr' => array(
                'class' => 'country select2',
            ),
        ));
        */
        $builder->add('vocal_characteristics', 'entity', [
            'label'         => 'Vocal Characteristics',
            'class'         => 'Vocalizr\AppBundle\Entity\VocalCharacteristic',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('vc')
                                              ->orderBy('vc.title', 'ASC');
            },
            'multiple' => true,
            'mapped'   => false,
            'attr'     => ['class' => 'select2'],
        ]);
        $builder->add('vocal_styles', 'entity', [
            'label'         => 'Vocal Styles',
            'class'         => 'Vocalizr\AppBundle\Entity\VocalStyle',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('vs')
                                              ->orderBy('vs.title', 'ASC');
            },
            'multiple' => true,
            'mapped'   => false,
            'attr'     => ['class' => 'select2'],
        ]);
        $builder->add('studio_access', null, [
            'label' => 'I have Studio Access',
        ]);

        $builder->add('languages', 'entity', [
            'label' => 'Language',
            'attr'  => [
                'class' => 'select2',
            ],
            'multiple' => true,
            'mapped'   => false,
            'class'    => 'Vocalizr\AppBundle\Entity\Language',
            'data'     => $languages,
        ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Vocalizr\AppBundle\Entity\UserInfo',
            'languages'  => [],
        ]);
    }

    public function getName()
    {
        return 'user';
    }
}
