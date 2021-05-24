<?php

namespace Vocalizr\AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Vocalizr\AppBundle\Entity\Project;

class PublishType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('to_favorites')
            ->add('publish_type', 'choice', [
                'label'   => 'PUBLISHING OPTIONS',
                'choices' => [
                    Project::PUBLISH_PUBLIC  => ucwords(Project::PUBLISH_PUBLIC),
                    Project::PUBLISH_PRIVATE => ucwords(Project::PUBLISH_PRIVATE),
                ],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('restrict_to_preferences')
            ->add('highlight')
            ->add('featured')
            ->add('messaging')
            ->add('lock_to_cert', null, [
            'property_path' => 'pro_required'
            ])
            ->add('upgrade_to_pro', 'hidden', [
                'mapped' => false,
                'attr'   => [
                    'class'  => 'js-upgrade-to-pro-check',
                ],
            ])
        ;
    }

    public function getDefaultOptions(array $options)
    {
        return [
            'data_class' => Project::class,
        ];
    }

    public function getName()
    {
        return 'project';
    }
}