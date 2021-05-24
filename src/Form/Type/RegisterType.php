<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\Type As FieldType;

use App\Entity\UserInfo;

/**
 * Class RegisterType
 *
 * @package App\Form\Type
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
            ->add('first_name', FieldType\TextType::class, [
                'label' => 'FIRST NAME',
                'attr'  => [
                    'class' => 'form-control',
                ],
            ])
            ->add('last_name', FieldType\TextType::class, [
                'label' => 'LAST NAME',
                'attr'  => [
                    'class' => 'form-control',
                ],
            ])
            ->add('username', FieldType\TextType::class, [
                'label' => 'USERNAME <small>(this can not be changed)</small><br>',
                'attr'  => [
                    'class' => 'form-control',
                ],
            ])
            ->add('password', FieldType\RepeatedType::class, [
                'type'            => FieldType\PasswordType::class,
                'invalid_message' => 'Password fields must match',
                'options'         => ['attr' => ['class' => 'form-control']],
                'required'        => true,
                'first_options'   => ['label' => 'PASSWORD'],
                'second_options'  => ['label' => 'CONFIRM PASSWORD'],
            ])
            ->add('email', FieldType\EmailType::class , [
                'label' => 'EMAIL ADDRESS',
                'attr'  => [
                    'class' => 'form-control',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
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
