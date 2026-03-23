<?php

namespace App\Form;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MessageType extends AbstractType
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentUser = $options['current_user'];
        
        // Get all users except the current user
        $users = $this->userRepository->createQueryBuilder('u')
            ->where('u.id != :currentUserId')
            ->andWhere('u.status = :status')
            ->setParameter('currentUserId', $currentUser->getId())
            ->setParameter('status', 'active')
            ->orderBy('u.firstName', 'ASC')
            ->addOrderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();

        $builder
            ->add('recipient', EntityType::class, [
                'class' => User::class,
                'choices' => $users,
                'choice_label' => function (User $user) {
                    return $user->getFullName() . ' (' . $user->getEmail() . ')';
                },
                'label' => 'To',
                'required' => true,
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('subject', TextType::class, [
                'label' => 'Subject',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter message subject',
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Message',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 8,
                    'placeholder' => 'Type your message here...',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Message::class,
            'current_user' => null,
        ]);
    }
}






