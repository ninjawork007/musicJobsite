<?php

namespace Vocalizr\AppBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Vocalizr\AppBundle\Entity\Language;
use Vocalizr\AppBundle\Entity\Project;

class ProjectSearchType extends AbstractType
{
    public function __construct($budget)
    {
        $this->budget = $budget;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('keywords', null, [
            'attr' => [
                'class' => 'form-control',
            ],
        ]);

        $builder->add('project_type', 'choice', [
            'label'   => 'Type',
            'choices' => [Project::PROJECT_TYPE_PAID => 'Gig',
                Project::PROJECT_TYPE_CONTEST        => 'Contest', ],
            'expanded' => true,
            'multiple' => true,
        ]);

        $builder->add('looking_for', 'choice', [
            'label'   => 'Jobs For',
            'choices' => ['vocalist' => 'Vocalists',
                'producer'           => 'Producers', ],
            'expanded' => true,
            'multiple' => true,
        ]);

        $builder->add(
            'gender',
            'choice',
            [
                'attr'        => ['class' => 'select2'],
                'label'       => 'Gender',
                'empty_value' => 'Choose a gender',
                'choices'     => [
                    'male'   => 'Male',
                    'female' => 'Female', ],
            ]
        );

        $builder->add('genre', 'entity', ['label' => 'GENRES',
            'class'                               => 'VocalizrAppBundle:Genre',
            'multiple'                            => true,
            'property'                            => 'title',
            'empty_value'                         => 'Choose a genre',
            'attr'                                => ['class' => 'select2'],
        ]);

        $builder->add('studio_access', 'checkbox');

        $builder->add('vocal_characteristic', 'entity', [
            'label'         => 'Vocal Characteristics',
            'class'         => 'Vocalizr\AppBundle\Entity\VocalCharacteristic',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('vc')
                                              ->orderBy('vc.title', 'ASC');
            },
            'multiple' => true,
            'attr'     => ['class' => 'select2'],
        ]);
        $builder->add('vocal_style', 'entity', [
            'label'         => 'Vocal Styles',
            'class'         => 'Vocalizr\AppBundle\Entity\VocalStyle',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('vs')
                                              ->orderBy('vs.title', 'ASC');
            },
            'multiple' => true,
            'attr'     => ['class' => 'select2'],
        ]);

        $builder->add('languages', 'entity', [
            'label' => 'Language',
            'attr'  => [
                'class' => 'select2',
            ],
            'multiple' => true,
            'class'    => Language::class,
        ]);

        $choices = ['' => 'Please select'] + $this->budget;
        $builder->add('budget', 'choice', ['label' => 'Budget',
            'attr'                                 => ['class' => 'select2'],
            'preferred_choices'                    => [''],
            'choices'                              => $choices, ]);
    }

    public function getName()
    {
        return 'project_search';
    }
}
