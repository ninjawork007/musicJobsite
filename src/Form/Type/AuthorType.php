<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class AuthorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', null, [
            'attr' => [
                'class' => 'form-control',
            ],
        ]);
        $builder->add('email', null, [
            'attr' => [
                'class' => 'form-control',
            ],
        ]);

        $builder->add('bio', null, [
            'attr' => [
                'class' => 'form-control bio',
            ],
        ]);
    }

    public function getName()
    {
        return 'author';
    }
}