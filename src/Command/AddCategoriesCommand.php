<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:add-categories',
    description: 'Add default categories for elementary English learning',
)]
class AddCategoriesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CategoryRepository $categoryRepository,
        private UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get first admin user or first user as creator
        $adminUser = $this->userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$adminUser) {
            $adminUser = $this->userRepository->findOneBy([]);
        }

        // Categories for elementary/kids English learning
        $categories = [
            [
                'name' => 'Alphabet & Phonics',
                'description' => 'Learn letter recognition, sounds, and basic phonics for young learners',
            ],
            [
                'name' => 'Basic Vocabulary',
                'description' => 'Essential English words for everyday communication and daily life',
            ],
            [
                'name' => 'Numbers & Counting',
                'description' => 'Learning numbers 1-100, counting, and basic math vocabulary',
            ],
            [
                'name' => 'Colors & Shapes',
                'description' => 'Basic colors and geometric shapes in English',
            ],
            [
                'name' => 'Animals',
                'description' => 'Farm animals, wild animals, pets, and animal-related vocabulary',
            ],
            [
                'name' => 'Family & Friends',
                'description' => 'Family members, relationships, and friendship vocabulary',
            ],
            [
                'name' => 'Food & Drinks',
                'description' => 'Common foods, beverages, and meal-related vocabulary',
            ],
            [
                'name' => 'Body Parts',
                'description' => 'Parts of the body and health-related vocabulary',
            ],
            [
                'name' => 'Simple Sentences',
                'description' => 'Basic sentence structure and simple grammar for beginners',
            ],
            [
                'name' => 'Greetings & Manners',
                'description' => 'Polite expressions, greetings, and social interaction phrases',
            ],
            [
                'name' => 'Action Words (Verbs)',
                'description' => 'Common action verbs and verb usage for kids',
            ],
            [
                'name' => 'Reading Comprehension',
                'description' => 'Understanding written text and developing reading skills',
            ],
            [
                'name' => 'Games & Activities',
                'description' => 'Interactive learning games to make English fun and engaging',
            ],
            [
                'name' => 'Songs & Rhymes',
                'description' => 'English songs, nursery rhymes, and musical learning activities',
            ],
        ];

        $added = 0;
        $skipped = 0;

        foreach ($categories as $categoryData) {
            // Check if category already exists
            $existing = $this->categoryRepository->findOneBy(['name' => $categoryData['name']]);
            
            if ($existing) {
                $io->note("Category '{$categoryData['name']}' already exists. Skipping...");
                $skipped++;
                continue;
            }

            $category = new Category();
            $category->setName($categoryData['name']);
            $category->setDescription($categoryData['description']);
            
            if ($adminUser instanceof User) {
                $category->setCreatedBy($adminUser);
            }

            $this->entityManager->persist($category);
            $added++;
        }

        $this->entityManager->flush();

        $io->success([
            "Successfully added {$added} new categories!",
            $skipped > 0 ? "Skipped {$skipped} existing categories." : '',
        ]);

        return Command::SUCCESS;
    }
}






