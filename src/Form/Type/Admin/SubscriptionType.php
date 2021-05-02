<?php

namespace App\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use App\Entity\UserSubscription;

class SubscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('paypalAccount', 'email', [
                'label'    => 'PayPal Email',
                'required' => false,
                'attr'     => [
                    'class' => 'form-group'
                ]
            ])
            ->add('paypalSubscrId', null, [
                'label'    => 'PayPal Subscription ID',
                'required' => false,
            ])
            ->add('stripeSubscrId', null, [
                'label'    => 'Stripe Subscription ID',
                'required' => false,
            ])
            ->add('dateEnded', 'date', [
                'label'    => 'Expiration Date',
                'required' => true,
                'data' => new \DateTime('+1 month')
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserSubscription::class,
            'attr'       => [
                'class' => 'form-group',
            ],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'user_subscription';
    }
}