<?php

namespace App\Form;

use App\Entity\Course;
use App\Repository\CourseRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class CourseType extends AbstractType
{
    public function __construct(
        private readonly CourseRepository $courseRepository
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Get existing levels from database
        $allCourses = $this->courseRepository->findAll();
        $existingLevels = array_unique(array_filter(array_map(function($course) {
            return $course->getLevel();
        }, $allCourses)));
        
        // Common course levels
        $commonLevels = [
            'Beginner' => 'Beginner',
            'Intermediate' => 'Intermediate',
            'Advanced' => 'Advanced',
            'Expert' => 'Expert',
            'All Levels' => 'All Levels',
        ];
        
        // Merge existing levels with common ones
        $levelChoices = [];
        foreach ($commonLevels as $key => $value) {
            $levelChoices[$value] = $value;
        }
        foreach ($existingLevels as $level) {
            if (!isset($levelChoices[$level])) {
                $levelChoices[$level] = $level;
            }
        }
        ksort($levelChoices);
        
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug',
                'required' => false,
            ])
            ->add('level', ChoiceType::class, [
                'label' => 'Level',
                'required' => false,
                'choices' => $levelChoices,
                'placeholder' => 'Select a level',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Course Image',
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
            'data_class' => Course::class,
        ]);
    }
}

