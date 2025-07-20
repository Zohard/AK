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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:migrate-data',
    description: 'Migrate data from legacy database to new Symfony application',
)]
class MigrateDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Data Migration from Legacy Database');
        
        try {
            // Create legacy database connection
            $legacyDb = $this->createLegacyConnection();
            
            // Migrate in order due to dependencies
            $this->migrateUsers($legacyDb, $io);
            $this->migrateAnimes($legacyDb, $io);
            $this->migrateMangas($legacyDb, $io);
            $this->migrateCritiques($legacyDb, $io);
            
            $io->success('Data migration completed successfully!');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('Migration failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    private function createLegacyConnection(): \PDO
    {
        // Use direct connection for migration
        $host = '127.0.0.1';
        $dbname = 'animekunnet';
        $username = 'animekunnet';
        $password = 'animekun77';
        
        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        return new \PDO($dsn, $username, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ]);
    }
    
    private function migrateUsers(\PDO $legacyDb, SymfonyStyle $io): void
    {
        $io->section('Migrating Users');
        
        // Try multiple possible table structures
        $possibleQueries = [
            // SMF Forum structure
            "SELECT member_name as username, email_address as email, passwd as legacy_password,
                    date_registered, is_activated, real_name
             FROM smf_members 
             WHERE is_activated = 1",
             
            // Standard users table
            "SELECT username, email, password as legacy_password,
                    created_at as date_registered, active as is_activated, 
                    CONCAT(first_name, ' ', last_name) as real_name
             FROM users 
             WHERE active = 1",
             
            // Anime-kun specific structure
            "SELECT pseudo as username, email, mot_de_passe as legacy_password,
                    date_inscription as date_registered, actif as is_activated,
                    CONCAT(prenom, ' ', nom) as real_name
             FROM ak_membres 
             WHERE actif = 1"
        ];
        
        $stmt = null;
        foreach ($possibleQueries as $query) {
            try {
                $stmt = $legacyDb->query($query);
                $io->success("Found users table with query: " . substr($query, 0, 50) . "...");
                break;
            } catch (\Exception $e) {
                $io->text("Query failed: " . substr($query, 0, 50) . "... - " . $e->getMessage());
                continue;
            }
        }
        
        if (!$stmt) {
            $io->error("Could not find a compatible users table structure");
            return;
        }
        
        $count = 0;
        $skipped = 0;
        while ($row = $stmt->fetch()) {
            // Check if user already exists by username
            $existingUser = $this->entityManager->getRepository(User::class)
                ->findOneBy(['username' => $row['username']]);
            
            if ($existingUser) {
                $skipped++;
                continue;
            }
            
            // Also check email (handle potential duplicates in source data)
            $existingUserByEmail = $this->entityManager->getRepository(User::class)
                ->findOneBy(['email' => $row['email']]);
            
            if ($existingUserByEmail) {
                $skipped++;
                continue;
            }
            
            // Skip if email is empty or invalid
            if (empty($row['email']) || !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }
            
            try {
                $user = new User();
                $user->setUsername($row['username']);
                $user->setEmail($row['email']);
                
                // Handle legacy password - you might want to force password reset
                $user->setLegacyPasswordHash($row['legacy_password']);
                $user->setPassword($this->passwordHasher->hashPassword($user, 'temp_' . uniqid()));
                
                // Handle different date formats
                if (is_numeric($row['date_registered'])) {
                    $user->setDateInscription(new \DateTime('@' . $row['date_registered']));
                } else {
                    $user->setDateInscription(new \DateTime($row['date_registered']));
                }
                
                $user->setActif($row['is_activated'] == 1);
                
                // Parse real name if available
                if (!empty($row['real_name'])) {
                    $names = explode(' ', trim($row['real_name']), 2);
                    $user->setPrenom($names[0]);
                    if (isset($names[1])) {
                        $user->setNom($names[1]);
                    }
                }
                
                $this->entityManager->persist($user);
                $count++;
                
                if ($count % 50 === 0) {
                    $this->entityManager->flush();
                    $io->text("Processed {$count} items... (skipped {$skipped} duplicates)");
                }
            } catch (\Exception $e) {
                $skipped++;
                $io->text("Skipped user '{$row['username']}': " . $e->getMessage());
                continue;
            }
        }
        
        try {
            $this->entityManager->flush();
            $io->success("Migrated {$count} users (skipped {$skipped} duplicates)");
        } catch (\Exception $e) {
            $io->warning("Final flush had issues: " . $e->getMessage());
            $io->success("Migrated {$count} users (skipped {$skipped} duplicates) - some records may not have been saved");
        }
    }
    
    private function migrateAnimes(\PDO $legacyDb, SymfonyStyle $io): void
    {
        $io->section('Migrating Animes');
        
        // Try multiple possible table structures for animes
        $possibleQueries = [
            // Standard ak_animes structure
            "SELECT titre, titre_original, synopsis, annee, nb_episodes, 
                    statut, genre, image, note_generale, nb_votes,
                    nice_url, date_ajout, actif, visible, studio, realisateur
             FROM ak_animes 
             WHERE actif = 1",
             
            // Alternative anime table structure
            "SELECT title as titre, original_title as titre_original, description as synopsis, 
                    year as annee, episodes as nb_episodes, status as statut, 
                    genre, image, rating as note_generale, vote_count as nb_votes,
                    slug as nice_url, created_at as date_ajout, active as actif, 
                    visible, studio, director as realisateur
             FROM animes 
             WHERE active = 1",
             
            // Simplified structure
            "SELECT name as titre, original_name as titre_original, summary as synopsis,
                    year as annee, episode_count as nb_episodes, status as statut,
                    genres as genre, poster as image, avg_rating as note_generale,
                    rating_count as nb_votes, slug as nice_url, created_date as date_ajout,
                    is_active as actif, is_visible as visible, studio, director as realisateur
             FROM anime_list 
             WHERE is_active = 1"
        ];
        
        $stmt = null;
        foreach ($possibleQueries as $query) {
            try {
                $stmt = $legacyDb->query($query);
                $io->success("Found animes table with structure matching query");
                break;
            } catch (\Exception $e) {
                $io->text("Anime query failed: " . $e->getMessage());
                continue;
            }
        }
        
        if (!$stmt) {
            $io->warning("Could not find a compatible animes table structure");
            return;
        }
        
        $count = 0;
        $skipped = 0;
        while ($row = $stmt->fetch()) {
            try {
                // Skip if title is empty
                if (empty($row['titre'])) {
                    $skipped++;
                    continue;
                }
                
                $anime = new Anime();
                $anime->setTitre($row['titre'] ?? '');
                $anime->setTitreOriginal($row['titre_original'] ?? null);
                $anime->setSynopsis($row['synopsis'] ?? null);
                $anime->setAnnee($row['annee'] ?? null);
                $anime->setNbEpisodes($row['nb_episodes'] ?? null);
                $anime->setStatut($row['statut'] ?? null);
                $anime->setGenre($row['genre'] ?? null);
                // Use anim_img path for better quality images instead of 120x140 thumbnails
                $imagePath = $row['image'] ?? null;
                if ($imagePath) {
                    // If it's currently pointing to 120x140 thumbnails, update to use anim_img
                    if (strpos($imagePath, '120x140/') !== false) {
                        $imagePath = str_replace('120x140/', '', $imagePath);
                        $imagePath = '/uploads/anime/' . basename($imagePath);
                    } elseif (strpos($imagePath, 'anim_img/') !== false) {
                        $imagePath = '/uploads/anime/' . basename($imagePath);
                    } else {
                        // Assume it's just a filename and point to our uploads directory
                        $imagePath = '/uploads/anime/' . basename($imagePath);
                    }
                }
                $anime->setImage($imagePath);
                $anime->setNoteGenerale($row['note_generale'] ?? null);
                $anime->setNbVotes($row['nb_votes'] ?? 0);
                $anime->setSlug($row['nice_url'] ?? null);
                $anime->setStudio($row['studio'] ?? null);
                $anime->setRealisateur($row['realisateur'] ?? null);
                
                // Handle date
                try {
                    $anime->setDateAjout(new \DateTime($row['date_ajout']));
                } catch (\Exception $e) {
                    $anime->setDateAjout(new \DateTime());
                }
                
                $anime->setActif(($row['actif'] ?? 1) == 1);
                $anime->setVisible(($row['visible'] ?? 1) == 1);
                
                $this->entityManager->persist($anime);
                $count++;
                
                if ($count % 50 === 0) {
                    $this->entityManager->flush();
                    $io->text("Processed {$count} items... (skipped {$skipped})");
                }
            } catch (\Exception $e) {
                $skipped++;
                $io->text("Skipped anime: " . $e->getMessage());
                continue;
            }
        }
        
        try {
            $this->entityManager->flush();
            $io->success("Migrated {$count} animes (skipped {$skipped})");
        } catch (\Exception $e) {
            $io->warning("Final anime flush had issues: " . $e->getMessage());
            $io->success("Migrated {$count} animes (skipped {$skipped}) - some records may not have been saved");
        }
    }
    
    private function migrateMangas(\PDO $legacyDb, SymfonyStyle $io): void
    {
        $io->section('Migrating Mangas');
        
        try {
            // Use actual ak_mangas table structure based on database examination
            $stmt = $legacyDb->query("
                SELECT titre, titre_orig, titre_fr, titres_alternatifs, auteur, annee, origine,
                       licence, nb_volumes, nb_vol, statut_vol, synopsis, image, editeur, isbn,
                       precisions, tags, nb_clics, nb_clics_day, nb_clics_week, nb_clics_month,
                       nb_reviews, LABEL, MoyenneNotes, lienforum, statut, fiche_complete,
                       date_ajout, date_modification, latest_cache, classement_popularite,
                       variation_popularite, nice_url
                FROM ak_mangas 
                WHERE statut = 1
            ");
        } catch (\Exception $e) {
            $io->warning("Could not find ak_mangas table: " . $e->getMessage());
            return;
        }
        
        $count = 0;
        $skipped = 0;
        while ($row = $stmt->fetch()) {
            try {
                // Skip if title is empty
                if (empty($row['titre'])) {
                    $skipped++;
                    continue;
                }
                
                $manga = new Manga();
                $manga->setTitre($row['titre']);
                $manga->setTitreOriginal($row['titre_orig']);
                $manga->setTitreFrancais($row['titre_fr']);
                $manga->setTitresAlternatifs($row['titres_alternatifs']);
                $manga->setAuteur($row['auteur']);
                $manga->setAnnee($row['annee']);
                $manga->setOrigine($row['origine']);
                $manga->setLicence((int)($row['licence'] ?? 0));
                $manga->setNbVolumes($row['nb_volumes']);
                $manga->setNbVol((int)($row['nb_vol'] ?? 0));
                $manga->setStatutVol($row['statut_vol']);
                $manga->setSynopsis($row['synopsis']);
                
                // Handle manga images - use manga_img directory for better quality
                $imagePath = $row['image'] ?? null;
                if ($imagePath) {
                    // If it's currently pointing to 120x140 thumbnails, update to use manga_img
                    if (strpos($imagePath, '120x140/') !== false) {
                        $imagePath = str_replace('120x140/', '', $imagePath);
                        $imagePath = '/uploads/manga/' . basename($imagePath);
                    } elseif (strpos($imagePath, 'manga_img/') !== false) {
                        $imagePath = '/uploads/manga/' . basename($imagePath);
                    } else {
                        // Assume it's just a filename and point to our uploads directory
                        $imagePath = '/uploads/manga/' . basename($imagePath);
                    }
                }
                $manga->setImage($imagePath);
                
                $manga->setEditeur($row['editeur']);
                $manga->setIsbn($row['isbn']);
                $manga->setPrecisions($row['precisions']);
                $manga->setTags($row['tags'] ?? '');
                $manga->setNbClics((int)($row['nb_clics'] ?? 0));
                $manga->setNbClicsDay($row['nb_clics_day']);
                $manga->setNbClicsWeek($row['nb_clics_week']);
                $manga->setNbClicsMonth($row['nb_clics_month']);
                $manga->setNbReviews((int)($row['nb_reviews'] ?? 0));
                $manga->setLabel((int)($row['LABEL'] ?? 0));
                $manga->setMoyenneNotes((float)($row['MoyenneNotes'] ?? 0.0));
                $manga->setLienForum((int)($row['lienforum'] ?? 0));
                $manga->setStatut((int)($row['statut'] ?? 1));
                $manga->setFicheComplete($row['fiche_complete'] === '1' || $row['fiche_complete'] === 1);
                $manga->setDateModification($row['date_modification']);
                $manga->setLatestCache($row['latest_cache']);
                $manga->setClassementPopularite($row['classement_popularite']);
                $manga->setVariationPopularite($row['variation_popularite'] ?? '');
                $manga->setSlug($row['nice_url']);
                
                // Handle date_ajout
                try {
                    if (!empty($row['date_ajout'])) {
                        $manga->setDateAjout(new \DateTime($row['date_ajout']));
                    } else {
                        $manga->setDateAjout(new \DateTime());
                    }
                } catch (\Exception $e) {
                    $manga->setDateAjout(new \DateTime());
                }
                
                $this->entityManager->persist($manga);
                $count++;
                
                if ($count % 50 === 0) {
                    $this->entityManager->flush();
                    $io->text("Processed {$count} items... (skipped {$skipped})");
                }
            } catch (\Exception $e) {
                $skipped++;
                $io->text("Skipped manga '{$row['titre']}': " . $e->getMessage());
                continue;
            }
        }
        
        try {
            $this->entityManager->flush();
            $io->success("Migrated {$count} mangas (skipped {$skipped})");
        } catch (\Exception $e) {
            $io->warning("Final manga flush had issues: " . $e->getMessage());
            $io->success("Migrated {$count} mangas (skipped {$skipped}) - some records may not have been saved");
        }
    }
    
    private function migrateCritiques(\PDO $legacyDb, SymfonyStyle $io): void
    {
        $io->section('Migrating Critiques');
        
        try {
            // You'll need to join with users table to get the user entity
            $stmt = $legacyDb->query("
                SELECT c.titre, c.contenu, c.note, c.date_creation, c.approuve,
                       c.visible, c.votes_positifs, c.votes_negatifs, c.type,
                       c.anime_id, c.manga_id, u.member_name as username
                FROM ak_critiques c
                JOIN smf_members u ON c.user_id = u.id_member
                WHERE c.visible = 1 AND c.approuve = 1
            ");
        } catch (\Exception $e) {
            $io->warning("Could not find critiques table or join failed: " . $e->getMessage());
            return;
        }
        
        $count = 0;
        $skipped = 0;
        while ($row = $stmt->fetch()) {
            try {
                // Skip if title is empty
                if (empty($row['titre'])) {
                    $skipped++;
                    continue;
                }
                
                // Find the migrated user
                $user = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['username' => $row['username']]);
                
                if (!$user) {
                    $skipped++;
                    continue; // Skip if user not found
                }
                
                $critique = new Critique();
                $critique->setTitre($row['titre']);
                $critique->setContenu($row['contenu']);
                $critique->setNote($row['note']);
                
                try {
                    $critique->setDateCreation(new \DateTime($row['date_creation']));
                } catch (\Exception $e) {
                    $critique->setDateCreation(new \DateTime());
                }
                
                $critique->setApprouve($row['approuve'] == 1);
                $critique->setVisible($row['visible'] == 1);
                $critique->setVotesPositifs($row['votes_positifs']);
                $critique->setVotesNegatifs($row['votes_negatifs']);
                $critique->setType($row['type']);
                $critique->setUser($user);
                
                // Link to anime or manga if IDs are provided
                if ($row['anime_id']) {
                    $anime = $this->entityManager->getRepository(Anime::class)->find($row['anime_id']);
                    if ($anime) {
                        $critique->setAnime($anime);
                    }
                }
                
                if ($row['manga_id']) {
                    $manga = $this->entityManager->getRepository(Manga::class)->find($row['manga_id']);
                    if ($manga) {
                        $critique->setManga($manga);
                    }
                }
                
                $this->entityManager->persist($critique);
                $count++;
                
                if ($count % 50 === 0) {
                    $this->entityManager->flush();
                    $io->text("Processed {$count} items... (skipped {$skipped})");
                }
            } catch (\Exception $e) {
                $skipped++;
                $io->text("Skipped critique: " . $e->getMessage());
                continue;
            }
        }
        
        try {
            $this->entityManager->flush();
            $io->success("Migrated {$count} critiques (skipped {$skipped})");
        } catch (\Exception $e) {
            $io->warning("Final critique flush had issues: " . $e->getMessage());
            $io->success("Migrated {$count} critiques (skipped {$skipped}) - some records may not have been saved");
        }
    }
}