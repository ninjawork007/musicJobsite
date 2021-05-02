<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use App\Entity\UserProProfile;

/**
 * Class ProProfileType
 * @package App\Form\Type
 */
class ProProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('enabled', 'checkbox', [
                'label'    => 'Switch on Advanced Profile',
                'required' => false,
            ])
            ->add('heroImage', 'hidden')
            ->add('socialLinks', 'text', [
                'label'         => 'Social Links',
                'required'      => false,
                'empty_data'    => '',
                'property_path' => 'socialLinksCommaSeparated',
                'attr' => [
                    'class'       => 'tag-select',
                    'placeholder' => '',
                ],
            ])
            ->add('facebookLink', null, [
                'label' => 'Facebook',
                'required' => false,
            ])
            ->add('soundcloudLink', null, [
                'label' => 'Soundcloud',
                'required' => false,
            ])
            ->add('instagramLink', null, [
                'label' => 'Instagram',
                'required' => false,
            ])
            ->add('spotifyLink', null, [
                'label' => 'Spotify',
                'required' => false,
            ])
            ->add('youtubeLink', null, [
                'label' => 'Youtube',
                'required' => false,
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserProProfile::class,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'pro_profile_type';
    }
}