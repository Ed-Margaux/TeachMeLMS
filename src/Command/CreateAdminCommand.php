<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create or update admin user account',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = 'marga@test.com';
        $password = 'm111';

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setFirstName('Admin');
            $user->setLastName('User');
            $io->info('Creating new admin user...');
        } else {
            $io->info('Updating existing admin user...');
        }

        // Always ensure admin role and active status
        $user->setStatus('active');
        $user->setRoles(['ROLE_ADMIN']);

        // Hash and set password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success([
            'Admin user created/updated successfully!',
            '',
            'Email: ' . $email,
            'Password: ' . $password,
        ]);

        return Command::SUCCESS;
    }
}
