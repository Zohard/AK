<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:analyze-legacy-db',
    description: 'Analyze the structure of the legacy database',
)]
class AnalyzeLegacyDbCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Legacy Database Analysis');
        
        try {
            // Create legacy database connection
            $legacyDb = $this->createLegacyConnection();
            
            // Get all tables
            $tables = $this->getTables($legacyDb);
            $io->section('Available Tables');
            $io->listing($tables);
            
            // Analyze key tables
            $keyTables = ['ak_animes', 'ak_mangas', 'ak_critiques', 'smf_members'];
            
            foreach ($keyTables as $table) {
                if (in_array($table, $tables)) {
                    $this->analyzeTable($legacyDb, $table, $io);
                } else {
                    $io->warning("Table '{$table}' not found - checking similar names...");
                    $this->findSimilarTables($tables, $table, $io);
                }
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('Analysis failed: ' . $e->getMessage());
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
    
    private function getTables(\PDO $db): array
    {
        $stmt = $db->query("SHOW TABLES");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
    
    private function analyzeTable(\PDO $db, string $tableName, SymfonyStyle $io): void
    {
        $io->section("Table: {$tableName}");
        
        // Get table structure
        $stmt = $db->query("DESCRIBE {$tableName}");
        $columns = $stmt->fetchAll();
        
        $tableData = [];
        foreach ($columns as $column) {
            $tableData[] = [
                $column['Field'],
                $column['Type'],
                $column['Null'],
                $column['Key'],
                $column['Default'],
                $column['Extra']
            ];
        }
        
        $io->table(
            ['Column', 'Type', 'Null', 'Key', 'Default', 'Extra'],
            $tableData
        );
        
        // Get row count
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM {$tableName}");
            $count = $stmt->fetch()['count'];
            $io->text("Total rows: {$count}");
        } catch (\Exception $e) {
            $io->text("Could not get row count: " . $e->getMessage());
        }
        
        // Sample data for first 3 rows
        try {
            $stmt = $db->query("SELECT * FROM {$tableName} LIMIT 3");
            $samples = $stmt->fetchAll();
            if ($samples) {
                $io->text("Sample data (first 3 rows):");
                foreach ($samples as $i => $row) {
                    $io->text("Row " . ($i + 1) . ":");
                    foreach ($row as $col => $val) {
                        $displayVal = strlen($val) > 50 ? substr($val, 0, 50) . '...' : $val;
                        $io->text("  {$col}: {$displayVal}");
                    }
                    $io->newLine();
                }
            }
        } catch (\Exception $e) {
            $io->text("Could not get sample data: " . $e->getMessage());
        }
        
        $io->newLine();
    }
    
    private function findSimilarTables(array $tables, string $searchTable, SymfonyStyle $io): void
    {
        $searchBase = str_replace(['ak_', 'smf_'], '', strtolower($searchTable));
        $similar = [];
        
        foreach ($tables as $table) {
            $tableBase = str_replace(['ak_', 'smf_'], '', strtolower($table));
            if (strpos($tableBase, $searchBase) !== false || strpos($searchBase, $tableBase) !== false) {
                $similar[] = $table;
            }
        }
        
        if ($similar) {
            $io->text("Similar tables found:");
            $io->listing($similar);
        } else {
            $io->text("No similar tables found for '{$searchTable}'");
        }
    }
}