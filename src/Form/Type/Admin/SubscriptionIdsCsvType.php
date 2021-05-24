<?php

namespace App\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use App\Object\SubscriptionIdsCsvObject;

/**
 * Class PaypalSubscriptionsCsvType
 *
 * @package App\Form\Type\Admin
 */
class SubscriptionIdsCsvType extends AbstractType
{
    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'paypal_sub_csv';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('file', FileType::class, [
                'label' => 'CSV File',
                'attr'  => [
                    'class' => 'form-control',
                ],
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SubscriptionIdsCsvObject::class,
            'attr'       => [
                'class' => 'form-group',
            ],
        ]);
    }
}