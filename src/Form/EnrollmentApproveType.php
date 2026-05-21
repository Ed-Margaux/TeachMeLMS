<?php

namespace App\Form;

use App\Entity\Tutor;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class EnrollmentApproveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tutor', EntityType::class, [
                'class' => Tutor::class,
                'choice_label' => 'fullName',
                'label' => 'Assign tutor',
                'placeholder' => 'Select tutor',
                'constraints' => [new NotBlank()],
            ])
            ->add('staffNote', TextareaType::class, [
                'label' => 'Note to parent (optional)',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
