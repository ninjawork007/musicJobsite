<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class EngineOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', null, [
            'label' => 'TITLE *',
            'attr'  => [
                'class' => 'form-control',
            ],
        ]);
        $builder->add('email', null, [
            'label' => 'CONTACT EMAIL *',
            'attr'  => [
                'class' => 'form-control',
            ],
        ]);
        $builder->add('notes', null, [
            'attr' => [
                'class' => 'form-control',
            ],
        ]);
    }

    public function getDefaultOptions(array $options)
    {
        return [
            'data_class' => 'App\Entity\EngineOrder',
        ];
    }

    public function getName()
    {
        return 'engine';
    }
}
