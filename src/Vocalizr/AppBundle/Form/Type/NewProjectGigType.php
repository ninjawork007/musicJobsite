<?php

namespace Vocalizr\AppBundle\Form\Type;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class NewProjectGigType extends AbstractType
{
    public function __construct($defaultLanguage, $budgets)
    {
        $this->defaultLanguage = $defaultLanguage;
        $this->budgets         = $budgets;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $defaultLanguage = $this->defaultLanguage;
        $budgets         = $this->budgets;

        $builder->add('title', null, [
            'label' => 'Gig Title *',
            'attr'  => [
                'class' => 'form-control',
            ],
        ])
                ->add('looking_for', 'choice', [
                    'label' => 'Looking for *',
                    'attr'  => [
                        'class' => 'select2-project-type',
                    ],
                    'choices' => [
                        'producer' => 'Producer',
                        'vocalist' => 'Vocalist',
                    ],
                    'data' => 'vocalist',
                ])
                ->add('description', null, [
                    'label'    => 'Describe your gig in detail *',
                    'required' => true,
                    'attr'     => [
                        'class' => 'form-control',
                        'rows'  => '4',
                    ],
                ])
                ->add('audio_brief', 'url', [
                    'label' => 'Audio Brief Link (Youtube, Soundcloud)',
                    'attr'  => [
                        'class'       => 'form-control',
                        'placeholder' => 'https://',
                    ],
                ])
                ->add('gender', 'choice', [
                    'label' => 'Gender',
                    'attr'  => [
                        'class' => 'select2',
                    ],
                    'preferred_choices' => [''],
                    'choices'           => [
                        ''       => 'Either',
                        'female' => 'Female',
                        'male'   => 'Male',
                    ],
                ])
                ->add('royalty_mechanical', null, [
                    'label'    => 'Mechanical',
                    'required' => false,
                ])
                ->add('royalty_performance', null, [
                    'label'    => 'Performance',
                    'required' => false,
                ])
                ->add('royalty', null, [
                    'label' => 'Royalty %',
                    'attr'  => [
                        'class' => 'form-control percent-slider',
                    ],
                    'required' => false,
                ])
                ->add('genres', null, [
                    'label'    => 'Genre *',
                    'required' => true,
                    'attr'     => [
                        'class' => 'select2',
                    ],
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('g')
                                    ->orderBy('g.title', 'ASC');
                    },
                ])
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
                ->add(
                    'studio_access',
                    null,
                    [
                        'label' => 'Studio access required',
                    ]
                )
                ->add(
                    'due_date',
                    'date',
                    [
                        'label'  => 'Gig to be completed by',
                        'widget' => 'single_text',
                        'format' => 'MM/dd/yyyy',
                        'attr'   => [
                            'class'               => 'form-control datepicker',
                            'type'                => 'text',
                            'data-date-format'    => 'mm/dd/yyyy',
                            'data-date-autoclose' => true, ],
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
                ->add('budget', 'choice', [
                    'label' => 'Budget *',
                    'attr'  => [
                        'class' => 'select2-budget',
                    ],
                    'preferred_choices' => ['100-300'],
                    'required'          => true,
                    'choices'           => $budgets,
                    'property_path'     => false,
                ]);
    }

    public function getDefaultOptions(array $options)
    {
        return [
            'data_class'        => 'Vocalizr\AppBundle\Entity\Project',
            'validation_groups' => ["project", "project_create", "Default"],
        ];
    }

    public function getName()
    {
        return 'project';
    }
}