-- Example SQL migration script
-- Adjust table names and column mappings to match your legacy database structure

-- First, you need to connect to both databases
-- This is an example of how you could structure the migration

-- 1. Migrate Users (adjust source table name and columns)
INSERT INTO ak_users (username, email, password, date_inscription, actif, email_verified, roles)
SELECT 
    member_name,
    email_address,
    CONCAT('$2y$10$', SUBSTRING(MD5(RAND()), 1, 22), 'temp'), -- Temporary password hash
    FROM_UNIXTIME(date_registered),
    is_activated,
    is_activated,
    '["ROLE_USER"]'
FROM legacy_db.smf_members 
WHERE is_activated = 1;

-- 2. Migrate Animes
INSERT INTO ak_animes (titre, titre_original, synopsis, annee, nb_episodes, statut, genre, image, note_generale, nb_votes, nice_url, date_ajout, actif, visible)
SELECT 
    titre,
    titre_original,
    synopsis,
    annee,
    nb_episodes,
    statut,
    genre,
    image,
    note_generale,
    nb_votes,
    nice_url,
    date_ajout,
    actif,
    visible
FROM legacy_db.ak_animes 
WHERE actif = 1;

-- 3. Migrate Mangas
INSERT INTO ak_mangas (titre, titre_original, synopsis, annee, nb_volumes, nb_chapitres, statut, genre, image, note_generale, nb_votes, nice_url, date_ajout, actif, visible, auteur, editeur)
SELECT 
    titre,
    titre_original,
    synopsis,
    annee,
    nb_volumes,
    nb_chapitres,
    statut,
    genre,
    image,
    note_generale,
    nb_votes,
    nice_url,
    date_ajout,
    actif,
    visible,
    auteur,
    editeur
FROM legacy_db.ak_mangas 
WHERE actif = 1;

-- 4. Migrate Critiques (with user mapping)
INSERT INTO ak_critique (titre, contenu, note, date_creation, approuve, visible, votes_positifs, votes_negatifs, type, user_id, anime_id, manga_id)
SELECT 
    c.titre,
    c.contenu,
    c.note,
    c.date_creation,
    c.approuve,
    c.visible,
    c.votes_positifs,
    c.votes_negatifs,
    c.type,
    u.id, -- Map to new user ID
    c.anime_id,
    c.manga_id
FROM legacy_db.ak_critiques c
JOIN legacy_db.smf_members sm ON c.user_id = sm.id_member
JOIN ak_users u ON u.username = sm.member_name
WHERE c.visible = 1 AND c.approuve = 1;