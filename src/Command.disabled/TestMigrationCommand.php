<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-migration',
    description: 'Test the migration by analyzing data without importing',
)]
class TestMigrationCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be migrated without actually doing it');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        
        $io->title('Migration Test - ' . ($dryRun ? 'DRY RUN' : 'PREVIEW'));
        
        try {
            // Create legacy database connection
            $legacyDb = $this->createLegacyConnection();
            $io->success('Connected to legacy database successfully');
            
            // Test each migration step
            $this->testUsersMigration($legacyDb, $io);
            $this->testAnimesMigration($legacyDb, $io);
            $this->testMangasMigration($legacyDb, $io);
            $this->testCritiquesMigration($legacyDb, $io);
            
            $io->success('Migration test completed successfully!');
            $io->note('Run with --dry-run to see detailed preview, or run app:migrate-data to perform actual migration');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('Migration test failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    private function createLegacyConnection(): \PDO
    {
        $host = $_ENV['LEGACY_DB_HOST'] ?? '127.0.0.1';
        $dbname = $_ENV['LEGACY_DB_NAME'] ?? 'animekunnet';
        $username = $_ENV['LEGACY_DB_USER'] ?? 'animekunnet';
        $password = $_ENV['LEGACY_DB_PASS'] ?? 'animekun77';
        
        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        return new \PDO($dsn, $username, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ]);
    }
    
    private function testUsersMigration(\PDO $legacyDb, SymfonyStyle $io): void
    {
        $io->section('Testing Users Migration');
        
        $possibleQueries = [
            "SELECT member_name as username, email_address as email, passwd as legacy_password,
                    date_registered, is_activated, real_name
             FROM smf_members 
             WHERE is_activated = 1 LIMIT 5",
             
            "SELECT username, email, password as legacy_password,
                    created_at as date_registered, active as is_activated, 
                    CONCAT(first_name, ' ', last_name) as real_name
             FROM users 
             WHERE active = 1 LIMIT 5",
             
            "SELECT pseudo as username, email, mot_de_passe as legacy_password,
                    date_inscription as date_registered, actif as is_activated,
                    CONCAT(prenom, ' ', nom) as real_name
             FROM ak_membres 
             WHERE actif = 1 LIMIT 5"
        ];
        
        $found = false;
        foreach ($possibleQueries as $i => $query) {
            try {
                $stmt = $legacyDb->query($query);
                $rows = $stmt->fetchAll();
                
                $io->success("✓ Found users table (query #" . ($i + 1) . ")");
                $io->text("Sample users found: " . count($rows));
                
                if (!empty($rows)) {
                    $io->table(
                        ['Username', 'Email', 'Has Password', 'Registration Date'],
                        array_map(function($row) {
                            return [
                                $row['username'],
                                $row['email'],
                                $row['legacy_password'] ? 'Yes' : 'No',
                                is_numeric($row['date_registered']) ? 
                                    date('Y-m-d', $row['date_registered']) : 
                                    $row['date_registered']
                            ];
                        }, array_slice($rows, 0, 3))
                    );
                }
                
                // Get total count
                $countQuery = str_replace(['LIMIT 5', 'SELECT member_name as username, email_address as email, passwd as legacy_password,
                    date_registered, is_activated, real_name'], ['', 'SELECT COUNT(*) as total'], $query);
                $countQuery = preg_replace('/SELECT .* FROM/', 'SELECT COUNT(*) as total FROM', $query);
                $countQuery = str_replace('LIMIT 5', '', $countQuery);
                
                $countStmt = $legacyDb->query($countQuery);
                $total = $countStmt->fetch()['total'];
                $io->text("Total users to migrate: {$total}");
                
                $found = true;
                break;
            } catch (\Exception $e) {
                $io->text("✗ Query #" . ($i + 1) . " failed: " . $e->getMessage());
            }
        }
        
        if (!$found) {
            $io->error("No compatible users table found");
        }
    }
    
    private function testAnimesMigration(\PDO $legacyDb, SymfonyStyle $io): void
    {
        $io->section('Testing Animes Migration');
        
        $possibleQueries = [
            "SELECT titre, titre_original, synopsis, annee, nb_episodes 
             FROM ak_animes WHERE actif = 1 LIMIT 5",
             
            "SELECT title as titre, original_title as titre_original, description as synopsis, 
                    year as annee, episodes as nb_episodes
             FROM animes WHERE active = 1 LIMIT 5",
             
            "SELECT name as titre, original_name as titre_original, summary as synopsis,
                    year as annee, episode_count as nb_episodes
             FROM anime_list WHERE is_active = 1 LIMIT 5"
        ];
        
        $this->testTableMigration($legacyDb, $io, $possibleQueries, 'animes', ['titre', 'annee', 'nb_episodes']);
    }
    
    private function testMangasMigration(\PDO $legacyDb, SymfonyStyle $io): void
    {
        $io->section('Testing Mangas Migration');
        
        $possibleQueries = [
            "SELECT titre, titre_original, synopsis, annee, nb_volumes, nb_chapitres 
             FROM ak_mangas WHERE actif = 1 LIMIT 5",
             
            "SELECT title as titre, original_title as titre_original, description as synopsis, 
                    year as annee, volumes as nb_volumes, chapters as nb_chapitres
             FROM mangas WHERE active = 1 LIMIT 5"
        ];
        
        $this->testTableMigration($legacyDb, $io, $possibleQueries, 'mangas', ['titre', 'annee', 'nb_volumes']);
    }
    
    private function testCritiquesMigration(\PDO $legacyDb, SymfonyStyle $io): void
    {
        $io->section('Testing Critiques Migration');
        
        $possibleQueries = [
            "SELECT titre, contenu, note, date_creation 
             FROM ak_critiques WHERE visible = 1 LIMIT 5",
             
            "SELECT title as titre, content as contenu, rating as note, created_at as date_creation
             FROM reviews WHERE active = 1 LIMIT 5"
        ];
        
        $this->testTableMigration($legacyDb, $io, $possibleQueries, 'critiques', ['titre', 'note', 'date_creation']);
    }
    
    private function testTableMigration(\PDO $legacyDb, SymfonyStyle $io, array $queries, string $tableName, array $displayColumns): void
    {
        $found = false;
        foreach ($queries as $i => $query) {
            try {
                $stmt = $legacyDb->query($query);
                $rows = $stmt->fetchAll();
                
                $io->success("✓ Found {$tableName} table (query #" . ($i + 1) . ")");
                $io->text("Sample {$tableName} found: " . count($rows));
                
                if (!empty($rows)) {
                    $tableData = [];
                    foreach (array_slice($rows, 0, 3) as $row) {
                        $rowData = [];
                        foreach ($displayColumns as $col) {
                            $rowData[] = $row[$col] ?? 'N/A';
                        }
                        $tableData[] = $rowData;
                    }
                    $io->table($displayColumns, $tableData);
                }
                
                // Get total count
                $countQuery = preg_replace('/SELECT .* FROM/', 'SELECT COUNT(*) as total FROM', $query);
                $countQuery = str_replace('LIMIT 5', '', $countQuery);
                
                $countStmt = $legacyDb->query($countQuery);
                $total = $countStmt->fetch()['total'];
                $io->text("Total {$tableName} to migrate: {$total}");
                
                $found = true;
                break;
            } catch (\Exception $e) {
                $io->text("✗ Query #" . ($i + 1) . " failed: " . $e->getMessage());
            }
        }
        
        if (!$found) {
            $io->warning("No compatible {$tableName} table found");
        }
    }
}