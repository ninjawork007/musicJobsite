<?php

namespace App\Form\Type;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditProjectType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $defaultLanguage = $options['english'];
        $budgets         = [];

        foreach ($options['budget'] as $value => $label) {
            $budgets[$label] = $value;
        }

        $builder->add(
            'budget',
            ChoiceType::class,
            [
                'label'             => 'Budget',
                'attr'              => ['class' => 'select2'],
                'preferred_choices' => [''],
                'required'          => true,
                'mapped'            => false,
                'choices'           => $budgets,
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
                    DateType::class,
                    [
                        'label'  => 'Gig to be completed by',
                        'widget' => 'single_text',
//                        'format' => 'MM/dd/yyyy',
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
                    ChoiceType::class,
                    [
                        'label'             => 'Gender',
                        'attr'              => ['class' => 'select2'],
                        'preferred_choices' => [''],
                        'choices'           => [
                            'Either'  => '',
                            'Female'  => 'female',
                            'Male'    => 'male',
                        ],
                    ]
                )
                ->add(
                    'looking_for',
                    ChoiceType::class,
                    [
                        'label'             => 'Looking for',
                        'attr'              => ['class' => 'select2'],
                        'preferred_choices' => [''],
                        'choices'           => [
                                'Either'    => '',
                                'Producer'  => 'producer',
                                'Vocalist'  => 'vocalist',
                            ],
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
                ->add('audio_brief', UrlType::class, [
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
            'data_class' => 'App\Entity\Project',
            'validation_groups' => ["project", "project_update", "Default"],
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // ...,
            'english' => 'App\Entity\Language',
            'budget' => [],
        ]);
    }
    public function getName()
    {
        return 'project';
    }
}