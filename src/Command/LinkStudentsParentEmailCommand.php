<?php

namespace App\Command;

use App\Repository\StudentRepository;
use App\Repository\UserRepository;
use App\Service\ParentLearnerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:students:link-parents',
    description: 'Link existing student rows to parent accounts using parent_email field',
)]
final class LinkStudentsParentEmailCommand extends Command
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly ParentLearnerService $parentLearners,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $linked = 0;

        foreach ($this->users->findAll() as $user) {
            $linked += $this->parentLearners->syncWebStudentsToParent($user);
        }

        $io->success(sprintf('Linked %d student record(s) to parent accounts.', $linked));

        return Command::SUCCESS;
    }
}
