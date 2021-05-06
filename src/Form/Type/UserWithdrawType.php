<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;

class UserWithdrawType extends AbstractType
{
    public $userInfo = null;


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if(isset($options['userInfo'])) {
            $this->userInfo = $options['userInfo'];
        }

        $builder->add('paypal_email', EmailType::class, [
            'mapped' => false,
            'constraints' => [new Email([
                    'message' => 'The email "{{ value }}" is not a valid email.'
                ])
            ],
            'attr' => [
                'class' => 'form-control',
            ],
        ]);
        $builder->add('amount', TextType::class, [
            'attr' => [
                'class' => 'form-control',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\UserWithdraw',
        ]);

        $resolver->setRequired('userInfo');
    }

    public function getName()
    {
        return 'user_withdraw';
    }
}
