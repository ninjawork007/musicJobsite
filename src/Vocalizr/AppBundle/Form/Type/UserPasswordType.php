<?php

namespace Vocalizr\AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UserPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('current_password', 'password', [
            'attr' => [
                'class' => 'form-control',
            ],
        ]);
        $builder->add('password', 'repeated', [
            'type'            => 'password',
            'invalid_message' => 'Password fields must match',
            'options'         => ['attr' => ['class' => 'form-control']],
            'required'        => true,
            'first_options'   => ['label' => 'New Password'],
            'second_options'  => ['label' => 'Confirm Password'],
        ]);
    }

    public function getName()
    {
        return 'user';
    }
}
