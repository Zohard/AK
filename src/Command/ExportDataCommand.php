<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PDO;
use Exception;

class ExportDataCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('app:export-data')
            ->setDescription('Export anime and manga data to JSON files for web interface');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // Connect to database using PDO with older auth method
            $dsn = 'mysql:host=127.0.0.1;dbname=animekunnet;charset=utf8mb4';
            $options = [
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))"
            ];
            $pdo = new PDO($dsn, 'animekunnet', 'animekun77', $options);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $output->writeln('Connected to database successfully!');

            // First, let's check what tables and columns exist
            $output->writeln('Checking database structure...');
            
            // Show tables
            $tablesStmt = $pdo->query("SHOW TABLES");
            $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
            $output->writeln('Available tables: ' . implode(', ', $tables));
            
            // Check ak_animes structure if it exists
            if (in_array('ak_animes', $tables)) {
                $animeCols = $pdo->query("DESCRIBE ak_animes");
                $animeColumns = $animeCols->fetchAll(PDO::FETCH_COLUMN);
                $output->writeln('ak_animes columns: ' . implode(', ', $animeColumns));
                
                // Try different column names for active status
                $animeCountQuery = "SELECT COUNT(*) FROM ak_animes";
                if (in_array('actif', $animeColumns)) {
                    $animeCountQuery .= " WHERE actif = 1";
                } elseif (in_array('active', $animeColumns)) {
                    $animeCountQuery .= " WHERE active = 1";
                } elseif (in_array('status', $animeColumns)) {
                    $animeCountQuery .= " WHERE status = 1";
                }
                
                $animeCountStmt = $pdo->query($animeCountQuery);
                $animeCount = $animeCountStmt->fetchColumn();
            } else {
                $animeCount = 0;
            }

            // Check ak_mangas structure if it exists
            if (in_array('ak_mangas', $tables)) {
                $mangaCols = $pdo->query("DESCRIBE ak_mangas");
                $mangaColumns = $mangaCols->fetchAll(PDO::FETCH_COLUMN);
                $output->writeln('ak_mangas columns: ' . implode(', ', $mangaColumns));
                
                // Try different column names for status
                $mangaCountQuery = "SELECT COUNT(*) FROM ak_mangas";
                if (in_array('statut', $mangaColumns)) {
                    $mangaCountQuery .= " WHERE statut = 1";
                } elseif (in_array('status', $mangaColumns)) {
                    $mangaCountQuery .= " WHERE status = 1";
                } elseif (in_array('active', $mangaColumns)) {
                    $mangaCountQuery .= " WHERE active = 1";
                }
                
                $mangaCountStmt = $pdo->query($mangaCountQuery);
                $mangaCount = $mangaCountStmt->fetchColumn();
            } else {
                $mangaCount = 0;
            }

            // Check critiques structure if it exists
            $critiqueCount = 0;
            if (in_array('ak_critique', $tables)) {
                $critiqueCols = $pdo->query("DESCRIBE ak_critique");
                $critiqueColumns = $critiqueCols->fetchAll(PDO::FETCH_COLUMN);
                $output->writeln('ak_critique columns: ' . implode(', ', $critiqueColumns));
                
                $critiqueCountStmt = $pdo->query("SELECT COUNT(*) FROM ak_critique");
                $critiqueCount = $critiqueCountStmt->fetchColumn();
                $output->writeln("Found {$critiqueCount} critiques");
                
                // Get sample critique data
                $sampleCritiqueStmt = $pdo->query("SELECT * FROM ak_critique LIMIT 3");
                $sampleCritiques = $sampleCritiqueStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($sampleCritiques)) {
                    $output->writeln('Sample critique data:');
                    foreach ($sampleCritiques as $sample) {
                        $output->writeln(json_encode($sample, JSON_UNESCAPED_UNICODE));
                    }
                }
            }

            $output->writeln("Found {$animeCount} active anime, {$mangaCount} active manga, and {$critiqueCount} critiques");

            // Get sample data for each table that exists
            $topAnimes = [];
            $topMangas = [];
            $animes = [];
            $mangas = [];
            $critiques = [];
            $recentCritiques = [];
            
            if (in_array('ak_animes', $tables)) {
                $output->writeln('Getting anime data...');
                
                // Get a few sample rows first to see actual data
                $sampleStmt = $pdo->query("SELECT * FROM ak_animes LIMIT 3");
                $samples = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($samples)) {
                    $output->writeln('Sample anime data:');
                    foreach ($samples as $sample) {
                        $output->writeln(json_encode($sample, JSON_UNESCAPED_UNICODE));
                    }
                    
                    // Build flexible query based on available columns
                    $availableCols = array_keys($samples[0]);
                    $selectCols = [];
                    
                    // Map common column names
                    $colMap = [
                        'titre' => ['titre', 'title', 'name'],
                        'note_generale' => ['note_generale', 'rating', 'score', 'note'],
                        'slug' => ['slug', 'url', 'nice_url'],
                        'titreOriginal' => ['titreOriginal', 'original_title', 'title_original'],
                        'annee' => ['annee', 'year', 'date_sortie'],
                        'genre' => ['genre', 'genres', 'category'],
                        'synopsis' => ['synopsis', 'description', 'resume'],
                        'nbEpisodes' => ['nbEpisodes', 'episodes', 'nb_episodes'],
                        'statut' => ['statut', 'status', 'state'],
                        'studio' => ['studio', 'producer', 'producteur']
                    ];
                    
                    foreach ($colMap as $key => $possibleCols) {
                        foreach ($possibleCols as $col) {
                            if (in_array($col, $availableCols)) {
                                $selectCols[$key] = $col;
                                break;
                            }
                        }
                    }
                    
                    $selectParts = [];
                    foreach ($selectCols as $alias => $realCol) {
                        $selectParts[] = "{$realCol} as {$alias}";
                    }
                    
                    if (!empty($selectParts)) {
                        $query = "SELECT " . implode(', ', $selectParts) . " FROM ak_animes LIMIT 50";
                        $animesStmt = $pdo->query($query);
                        $animes = $animesStmt->fetchAll(PDO::FETCH_ASSOC);
                        $topAnimes = array_slice($animes, 0, 10);
                    }
                }
            }
            
            if (in_array('ak_mangas', $tables)) {
                $output->writeln('Getting manga data...');
                
                // Get a few sample rows first to see actual data  
                $sampleStmt = $pdo->query("SELECT * FROM ak_mangas LIMIT 3");
                $samples = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($samples)) {
                    $output->writeln('Sample manga data:');
                    foreach ($samples as $sample) {
                        $output->writeln(json_encode($sample, JSON_UNESCAPED_UNICODE));
                    }
                    
                    // Build flexible query based on available columns
                    $availableCols = array_keys($samples[0]);
                    $selectCols = [];
                    
                    // Map common column names for manga
                    $colMap = [
                        'titre' => ['titre', 'title', 'name'],
                        'MoyenneNotes' => ['MoyenneNotes', 'rating', 'score', 'note', 'moyenne'],
                        'nice_url' => ['nice_url', 'slug', 'url'],
                        'auteur' => ['auteur', 'author', 'creator'],
                        'annee' => ['annee', 'year', 'date_sortie'],
                        'tags' => ['tags', 'genre', 'genres', 'category'],
                        'synopsis' => ['synopsis', 'description', 'resume'],
                        'nb_volumes' => ['nb_volumes', 'volumes', 'tomes'],
                        'editeur' => ['editeur', 'publisher', 'editor']
                    ];
                    
                    foreach ($colMap as $key => $possibleCols) {
                        foreach ($possibleCols as $col) {
                            if (in_array($col, $availableCols)) {
                                $selectCols[$key] = $col;
                                break;
                            }
                        }
                    }
                    
                    $selectParts = [];
                    foreach ($selectCols as $alias => $realCol) {
                        $selectParts[] = "{$realCol} as {$alias}";
                    }
                    
                    if (!empty($selectParts)) {
                        $query = "SELECT " . implode(', ', $selectParts) . " FROM ak_mangas LIMIT 50";
                        $mangasStmt = $pdo->query($query);
                        $mangas = $mangasStmt->fetchAll(PDO::FETCH_ASSOC);
                        $topMangas = array_slice($mangas, 0, 10);
                    }
                }
            }
            
            if (in_array('ak_critique', $tables)) {
                $output->writeln('Getting critique data...');
                
                // Get all critiques with basic info - try different sort orders
                $critiqueStmt = $pdo->query("SELECT * FROM ak_critique ORDER BY id_critique ASC LIMIT 100");
                $critiques = $critiqueStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get recent critiques for homepage - using same order for consistency
                $recentCritiqueStmt = $pdo->query("SELECT * FROM ak_critique ORDER BY id_critique ASC LIMIT 10");
                $recentCritiques = $recentCritiqueStmt->fetchAll(PDO::FETCH_ASSOC);
                
                $output->writeln('Found ' . count($critiques) . ' critiques for export');
            }

            // Create data structure
            $data = [
                'animeCount' => $animeCount,
                'mangaCount' => $mangaCount,
                'critiqueCount' => $critiqueCount,
                'topAnimes' => $topAnimes,
                'topMangas' => $topMangas,
                'animes' => $animes,
                'mangas' => $mangas,
                'critiques' => $critiques,
                'recentCritiques' => $recentCritiques,
                'exportTime' => date('Y-m-d H:i:s')
            ];

            // Save to JSON file
            $jsonPath = __DIR__ . '/../../data/exported_data.json';
            
            // Create data directory if it doesn't exist
            $dataDir = dirname($jsonPath);
            if (!is_dir($dataDir)) {
                mkdir($dataDir, 0755, true);
            }

            file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $output->writeln("Data exported successfully to: {$jsonPath}");
            $output->writeln("Top anime: " . count($topAnimes) . " entries");
            $output->writeln("Top manga: " . count($topMangas) . " entries");
            $output->writeln("Sample anime: " . count($animes) . " entries");
            $output->writeln("Sample manga: " . count($mangas) . " entries");
            $output->writeln("Critiques: " . count($critiques) . " entries");
            $output->writeln("Recent critiques: " . count($recentCritiques) . " entries");

            return Command::SUCCESS;

        } catch (Exception $e) {
            $output->writeln("Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}