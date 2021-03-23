<?php

namespace Vocalizr\AppBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\DataEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;

class UserRateVocalType extends AbstractType
{
    private $rateUserInfo;

    public function __construct($rateUserInfo)
    {
        $this->rateUserInfo = $rateUserInfo;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // grab the user, do a quick sanity check that one exists
        if (!$this->rateUserInfo) {
            throw new \LogicException(
                'The UserRateVocalType cannot be used without an authenticated user!'
            );
        }

        $rateUserInfo = $this->rateUserInfo;

        $factory = $builder->getFormFactory();

        /**
         * Voice Tags
         * Only display voice tags for the user we are rating
         */
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (\Symfony\Component\Form\FormEvent $event) use ($rateUserInfo, $factory) {
                $form = $event->getForm();

                $formOptions = [
                    'class'         => 'Vocalizr\AppBundle\Entity\UserVoiceTag',
                    'multiple'      => true,
                    'expanded'      => true,
                    'property'      => 'name',
                    'query_builder' => function (EntityRepository $er) use ($rateUserInfo) {
                        return $er->createQueryBuilder('uvt')
                                ->select('vt, uvt')
                                ->innerJoin('uvt.voice_tag', 'vt')
                                ->where('uvt.user_info = :userInfoId')
                                ->setParameter(':userInfoId', $rateUserInfo->getId());
                    },
                ];
                $form->add($factory->createNamed('voice_tag', 'entity', null, $formOptions));
            }
        );
        /*
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (DataEvent $event) {
            $data = $event->getData();
            if (null === $data) {
                return;
            }

            $form->get('region')->setData($data->getCity()->getRegion());
        });
         *
         */

        /**
         * Vocal Styles
         * Only display voice styles for the user we are rating
         */
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (\Symfony\Component\Form\FormEvent $event) use ($rateUserInfo, $factory) {
                $form = $event->getForm();

                $formOptions = [
                    'class'         => 'Vocalizr\AppBundle\Entity\VocalStyle',
                    'multiple'      => true,
                    'expanded'      => true,
                    'property'      => 'title',
                    'query_builder' => function (EntityRepository $er) use ($rateUserInfo) {
                        return $er->createQueryBuilder('vs')
                                ->innerJoin('vs.user_vocal_styles', 'uvs')
                                ->where('uvs.user_info = :userInfoId')
                                ->setParameter(':userInfoId', $rateUserInfo->getId());
                    },
                ];
                $form->add($factory->createNamed('vocal_style', 'entity', null, $formOptions));
            }
        );

        /**
         * Vocal Characteristics
         * Only display voice characteristics for the user we are rating
         */
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (\Symfony\Component\Form\FormEvent $event) use ($rateUserInfo, $factory) {
                $form = $event->getForm();

                $formOptions = [
                    'class'         => 'Vocalizr\AppBundle\Entity\VocalCharacteristic',
                    'multiple'      => true,
                    'expanded'      => true,
                    'property'      => 'title',
                    'query_builder' => function (EntityRepository $er) use ($rateUserInfo) {
                        return $er->createQueryBuilder('vc')
                                ->innerJoin('vc.user_vocal_characteristics', 'uvc')
                                ->where('uvc.user_info = :userInfoId')
                                ->setParameter(':userInfoId', $rateUserInfo->getId());
                    },
                ];
                $form->add($factory->createNamed('vocal_characteristic', 'entity', null, $formOptions));
            }
        );
    }

    public function getName()
    {
        return 'user_rate_vocal';
    }
}