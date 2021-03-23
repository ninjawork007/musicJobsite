<?php

namespace Vocalizr\AppBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Vocalizr\AppBundle\Entity\Country;
use Vocalizr\AppBundle\Repository\CountryRepository;

class UserSearchType extends AbstractType
{
    /**
     * @var bool
     */
    private $lockCertified;

    public function __construct($fees = null, $lockCertified = false)
    {
        $this->fees          = $fees;
        $this->lockCertified = $lockCertified;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('gender', 'choice', ['label' => 'Gender',
            'attr'                                 => ['class' => 'select2'],
            'preferred_choices'                    => [''],
            'choices'                              => ['' => 'Either',
                'f'                                       => 'Female',
                'm'                                       => 'Male', ], ]);

        $builder->add('genre', 'entity', ['label' => 'GENRES',
            'class'                               => 'VocalizrAppBundle:Genre',
            'multiple'                            => true,
            'property'                            => 'title',
            'empty_value'                         => 'Choose a genre',
            'attr'                                => ['class' => 'select2'],
            'query_builder'                       => function (EntityRepository $er) {
                return $er->createQueryBuilder('g')
                                ->orderBy('g.title', 'ASC');
            },
        ]);
        $builder->add('studio_access', 'checkbox');
        $builder->add('audio', 'checkbox');

        $certifiedAttrs = [];
        if ($this->lockCertified) {
            $certifiedAttrs['disabled'] = 'disabled';
        }
        if (!$this->lockCertified) {
            $builder->add('certified', 'checkbox', [
                'attr' => $certifiedAttrs,
            ]);
        }

        $builder->add('vocal_characteristic', 'entity', [
            'label'         => 'Vocal Characteristics',
            'class'         => 'Vocalizr\AppBundle\Entity\VocalCharacteristic',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('vc')
                                ->orderBy('vc.title', 'ASC');
            },
            'multiple' => true,
            'mapped'   => true,
            'attr'     => ['class' => 'select2'],
        ]);
        $builder->add('vocal_style', 'entity', [
            'label'         => 'Vocal Styles',
            'class'         => 'Vocalizr\AppBundle\Entity\VocalStyle',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('vs')
                                ->orderBy('vs.title', 'ASC');
            },
            'multiple' => true,
            'mapped'   => true,
            'attr'     => ['class' => 'select2'],
        ]);
        $builder->add('sounds_like', 'text', [
            'attr' => [
                'class' => 'form-control',
            ],
            'label' => 'SOUNDS LIKE',
        ]);
        $builder->add('username', 'text', [
            'attr' => [
                'class' => 'form-control',
            ],
            'label' => 'USERNAME',
        ]);

        $builder->add('country', 'entity', [
            'class'         => Country::class,
            'property'      => 'title',
            'query_builder' => function (CountryRepository $repo) {
                return $repo->findAllSort();
            },
            'empty_value' => 'Start typing the country...',
            'empty_data'  => null,
            'attr'        => [
                'class' => 'country select2',
            ], ]);

        /* $builder->add('country', 'country', array(
            'preferred_choices' => array('US', 'GB', 'AU', 'CA'),
            'required' => true,
            'empty_value' => 'Choose country',
            'empty_data' => null,
            'attr' => array(
                'class' => 'country select2',
        ))); */

        $builder->add('city', null, [
            'attr' => [
                'class'    => 'geo hide',
                'data-geo' => 'locality',
            ],
            'label' => false,
        ]);

        if ($this->fees) {
            $feeChoices = ['' => 'Please select'] + $this->fees;
            $builder->add('fees', 'choice', ['label' => 'Fee',
                'attr'                               => ['class' => 'select2'],
                'preferred_choices'                  => [''],
                'choices'                            => $feeChoices, ]);
        }

        $builder->add('languages', 'entity', [
            'label' => 'Language',
            'attr'  => [
                'class' => 'select2',
            ],
            'multiple' => true,
            'class'    => 'Vocalizr\AppBundle\Entity\Language',
        ]);
    }

    public function getName()
    {
        return 'user_search';
    }
}
