<?php

namespace App\Entity;

use App\Repository\MangaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: MangaRepository::class)]
#[ORM\Table(name: 'ak_mangas')]
class Manga
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_manga', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'nice_url', type: Types::STRING, length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(name: 'titre', type: Types::TEXT, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(name: 'auteur', type: Types::TEXT, nullable: true)]
    private ?string $auteur = null;

    #[ORM\Column(name: 'annee', type: Types::STRING, length: 4, nullable: true)]
    private ?string $annee = null;

    #[ORM\Column(name: 'origine', type: Types::STRING, length: 255, nullable: true)]
    private ?string $origine = null;

    #[ORM\Column(name: 'titre_orig', type: Types::TEXT, nullable: true)]
    private ?string $titreOriginal = null;

    #[ORM\Column(name: 'titre_fr', type: Types::TEXT, nullable: true)]
    private ?string $titreFrancais = null;

    #[ORM\Column(name: 'titres_alternatifs', type: Types::TEXT, nullable: true)]
    private ?string $titresAlternatifs = null;

    #[ORM\Column(name: 'licence', type: Types::INTEGER, options: ['default' => 0])]
    private int $licence = 0;

    #[ORM\Column(name: 'nb_volumes', type: Types::STRING, length: 255, nullable: true)]
    private ?string $nbVolumes = null;

    #[ORM\Column(name: 'nb_vol', type: Types::INTEGER, options: ['default' => 0])]
    private int $nbVol = 0;

    #[ORM\Column(name: 'statut_vol', type: Types::STRING, length: 11, nullable: true)]
    private ?string $statutVol = null;

    #[ORM\Column(name: 'synopsis', type: Types::TEXT, nullable: true)]
    private ?string $synopsis = null;

    #[ORM\Column(name: 'image', type: Types::STRING, length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(name: 'editeur', type: Types::TEXT, nullable: true)]
    private ?string $editeur = null;

    #[ORM\Column(name: 'isbn', type: Types::STRING, length: 255, nullable: true)]
    private ?string $isbn = null;

    #[ORM\Column(name: 'precisions', type: Types::TEXT, nullable: true)]
    private ?string $precisions = null;

    #[ORM\Column(name: 'tags', type: Types::TEXT)]
    private string $tags = '';

    #[ORM\Column(name: 'nb_clics', type: Types::INTEGER, options: ['default' => 0])]
    private int $nbClics = 0;

    #[ORM\Column(name: 'nb_clics_day', type: Types::INTEGER, nullable: true)]
    private ?int $nbClicsDay = null;

    #[ORM\Column(name: 'nb_clics_week', type: Types::INTEGER, nullable: true)]
    private ?int $nbClicsWeek = null;

    #[ORM\Column(name: 'nb_clics_month', type: Types::INTEGER, nullable: true)]
    private ?int $nbClicsMonth = null;

    #[ORM\Column(name: 'nb_reviews', type: Types::INTEGER, options: ['default' => 0])]
    private int $nbReviews = 0;

    #[ORM\Column(name: 'LABEL', type: Types::INTEGER, options: ['default' => 0])]
    private int $label = 0;

    #[ORM\Column(name: 'MoyenneNotes', type: Types::FLOAT, options: ['default' => 0])]
    private float $moyenneNotes = 0.0;

    #[ORM\Column(name: 'lienforum', type: Types::INTEGER, options: ['default' => 0])]
    private int $lienForum = 0;

    #[ORM\Column(name: 'statut', type: Types::INTEGER, options: ['default' => 1])]
    private int $statut = 1;

    #[ORM\Column(name: 'fiche_complete', type: Types::BOOLEAN, nullable: true)]
    private ?bool $ficheComplete = null;

    #[ORM\Column(name: 'date_ajout', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateAjout = null;

    #[ORM\Column(name: 'date_modification', type: Types::INTEGER, nullable: true)]
    private ?int $dateModificationTimestamp = null;

    #[ORM\Column(name: 'latest_cache', type: Types::INTEGER, nullable: true)]
    private ?int $latestCache = null;

    #[ORM\Column(name: 'classement_popularite', type: Types::INTEGER, nullable: true)]
    private ?int $classementPopularite = null;

    #[ORM\Column(name: 'variation_popularite', type: Types::TEXT)]
    private string $variationPopularite = '';

    #[ORM\OneToMany(mappedBy: 'manga', targetEntity: Critique::class, cascade: ['persist'])]
    private Collection $critiques;

    #[ORM\OneToMany(mappedBy: 'manga', targetEntity: UserMangaList::class, cascade: ['persist'])]
    private Collection $userLists;

    // Computed properties
    private ?float $noteGenerale = null;
    private ?string $genre = null;
    private bool $actif = true;
    private bool $visible = true;

    public function __construct()
    {
        $this->critiques = new ArrayCollection();
        $this->userLists = new ArrayCollection();
        $this->dateAjout = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getAuteur(): ?string
    {
        return $this->auteur;
    }

    public function setAuteur(?string $auteur): static
    {
        $this->auteur = $auteur;
        return $this;
    }

    public function getAnnee(): ?string
    {
        return $this->annee;
    }

    public function setAnnee(?string $annee): static
    {
        $this->annee = $annee;
        return $this;
    }

    public function getOrigine(): ?string
    {
        return $this->origine;
    }

    public function setOrigine(?string $origine): static
    {
        $this->origine = $origine;
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

    public function getTitreFrancais(): ?string
    {
        return $this->titreFrancais;
    }

    public function setTitreFrancais(?string $titreFrancais): static
    {
        $this->titreFrancais = $titreFrancais;
        return $this;
    }

    public function getTitresAlternatifs(): ?string
    {
        return $this->titresAlternatifs;
    }

    public function setTitresAlternatifs(?string $titresAlternatifs): static
    {
        $this->titresAlternatifs = $titresAlternatifs;
        return $this;
    }

    public function getLicence(): int
    {
        return $this->licence;
    }

    public function setLicence(int $licence): static
    {
        $this->licence = $licence;
        return $this;
    }

    public function getNbVolumes(): ?string
    {
        return $this->nbVolumes;
    }

    public function setNbVolumes(?string $nbVolumes): static
    {
        $this->nbVolumes = $nbVolumes;
        return $this;
    }

    public function getNbVol(): int
    {
        return $this->nbVol;
    }

    public function setNbVol(int $nbVol): static
    {
        $this->nbVol = $nbVol;
        return $this;
    }

    public function getStatutVol(): ?string
    {
        return $this->statutVol;
    }

    public function setStatutVol(?string $statutVol): static
    {
        $this->statutVol = $statutVol;
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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getEditeur(): ?string
    {
        return $this->editeur;
    }

    public function setEditeur(?string $editeur): static
    {
        $this->editeur = $editeur;
        return $this;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(?string $isbn): static
    {
        $this->isbn = $isbn;
        return $this;
    }

    public function getPrecisions(): ?string
    {
        return $this->precisions;
    }

    public function setPrecisions(?string $precisions): static
    {
        $this->precisions = $precisions;
        return $this;
    }

    public function getTags(): string
    {
        return $this->tags;
    }

    public function setTags(string $tags): static
    {
        $this->tags = $tags;
        return $this;
    }

    public function getNbClics(): int
    {
        return $this->nbClics;
    }

    public function setNbClics(int $nbClics): static
    {
        $this->nbClics = $nbClics;
        return $this;
    }

    public function getNbClicsDay(): ?int
    {
        return $this->nbClicsDay;
    }

    public function setNbClicsDay(?int $nbClicsDay): static
    {
        $this->nbClicsDay = $nbClicsDay;
        return $this;
    }

    public function getNbClicsWeek(): ?int
    {
        return $this->nbClicsWeek;
    }

    public function setNbClicsWeek(?int $nbClicsWeek): static
    {
        $this->nbClicsWeek = $nbClicsWeek;
        return $this;
    }

    public function getNbClicsMonth(): ?int
    {
        return $this->nbClicsMonth;
    }

    public function setNbClicsMonth(?int $nbClicsMonth): static
    {
        $this->nbClicsMonth = $nbClicsMonth;
        return $this;
    }

    public function getNbReviews(): int
    {
        return $this->nbReviews;
    }

    public function setNbReviews(int $nbReviews): static
    {
        $this->nbReviews = $nbReviews;
        return $this;
    }

    public function getLabel(): int
    {
        return $this->label;
    }

    public function setLabel(int $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function getMoyenneNotes(): float
    {
        return $this->moyenneNotes;
    }

    public function setMoyenneNotes(float $moyenneNotes): static
    {
        $this->moyenneNotes = $moyenneNotes;
        return $this;
    }

    public function getLienForum(): int
    {
        return $this->lienForum;
    }

    public function setLienForum(int $lienForum): static
    {
        $this->lienForum = $lienForum;
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

    public function isFicheComplete(): ?bool
    {
        return $this->ficheComplete;
    }

    public function setFicheComplete(?bool $ficheComplete): static
    {
        $this->ficheComplete = $ficheComplete;
        return $this;
    }

    public function getDateAjout(): ?\DateTimeInterface
    {
        return $this->dateAjout;
    }

    public function setDateAjout(\DateTimeInterface $dateAjout): static
    {
        $this->dateAjout = $dateAjout;
        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        if ($this->dateModificationTimestamp === null) {
            return null;
        }
        
        return new \DateTime('@' . $this->dateModificationTimestamp);
    }

    public function setDateModification(?\DateTimeInterface $dateModification): static
    {
        if ($dateModification === null) {
            $this->dateModificationTimestamp = null;
        } else {
            $this->dateModificationTimestamp = $dateModification->getTimestamp();
        }
        return $this;
    }
    
    public function getDateModificationTimestamp(): ?int
    {
        return $this->dateModificationTimestamp;
    }
    
    public function setDateModificationTimestamp(?int $timestamp): static
    {
        $this->dateModificationTimestamp = $timestamp;
        return $this;
    }

    public function getLatestCache(): ?int
    {
        return $this->latestCache;
    }

    public function setLatestCache(?int $latestCache): static
    {
        $this->latestCache = $latestCache;
        return $this;
    }

    public function getClassementPopularite(): ?int
    {
        return $this->classementPopularite;
    }

    public function setClassementPopularite(?int $classementPopularite): static
    {
        $this->classementPopularite = $classementPopularite;
        return $this;
    }

    public function getVariationPopularite(): string
    {
        return $this->variationPopularite;
    }

    public function setVariationPopularite(string $variationPopularite): static
    {
        $this->variationPopularite = $variationPopularite;
        return $this;
    }

    // Computed properties getters/setters
    public function getNoteGenerale(): ?float
    {
        return $this->noteGenerale ?: $this->moyenneNotes;
    }

    public function setNoteGenerale(?float $noteGenerale): static
    {
        $this->noteGenerale = $noteGenerale;
        return $this;
    }

    public function getGenre(): ?string
    {
        return $this->genre ?: $this->tags;
    }

    public function setGenre(?string $genre): static
    {
        $this->genre = $genre;
        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif && $this->statut === 1;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;
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
            $critique->setManga($this);
        }

        return $this;
    }

    public function removeCritique(Critique $critique): static
    {
        if ($this->critiques->removeElement($critique)) {
            if ($critique->getManga() === $this) {
                $critique->setManga(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserMangaList>
     */
    public function getUserLists(): Collection
    {
        return $this->userLists;
    }

    public function addUserList(UserMangaList $userList): static
    {
        if (!$this->userLists->contains($userList)) {
            $this->userLists->add($userList);
            $userList->setManga($this);
        }

        return $this;
    }

    public function removeUserList(UserMangaList $userList): static
    {
        if ($this->userLists->removeElement($userList)) {
            if ($userList->getManga() === $this) {
                $userList->setManga(null);
            }
        }

        return $this;
    }

    #[ORM\PreUpdate]
    public function setDateModificationValue(): void
    {
        $this->dateModificationTimestamp = time();
    }
}