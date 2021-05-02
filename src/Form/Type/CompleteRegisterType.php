<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CompleteRegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('username');
        $builder->add('is_producer', null, [
            'label'    => 'Producer',
            'required' => false,
        ]);
        $builder->add('is_vocalist', null, [
            'label'    => 'Vocalist',
            'required' => false,
        ]);

        $builder->add('referral_code', 'text', [
            'label' => 'Beta Code',
        ]);

        $builder->add('password', 'password', [
            'label' => 'Vocalizr Password',
        ]);
    }

    public function getDefaultOptions(array $options)
    {
        return [
            'data_class' => 'App\Entity\UserInfo',
        ];
    }

    public function getName()
    {
        return 'user';
    }
}
