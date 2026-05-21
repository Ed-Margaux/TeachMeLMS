<?php

namespace App\Form;

use App\Entity\ClassSession;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClassSessionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Session title',
            ])
            ->add('scheduledAt', DateTimeType::class, [
                'label' => 'Scheduled at',
                'widget' => 'single_text',
            ])
            ->add('durationMinutes', IntegerType::class, [
                'label' => 'Duration (minutes)',
                'empty_data' => '45',
            ])
            ->add('meetingUrl', UrlType::class, [
                'label' => 'Online meeting link (Zoom / Google Meet)',
                'required' => false,
                'default_protocol' => 'https',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ClassSession::class,
        ]);
    }
}
