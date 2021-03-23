<?php


namespace Vocalizr\AppBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UserCertifiedType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('userName', null, [
            'attr' => [
                'class' => 'form-control',
            ],
            'label' => 'USERNAME'
        ]);

        $builder->add('spotifyOrAppleMusicLink', null, [
            'attr' => [
                'class' => 'form-control',
            ],
            'label' => 'SPOTIFY OR APPLE MUSIC LINK'
        ]);

        $builder->add('soundcloudLink', null, [
            'attr' => [
                'class' => 'form-control bio',
            ],
            'label' => 'SOUNDCLOUD LINK'
        ]);

        $builder->add('facebookPage', null, [
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
