<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Email;

class UserWithdrawType extends AbstractType
{
    public function __construct($userInfo)
    {
        $this->userInfo = $userInfo;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('paypal_email', 'email', [
            'mapped' => false,
            'constraints' => array(
                new Email(array('checkMX' => true)),
            ),
            'attr' => [
                'class' => 'form-control',
            ],
        ]);
        $builder->add('amount', 'text', [
            'attr' => [
                'class' => 'form-control',
            ],
        ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\UserWithdraw',
        ]);
    }

    public function getName()
    {
        return 'user_withdraw';
    }
}
