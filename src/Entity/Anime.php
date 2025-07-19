<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: "App\Repository\AnimeRepository")]
#[ORM\Table(name: "ak_animes")]
#[ORM\HasLifecycleCallbacks]
class Anime
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_anime", type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $titre = null;

    #[ORM\Column(name: "titre_orig", type: Types::TEXT, nullable: true)]
    private ?string $titreOriginal = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $synopsis = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Range(min: 1900, max: 2030)]
    private ?int $annee = null;

    #[ORM\Column(name: "nb_ep", type: Types::INTEGER, nullable: true)]
    #[Assert\Range(min: 1, max: 9999)]
    private ?int $nbEpisodes = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $statut = null;


    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(name: "MoyenneNotes", type: Types::FLOAT, nullable: true)]
    private ?float $noteGenerale = null;

    #[ORM\Column(name: "nb_reviews", type: Types::INTEGER, nullable: true)]
    private ?int $nbVotes = null;

    #[ORM\Column(name: "nice_url", type: Types::STRING, length: 255, nullable: true, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(name: "date_ajout", type: Types::INTEGER)]
    private ?int $dateAjout = null;

    #[ORM\Column(name: "date_modification", type: Types::INTEGER, nullable: true)]
    private ?int $dateModification = null;


    #[ORM\Column(name: "sources", type: Types::STRING, length: 50, nullable: true)]
    private ?string $source = null;


    #[ORM\OneToMany(mappedBy: 'anime', targetEntity: Critique::class, cascade: ['persist'])]
    private Collection $critiques;

    #[ORM\OneToMany(mappedBy: 'anime', targetEntity: AnimeScreenshot::class, cascade: ['persist', 'remove'])]
    private Collection $screenshots;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'animes')]
    #[ORM\JoinTable(name: 'ak_anime_tags')]
    private Collection $tags;

    #[ORM\OneToMany(mappedBy: 'anime', targetEntity: UserAnimeList::class, cascade: ['persist'])]
    private Collection $userLists;

    public function __construct()
    {
        $this->critiques = new ArrayCollection();
        $this->screenshots = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->userLists = new ArrayCollection();
        $this->dateAjout = time();
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

    public function getTitreOriginal(): ?string
    {
        return $this->titreOriginal;
    }

    public function setTitreOriginal(?string $titreOriginal): static
    {
        $this->titreOriginal = $titreOriginal;
        return $this;
    }

    public function getSynopsis(): ?string
    {
        return $this->synopsis;
    }

    public function setSynopsis(?string $synopsis): static
    {
        $this->synopsis = $synopsis;
        return $this;
    }

    public function getAnnee(): ?int
    {
        return $this->annee;
    }

    public function setAnnee(?int $annee): static
    {
        $this->annee = $annee;
        return $this;
    }

    public function getNbEpisodes(): ?int
    {
        return $this->nbEpisodes;
    }

    public function setNbEpisodes(?int $nbEpisodes): static
    {
        $this->nbEpisodes = $nbEpisodes;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }


    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getNoteGenerale(): ?float
    {
        return $this->noteGenerale;
    }

    public function setNoteGenerale(?float $noteGenerale): static
    {
        $this->noteGenerale = $noteGenerale;
        return $this;
    }

    public function getNbVotes(): ?int
    {
        return $this->nbVotes;
    }

    public function setNbVotes(?int $nbVotes): static
    {
        $this->nbVotes = $nbVotes;
        return $this;
    }


    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getDateAjout(): ?\DateTimeInterface
    {
        return $this->dateAjout ? new \DateTime('@' . $this->dateAjout) : null;
    }

    public function setDateAjout(\DateTimeInterface $dateAjout): static
    {
        $this->dateAjout = $dateAjout->getTimestamp();
        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->dateModification ? new \DateTime('@' . $this->dateModification) : null;
    }

    public function setDateModification(?\DateTimeInterface $dateModification): static
    {
        $this->dateModification = $dateModification ? $dateModification->getTimestamp() : null;
        return $this;
    }


    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;
        return $this;
    }


    /**
     * @return Collection<int, Critique>
     */
    public function getCritiques(): Collection
    {
        return $this->critiques;
    }

    public function addCritique(Critique $critique): static
    {
        if (!$this->critiques->contains($critique)) {
            $this->critiques->add($critique);
            $critique->setAnime($this);
        }

        return $this;
    }

    public function removeCritique(Critique $critique): static
    {
        if ($this->critiques->removeElement($critique)) {
            if ($critique->getAnime() === $this) {
                $critique->setAnime(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AnimeScreenshot>
     */
    public function getScreenshots(): Collection
    {
        return $this->screenshots;
    }

    public function addScreenshot(AnimeScreenshot $screenshot): static
    {
        if (!$this->screenshots->contains($screenshot)) {
            $this->screenshots->add($screenshot);
            $screenshot->setAnime($this);
        }

        return $this;
    }

    public function removeScreenshot(AnimeScreenshot $screenshot): static
    {
        if ($this->screenshots->removeElement($screenshot)) {
            if ($screenshot->getAnime() === $this) {
                $screenshot->setAnime(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);
        return $this;
    }

    /**
     * @return Collection<int, UserAnimeList>
     */
    public function getUserLists(): Collection
    {
        return $this->userLists;
    }

    public function addUserList(UserAnimeList $userList): static
    {
        if (!$this->userLists->contains($userList)) {
            $this->userLists->add($userList);
            $userList->setAnime($this);
        }

        return $this;
    }

    public function removeUserList(UserAnimeList $userList): static
    {
        if ($this->userLists->removeElement($userList)) {
            if ($userList->getAnime() === $this) {
                $userList->setAnime(null);
            }
        }

        return $this;
    }

    #[ORM\PreUpdate]
    public function setDateModificationValue(): void
    {
        $this->dateModification = time();
    }

    public function __toString(): string
    {
        return $this->titre ?? '';
    }
}