<?php

namespace App\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

use App\Entity\Language;
use App\Entity\Project;

class ProjectSearchType extends AbstractType
{


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $budget = isset($options['data']) ? $options['data'] : [];

        $builder->add('keywords', null, [
            'attr' => [
                'class' => 'form-control',
            ],
        ]);

        $builder->add('project_type', ChoiceType::class, [
            'label'   => 'Type',
            'choices' => [
                    Project::PROJECT_TYPE_PAID => 'Gig',
                    Project::PROJECT_TYPE_CONTEST        => 'Contest',
                ],
            'expanded' => true,
            'multiple' => true,
        ]);

        $builder->add('looking_for', ChoiceType::class, [
            'label'   => 'Jobs For',
            'choices' => [
                    'vocalist' => 'Vocalists',
                    'producer'           => 'Producers',
                ],
            'expanded' => true,
            'multiple' => true,
        ]);

        $builder->add(
            'gender',
            ChoiceType::class,
            [
                'attr'        => ['class' => 'select2'],
                'label'       => 'Gender',
                'choices'     => [
                    'male'   => 'Male',
                    'female' => 'Female',
                    ],
            ]
        );

        $builder->add('genre', EntityType::class, [
            'label'         => 'GENRES',
            'class'         => 'App:Genre',
            'multiple'      => true,
            'attr'          => ['class' => 'select2'
            ],
        ]);

        $builder->add('studio_access', CheckboxType::class);

        $builder->add('vocal_characteristic', EntityType::class, [
            'label'         => 'Vocal Characteristics',
            'class'         => 'App\Entity\VocalCharacteristic',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('vc')
                                              ->orderBy('vc.title', 'ASC');
            },
            'multiple' => true,
            'attr'     => ['class' => 'select2'],
        ]);
        $builder->add('vocal_style', EntityType::class, [
            'label'         => 'Vocal Styles',
            'class'         => 'App:VocalStyle',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('vs')
                                              ->orderBy('vs.title', 'ASC');
            },
            'multiple' => true,
            'attr'     => ['class' => 'select2'],
        ]);

        $builder->add('languages', EntityType::class, [
            'label' => 'Language',
            'attr'  => [
                'class' => 'select2',
            ],
            'multiple' => true,
            'class'    => Language::class,
        ]);

        $choices = ['' => 'Please select'] + $budget;
        $builder->add('budget', ChoiceType::class, [
            'label'                                 => 'Budget',
            'attr'                                 => ['class' => 'select2'],
            'preferred_choices'                    => [''],
            'choices'                              => $choices
            ]);
    }

    public function getName()
    {
        return 'project_search';
    }
}
