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
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 50)]
    private ?string $contenu = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 20)]
    private ?int $note = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateModification = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $approuve = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $visible = true;

    #[ORM\Column(type: Types::INTEGER)]
    private int $votesPositifs = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $votesNegatifs = 0;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $type = 'anime'; // anime, manga, ost, jeu

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'critiques')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Anime::class, inversedBy: 'critiques')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Anime $anime = null;

    #[ORM\ManyToOne(targetEntity: Manga::class, inversedBy: 'critiques')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Manga $manga = null;

    // Additional fields for specific content types
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $animeId = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $mangaId = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $ostId = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $jeuId = null;

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
        return $this->approuve;
    }

    public function setApprouve(bool $approuve): static
    {
        $this->approuve = $approuve;
        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;
        return $this;
    }

    public function getVotesPositifs(): int
    {
        return $this->votesPositifs;
    }

    public function setVotesPositifs(int $votesPositifs): static
    {
        $this->votesPositifs = $votesPositifs;
        return $this;
    }

    public function getVotesNegatifs(): int
    {
        return $this->votesNegatifs;
    }

    public function setVotesNegatifs(int $votesNegatifs): static
    {
        $this->votesNegatifs = $votesNegatifs;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
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

    public function getScoreTotal(): int
    {
        return $this->votesPositifs - $this->votesNegatifs;
    }

    public function getNoteFormatted(): string
    {
        return number_format($this->note, 1) . '/20';
    }

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