<?php

namespace Vocalizr\AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Vocalizr\AppBundle\Entity\UserInfo;

/**
 * Class RegisterType
 *
 * @package Vocalizr\AppBundle\Form\Type
 */
class RegisterType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', 'text', [
                'label' => 'USERNAME <small>(this can not be changed)</small><br>',
            ])
            ->add('is_producer', null, [
                'label' => 'Producer',
                'required' => false,
            ])
            ->add('is_vocalist', null, [
                'label'    => 'Vocalist',
                'attr' => ['id' => 'is_vocalist'],
                'required' => false,
            ])
            ->add('password', 'repeated', [
                'type'            => 'password',
                'invalid_message' => 'Password fields must match',
                'options'         => ['attr' => ['class' => 'form-control']],
                'required'        => true,
                'first_options'   => ['label' => 'PASSWORD'],
                'second_options'  => ['label' => 'CONFIRM PASSWORD'],
            ])
            ->add('email', 'email', [
                'label' => 'EMAIL ADDRESS',
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class'        => UserInfo::class,
            'validation_groups' => ['register_step1'],
            'attr'              => [
                'class' => 'voc-form',
            ],
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'user';
    }
}
