<?php

namespace Vocalizr\AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UserReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('quality_of_work', 'choice', [
            'choices'  => [5 => 5, 4 => 4, 3 => 3, 2 => 2, 1 => 1],
            'required' => true,
        ]);
        $builder->add('communication', 'choice', [
            'choices'  => [5 => 5, 4 => 4, 3 => 3, 2 => 2, 1 => 1],
            'required' => true,
        ]);
        $builder->add('professionalism', 'choice', [
            'choices'  => [5 => 5, 4 => 4, 3 => 3, 2 => 2, 1 => 1],
            'required' => true,
        ]);
        $builder->add('work_with_again', 'choice', [
            'choices'  => [5 => 5, 4 => 4, 3 => 3, 2 => 2, 1 => 1],
            'required' => true,
        ]);
        $builder->add('on_time');
        $builder->add('content', 'textarea');
    }

    public function getDefaultOptions(array $options)
    {
        return [
            'data_class' => 'Vocalizr\AppBundle\Entity\UserReview',
        ];
    }

    public function getName()
    {
        return 'user_review';
    }
}
