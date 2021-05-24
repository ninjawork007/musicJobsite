<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ProjectBidType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('amount');
    }

    public function getDefaultOptions(array $options)
    {
        return [
            'data_class' => 'App\Entity\ProjectBid',
        ];
    }

    public function getName()
    {
        return 'project_bid';
    }
}