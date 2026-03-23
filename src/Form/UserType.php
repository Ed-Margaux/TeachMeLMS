<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Email;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
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
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Active' => 'active',
                    'Disabled' => 'disabled',
                ],
                'required' => true,
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Role',
                'choices' => [
                    'Admin' => 'ROLE_ADMIN',
                    'Staff' => 'ROLE_STAFF',
                ],
                'multiple' => false,
                'expanded' => false,
                'required' => true,
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => $options['is_new'],
                'first_options' => [
                    'label' => 'Password',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'Repeat Password',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'The password fields must match.',
            ]);
        
        // Add data transformer to convert between array and string
        $builder->get('roles')
            ->addModelTransformer(new CallbackTransformer(
                // Transform array to string for form display
                function ($rolesArray) {
                    if (empty($rolesArray) || !is_array($rolesArray)) {
                        return 'ROLE_STAFF';
                    }
                    // Return the first role (excluding ROLE_USER which is always added)
                    $roles = array_filter($rolesArray, function($role) {
                        return $role !== 'ROLE_USER';
                    });
                    return !empty($roles) ? reset($roles) : 'ROLE_STAFF';
                },
                // Transform string to array for entity
                function ($roleString) {
                    if (is_string($roleString) && !empty($roleString)) {
                        return [$roleString];
                    }
                    if (is_array($roleString)) {
                        return $roleString;
                    }
                    return ['ROLE_STAFF'];
                }
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_new' => false,
        ]);
    }
}
