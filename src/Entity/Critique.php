<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: "App\Repository\CritiqueRepository")]
#[ORM\Table(name: "ak_critique")]
#[ORM\HasLifecycleCallbacks]
class Critique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_critique', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $titre = null;

    #[ORM\Column(name: 'critique', type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 50)]
    private ?string $contenu = null;

    #[ORM\Column(name: 'notation', type: Types::INTEGER)]
    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 20)]
    private ?int $note = null;

    #[ORM\Column(name: 'date_critique', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateCreation = null;

    // Legacy fields that exist in the database but aren't part of our new model
    #[ORM\Column(type: Types::INTEGER)]
    private int $statut = 0;

    // Computed properties for new model compatibility
    private ?\DateTimeInterface $dateModification = null;
    private bool $approuve = false;
    private bool $visible = true;
    private int $votesPositifs = 0;
    private int $votesNegatifs = 0;
    private string $type = 'anime';

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'critiques')]
    #[ORM\JoinColumn(name: 'id_membre', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Anime::class, inversedBy: 'critiques')]
    #[ORM\JoinColumn(name: 'id_anime', referencedColumnName: 'id_anime', nullable: true)]
    private ?Anime $anime = null;

    #[ORM\ManyToOne(targetEntity: Manga::class, inversedBy: 'critiques')]
    #[ORM\JoinColumn(name: 'id_manga', referencedColumnName: 'id_manga', nullable: true)]
    private ?Manga $manga = null;

    // Legacy ID fields that still exist in the database
    #[ORM\Column(name: 'id_ost', type: Types::INTEGER)]
    private int $ostId = 0;

    #[ORM\Column(name: 'id_jeu', type: Types::INTEGER)]
    private int $jeuId = 0;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;
        return $this;
    }

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(int $note): static
    {
        $this->note = $note;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->dateModification;
    }

    public function setDateModification(?\DateTimeInterface $dateModification): static
    {
        $this->dateModification = $dateModification;
        return $this;
    }

    public function isApprouve(): bool
    {
        return $this->statut === 1;
    }

    public function setApprouve(bool $approuve): static
    {
        $this->statut = $approuve ? 1 : 0;
        return $this;
    }

    public function isVisible(): bool
    {
        return $this->statut === 1; // Same logic as approved
    }

    public function setVisible(bool $visible): static
    {
        return $this; // Legacy system doesn't have separate visibility
    }

    public function getVotesPositifs(): int
    {
        return 0; // Not implemented in legacy system
    }

    public function setVotesPositifs(int $votesPositifs): static
    {
        return $this;
    }

    public function getVotesNegatifs(): int
    {
        return 0; // Not implemented in legacy system
    }

    public function setVotesNegatifs(int $votesNegatifs): static
    {
        return $this;
    }

    public function getType(): string
    {
        // Determine type based on which ID fields are set
        if ($this->anime) return 'anime';
        if ($this->manga) return 'manga';
        if ($this->ostId > 0) return 'ost';
        if ($this->jeuId > 0) return 'jeu';
        return 'anime';
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getAnime(): ?Anime
    {
        return $this->anime;
    }

    public function setAnime(?Anime $anime): static
    {
        $this->anime = $anime;
        return $this;
    }

    public function getManga(): ?Manga
    {
        return $this->manga;
    }

    public function setManga(?Manga $manga): static
    {
        $this->manga = $manga;
        return $this;
    }

    public function getAnimeId(): ?int
    {
        return $this->animeId;
    }

    public function setAnimeId(?int $animeId): static
    {
        $this->animeId = $animeId;
        return $this;
    }

    public function getMangaId(): ?int
    {
        return $this->mangaId;
    }

    public function setMangaId(?int $mangaId): static
    {
        $this->mangaId = $mangaId;
        return $this;
    }

    public function getOstId(): ?int
    {
        return $this->ostId;
    }

    public function setOstId(?int $ostId): static
    {
        $this->ostId = $ostId;
        return $this;
    }

    public function getJeuId(): ?int
    {
        return $this->jeuId;
    }

    public function setJeuId(?int $jeuId): static
    {
        $this->jeuId = $jeuId;
        return $this;
    }

    public function getStatut(): int
    {
        return $this->statut;
    }

    public function setStatut(int $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getScoreTotal(): int
    {
        return $this->votesPositifs - $this->votesNegatifs;
    }

    public function getNoteFormatted(): string
    {
        return number_format($this->note, 1) . '/20';
    }

    // Duplicate methods removed - using updated versions above

    #[ORM\PreUpdate]
    public function setDateModificationValue(): void
    {
        $this->dateModification = new \DateTime();
    }

    public function __toString(): string
    {
        return $this->titre ?? '';
    }
}