<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;

class ResetPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('password', RepeatedType::class, [
            'type'            => PasswordType::class,
            'invalid_message' => 'Password fields must match',
            'options'         => ['attr' => ['class' => 'form-control']],
            'required'        => true,
            'first_options'   => ['label' => 'Password'],
            'second_options'  => ['label' => 'Confirm Password'],
        ]);
    }

    public function getDefaultOptions(array $options)
    {
        return [
            'data_class'        => 'App\Entity\UserInfo',
            'validation_groups' => [
                'password_change',
            ],
        ];
    }

    public function getName()
    {
        return 'user';
    }
}
