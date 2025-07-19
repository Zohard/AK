<?php

namespace App\Command;

use App\Entity\Anime;
use App\Entity\Manga;
use App\Entity\User;
use App\Entity\Critique;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clear-data',
    description: 'Clear all migrated data from the database',
)]
class ClearDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Force deletion without confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');
        
        $io->title('Clear Migration Data');
        
        if (!$force) {
            $confirm = $io->confirm('This will delete ALL users, animes, mangas, and critiques. Are you sure?', false);
            if (!$confirm) {
                $io->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }
        
        try {
            // Clear in order to respect foreign key constraints
            $io->section('Clearing Critiques');
            $this->clearEntity(Critique::class, $io);
            
            $io->section('Clearing Animes');
            $this->clearEntity(Anime::class, $io);
            
            $io->section('Clearing Mangas');
            $this->clearEntity(Manga::class, $io);
            
            $io->section('Clearing Users');
            $this->clearEntity(User::class, $io);
            
            $io->success('All data cleared successfully!');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('Clear failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    private function clearEntity(string $entityClass, SymfonyStyle $io): void
    {
        $repository = $this->entityManager->getRepository($entityClass);
        $entities = $repository->findAll();
        
        if (empty($entities)) {
            $io->text("No {$entityClass} entities to clear");
            return;
        }
        
        $count = count($entities);
        $io->text("Clearing {$count} {$entityClass} entities...");
        
        foreach ($entities as $entity) {
            $this->entityManager->remove($entity);
        }
        
        $this->entityManager->flush();
        $io->success("Cleared {$count} {$entityClass} entities");
    }
}