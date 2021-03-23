<?php

namespace Vocalizr\AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UserEmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('email', null, [
            'attr' => [
                'class' => 'form-control',
            ],
        ]);
    }

    public function getName()
    {
        return 'user';
    }
}
