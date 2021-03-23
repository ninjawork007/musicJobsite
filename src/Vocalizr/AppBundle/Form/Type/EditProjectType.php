<?php

namespace Vocalizr\AppBundle\Form\Type;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class EditProjectType extends AbstractType
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

        $builder->add(
            'budget',
            'choice',
            [
                'label'             => 'Budget',
                'attr'              => ['class' => 'select2'],
                'preferred_choices' => [''],
                'required'          => true,
                'choices'           => $budgets,
                'property_path'     => false,
            ]
        )
                /*
                ->add('project_type', 'choice', array(
                    'label' => 'Gig type',
                    'expanded' => true,
                    'multiple' => false,
                    'choices' => array(
                        'paid' => 'Paid gig',
                        'collaboration' => 'Collaboration'
                    )
                ))
                 *
                 */
                ->add('royalty', null, [
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
                    'studio_access',
                    null,
                    [
                        'label' => 'Studio access required',
                    ]
                )
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
                    'looking_for',
                    'choice',
                    [
                        'label'             => 'Looking for',
                        'attr'              => ['class' => 'select2'],
                        'preferred_choices' => [''],
                        'choices'           => ['' => 'Either',
                            'producer'             => 'Producer',
                            'vocalist'             => 'Vocalist', ],
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
                ->add('audio_brief', 'url', [
                    'label' => 'Audio Brief Link (Youtube, Soundcloud)',
                    'attr'  => [
                        'class'       => 'form-control',
                        'placeholder' => 'https://',
                    ],
                ])
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
                ->add(
                    'description',
                    null,
                    [
                        'label' => 'Describe your gig in detail',
                        'attr'  => [
                            'class' => 'form-control',
                            'rows'  => '4',
                        ],
                    ]
                );
    }

    public function getDefaultOptions(array $options)
    {
        return [
            'data_class' => 'Vocalizr\AppBundle\Entity\Project',
            'validation_groups' => ["project", "project_update", "Default"],
        ];
    }

    public function getName()
    {
        return 'project';
    }
}