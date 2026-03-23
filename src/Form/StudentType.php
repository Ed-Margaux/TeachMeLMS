<?php

namespace App\Form;

use App\Entity\Student;
use App\Repository\StudentRepository;
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

class StudentType extends AbstractType
{
    private $studentRepository;

    public function __construct(StudentRepository $studentRepository)
    {
        $this->studentRepository = $studentRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Get existing grades from the database
        $existingGrades = array_unique(array_filter(array_map(function($student) {
            return $student->getGrade();
        }, $this->studentRepository->findAll())));
        sort($existingGrades);

        // Define common grades (Elementary only)
        $commonGrades = [
            'Kindergarten',
            'Grade 1',
            'Grade 2',
            'Grade 3',
            'Grade 4',
            'Grade 5',
            'Grade 6',
        ];

        // Combine and sort all unique grades
        $allGrades = array_unique(array_merge($commonGrades, $existingGrades));
        sort($allGrades);

        $choices = array_combine($allGrades, $allGrades);

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
            ->add('grade', ChoiceType::class, [
                'label' => 'Grade',
                'choices' => $choices,
                'placeholder' => 'Select a grade',
                'required' => false,
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
            'data_class' => Student::class,
        ]);
    }
}
