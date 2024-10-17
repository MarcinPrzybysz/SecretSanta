<?php

namespace App\Form\Type;

use App\Entity\Party;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SetJoinModeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'join_mode',
                ChoiceType::class,
                [
                    'attr' => ['data-hj-masked' => ''],
                    'choices' => ['party_manage_valid.join_mode.mode.no' => '0', 'party_manage_valid.join_mode.mode.yes' => '1'],
                    'expanded' => true,
                    'multiple' => false,
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Party::class,
        ]);
    }
}
