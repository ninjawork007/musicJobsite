<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProjectLyricType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('lyrics');
    }

    public function getDefaultOptions(array $options)
    {
        return [
            'data_class' => 'App\Entity\Project',
            'validation_groups' => ['project_update_lyrics'],
        ];
    }

    public function getName()
    {
        return 'project_lyric';
    }
}