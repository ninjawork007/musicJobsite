<?php

namespace App\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use App\Entity\UserSubscription;

class SubscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('paypalAccount', EmailType::class, [
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
            ->add('dateEnded', DateType::class, [
                'label'    => 'Expiration Date',
                'required' => true,
                'data' => new \DateTime('+1 month')
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
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