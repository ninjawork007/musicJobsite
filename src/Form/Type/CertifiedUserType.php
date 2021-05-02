<?php


namespace App\Form\Type;


use Doctrine\DBAL\Types\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CertifiedUserType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', null, [
            'attr' => [
                'class' => 'form-control',
            ],
            'label' => 'USERNAME'
        ]);

        $builder->add('spotify', null, [
            'attr' => [
                'class' => 'form-control',
            ],
            'label' => 'SPOTIFY OR APPLE MUSIC LINK'
        ]);

        $builder->add('soundcloud', null, [
            'attr' => [
                'class' => 'form-control bio',
            ],
            'label' => 'SOUNDCLOUD LINK'
        ]);

        $builder->add('facebook', null, [
            'attr' => [
                'class' => 'form-control bio',
            ],
            'label' => 'FACEBOOK PAGE'
        ]);
    }

    public function getName()
    {
        return 'certifiedUser';
    }
}
