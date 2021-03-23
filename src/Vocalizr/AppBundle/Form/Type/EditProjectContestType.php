<?php

namespace Vocalizr\AppBundle\Form\Type;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class EditProjectContestType extends AbstractType
{
    public function __construct($defaultLanguage)
    {
        $this->defaultLanguage = $defaultLanguage;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $defaultLanguage = $this->defaultLanguage;

        $builder->add('royalty', null, [
            'label' => 'Royalty %',
            'attr'  => [
                'class' => 'form-control percent-slider',
            ],
            'required' => false,
        ])
                ->add('royalty_mechanical', null, [
                    'label'    => 'Mechanical',
                    'required' => false,
                ])
                ->add('royalty_performance', null, [
                    'label'    => 'Performance',
                    'required' => false,
                ])
                ->add(
                    'studio_access',
                    null,
                    [
                        'label' => 'Studio access required',
                    ]
                )
                ->add(
                    'bpm',
                    null,
                    [
                        'label' => 'BPM',
                        'attr'  => [
                            'class' => 'form-control',
                            'type'  => 'number',
                        ],
                    ]
                )
                ->add(
                    'pro_required',
                    null,
                    [
                        'label' => 'Vocalizr Certified Pros Only',
                    ]
                )
                ->add('audio_brief', 'url', [
                    'label' => 'Audio Brief Link (Youtube, Soundcloud)',
                    'attr'  => [
                        'class'       => 'form-control',
                        'placeholder' => 'https://',
                    ],
                ])
                ->add(
                    'gender',
                    'choice',
                    [
                        'label'             => 'Gender',
                        'attr'              => ['class' => 'select2'],
                        'preferred_choices' => [''],
                        'choices'           => ['' => 'Either',
                            'female'               => 'Female',
                            'male'                 => 'Male', ],
                    ]
                )
                ->add(
                    'language',
                    null,
                    [
                        'label'             => 'Language',
                        'preferred_choices' => [$defaultLanguage],
                        'attr'              => ['class' => 'select2'],
                        'data'              => $defaultLanguage,
                        'query_builder'     => function (EntityRepository $er) {
                            return $er->createQueryBuilder('l')
                                        ->orderBy('l.title', 'ASC');
                        },
                    ]
                )
                ->add(
                    'genres',
                    null,
                    [
                        'label'         => 'Genres',
                        'attr'          => ['class' => 'select2'],
                        'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('g')
                                        ->orderBy('g.title', 'ASC');
                        },
                    ]
                )
                ->add(
                    'vocalStyles',
                    null,
                    [
                        'label'         => 'Vocal Styles',
                        'attr'          => ['class' => 'select2'],
                        'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('vs')
                                        ->orderBy('vs.title', 'ASC');
                        },
                    ]
                )
                ->add(
                    'vocalCharacteristics',
                    null,
                    [
                        'label'         => 'Vocal Characteristics',
                        'attr'          => ['class' => 'select2'],
                        'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('vc')
                                        ->orderBy('vc.title', 'ASC');
                        },
                    ]
                )
                ->add('description', null, [
                    'label' => "Describe what you're looking for",
                    'attr'  => [
                        'class' => 'form-control',
                        'rows'  => '4',
                    ],
                ])
                ->add('lyrics', null, [
                    'label' => 'Lyrics <span class="help-note">ENTER SOMETHING YOU MIGHT WANT TO HEAR</span>',
                    'attr'  => [
                        'class' => 'form-control',
                        'rows'  => '4',
                    ],
                ])
                ->add('lyrics_needed', 'choice', [
                    'choices' => [
                        '1' => 'Vocalist to provide lyrics',
                        '0' => 'I will provide lyrics', ],
                    'property_path' => false,
                    'multiple'      => false,
                    'expanded'      => true,
                ]);
    }

    public function getDefaultOptions(array $options)
    {
        return [
            'data_class' => 'Vocalizr\AppBundle\Entity\Project',
        ];
    }

    public function getName()
    {
        return 'project';
    }
}