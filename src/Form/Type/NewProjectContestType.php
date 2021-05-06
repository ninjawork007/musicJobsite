<?php

namespace App\Form\Type;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewProjectContestType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $defaultLanguage = $options['english'];
        $budgets         = [];

        foreach ($options['budget'] as $value => $label) {
            $budgets[$label] = $value;
        }

        $builder
                ->add('title', null, [
                    'label' => 'Contest title *',
                    'attr'  => [
                        'class' => 'form-control',
                    ],
                ])
                ->add('budget_from', ChoiceType::class, [
                    'label' => 'Price *',
                    'attr'  => [
                        'class' => 'select2-budget budget-cost',
                    ],
                    'preferred_choices' => [''],
                    'required'          => true,
                    'choices'           => $budgets,
                ])
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
                    'gender',
                    ChoiceType::class,
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
                        'label'         => 'Genres *',
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
                    'label' => "Describe what you're looking for *",
                    'attr'  => [
                        'class' => 'form-control',
                        'rows'  => '4',
                    ],
                ])
                ->add('audio_brief', UrlType::class, [
                    'label' => 'Audio Brief Link (Youtube, Soundcloud)',
                    'attr'  => [
                        'class'       => 'form-control',
                        'placeholder' => 'https://',
                    ],
                ])
                ->add('lyrics', null, [
                    'label' => 'Lyrics',
                    'attr'  => [
                        'class' => 'form-control',
                        'rows'  => '4',
                    ],
                ])
                ->add('lyrics_needed', ChoiceType::class, [
                    'choices' => [
                        'Vocalist to provide lyrics' => '1',
                        'I will provide lyrics'      => '0',
                        ],
                    'mapped' => false,
                    'multiple'      => false,
                    'expanded'      => true,
                    'data'          => 1,
                ])
                ->add('looking_for', ChoiceType::class, [
                    'label' => 'Looking for *',
                    'attr'  => [
                        'class' => 'select2-project-type contest-type',
                    ],
                    'choices' => [
                        'producer' => 'Producer',
                        'vocalist' => 'Vocalist', ],
                    'data' => 'vocalist',
                ])
                ->add('agree', CheckboxType::class, [
                    'label'         => 'I agree to the terms & conditions',
                    'mapped' => false,
                ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'english' => 'App\Entity\Language',
            'budget' => [],
        ]);
    }

    public function getDefaultOptions(array $options)
    {
        return [
            'data_class' => 'App\Entity\Project',
        ];
    }

    public function getName()
    {
        return 'project';
    }
}