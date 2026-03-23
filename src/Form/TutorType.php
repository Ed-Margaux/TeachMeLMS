<?php

namespace App\Form;

use App\Entity\Tutor;
use App\Repository\TutorRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Email;

class TutorType extends AbstractType
{
    public function __construct(
        private readonly TutorRepository $tutorRepository
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Get existing specialties from database
        $allTutors = $this->tutorRepository->findAll();
        $existingSpecialties = array_unique(array_filter(array_map(function($tutor) {
            return $tutor->getSpecialty();
        }, $allTutors)));
        
        // Common language learning specialties
        $commonSpecialties = [
            'Basic Grammar' => 'Basic Grammar',
            'Conversational English' => 'Conversational English',
            'Business English' => 'Business English',
            'Academic Writing' => 'Academic Writing',
            'IELTS Preparation' => 'IELTS Preparation',
            'TOEFL Preparation' => 'TOEFL Preparation',
            'Pronunciation' => 'Pronunciation',
            'Reading Comprehension' => 'Reading Comprehension',
            'Vocabulary Building' => 'Vocabulary Building',
            'English for Kids' => 'English for Kids',
            'English for Beginners' => 'English for Beginners',
            'Advanced English' => 'Advanced English',
        ];
        
        // Merge existing specialties with common ones
        $specialtyChoices = [];
        foreach ($commonSpecialties as $key => $value) {
            $specialtyChoices[$value] = $value;
        }
        foreach ($existingSpecialties as $specialty) {
            if (!isset($specialtyChoices[$specialty])) {
                $specialtyChoices[$specialty] = $specialty;
            }
        }
        ksort($specialtyChoices);
        
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
                'required' => true,
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'constraints' => [
                    new Email([
                        'message' => 'Please enter a valid email address.',
                    ]),
                    new Regex([
                        'pattern' => '/@.*\.com$/i',
                        'message' => 'Email must contain @ symbol and end with .com',
                    ]),
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Phone',
                'required' => false,
                'constraints' => [
                    new Regex([
                        'pattern' => '/^09\d{9}$/',
                        'message' => 'Phone number must be 11 digits and start with 09 (e.g., 09123456789)',
                    ]),
                ],
                'attr' => [
                    'placeholder' => '09123456789',
                    'maxlength' => 11,
                ],
            ])
            ->add('specialty', ChoiceType::class, [
                'label' => 'Specialty',
                'required' => false,
                'choices' => $specialtyChoices,
                'placeholder' => 'Select a specialty',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Profile Picture',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tutor::class,
        ]);
    }
}
