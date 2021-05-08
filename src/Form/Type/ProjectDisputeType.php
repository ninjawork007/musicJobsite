<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class ProjectDisputeType extends AbstractType
{
    private $userInfo;

    private $projectBid;

    public function __construct($userInfo, $projectBid)
    {
        $this->userInfo   = $userInfo;
        $this->projectBid = $projectBid;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $amountLabel = "Amount i'll pay";
        $maxAmount   = (($this->projectBid->getAmount() / 100) - 1);
        if ($this->projectBid->getUserInfo()->getId() == $this->userInfo->getId()) {
            $amountLabel = "Amount i'll accept";
            $maxAmount   = ($this->projectBid->getAmount() / 100);
        }
        $builder->add('amount', TextType::class, [
            'label' => $amountLabel,
            'attr'  => [
                'class'       => 'form-control',
                'placeholder' => 'Enter the amount you would be happy with',
            ],
            'constraints' => [
                new NotBlank(),
                new Range([
                    'min' => 0,
                    'max' => $maxAmount, // Don't allow them to enter the same number as bid
                ]),
            ],
        ]);
        $builder->add('reason', null, [
            'attr' => [
                'class'       => 'form-control',
                'placeholder' => 'Please give a detailed reason...',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\ProjectDispute',
        ]);
    }

    public function getName()
    {
        return 'project_dispute';
    }
}
