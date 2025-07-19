<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: "App\Repository\UserRepository")]
#[ORM\Table(name: "ak_users")]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 180)]
    private ?string $username = null;

    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[ORM\Column(type: Types::STRING)]
    private ?string $password = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateInscription = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $derniereConnexion = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $actif = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $emailVerified = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $emailVerificationToken = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $pays = null;

    // Legacy SMF integration fields
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $smfId = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $legacyPasswordHash = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Critique::class, cascade: ['persist'])]
    private Collection $critiques;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserAnimeList::class, cascade: ['persist', 'remove'])]
    private Collection $animeLists;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserMangaList::class, cascade: ['persist', 'remove'])]
    private Collection $mangaLists;

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: TopList::class, cascade: ['persist'])]
    private Collection $topLists;

    public function __construct()
    {
        $this->critiques = new ArrayCollection();
        $this->animeLists = new ArrayCollection();
        $this->mangaLists = new ArrayCollection();
        $this->topLists = new ArrayCollection();
        $this->dateInscription = new \DateTime();
        $this->roles = ['ROLE_USER'];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getFullName(): string
    {
        return trim(($this->prenom ?? '') . ' ' . ($this->nom ?? ''));
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getDateInscription(): ?\DateTimeInterface
    {
        return $this->dateInscription;
    }

    public function setDateInscription(\DateTimeInterface $dateInscription): static
    {
        $this->dateInscription = $dateInscription;
        return $this;
    }

    public function getDerniereConnexion(): ?\DateTimeInterface
    {
        return $this->derniereConnexion;
    }

    public function setDerniereConnexion(?\DateTimeInterface $derniereConnexion): static
    {
        $this->derniereConnexion = $derniereConnexion;
        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;
        return $this;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function setEmailVerified(bool $emailVerified): static
    {
        $this->emailVerified = $emailVerified;
        return $this;
    }

    public function getEmailVerificationToken(): ?string
    {
        return $this->emailVerificationToken;
    }

    public function setEmailVerificationToken(?string $emailVerificationToken): static
    {
        $this->emailVerificationToken = $emailVerificationToken;
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;
        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeInterface $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): static
    {
        $this->ville = $ville;
        return $this;
    }

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(?string $pays): static
    {
        $this->pays = $pays;
        return $this;
    }

    public function getSmfId(): ?int
    {
        return $this->smfId;
    }

    public function setSmfId(?int $smfId): static
    {
        $this->smfId = $smfId;
        return $this;
    }

    public function getLegacyPasswordHash(): ?string
    {
        return $this->legacyPasswordHash;
    }

    public function setLegacyPasswordHash(?string $legacyPasswordHash): static
    {
        $this->legacyPasswordHash = $legacyPasswordHash;
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
            $critique->setUser($this);
        }

        return $this;
    }

    public function removeCritique(Critique $critique): static
    {
        if ($this->critiques->removeElement($critique)) {
            if ($critique->getUser() === $this) {
                $critique->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserAnimeList>
     */
    public function getAnimeLists(): Collection
    {
        return $this->animeLists;
    }

    public function addAnimeList(UserAnimeList $animeList): static
    {
        if (!$this->animeLists->contains($animeList)) {
            $this->animeLists->add($animeList);
            $animeList->setUser($this);
        }

        return $this;
    }

    public function removeAnimeList(UserAnimeList $animeList): static
    {
        if ($this->animeLists->removeElement($animeList)) {
            if ($animeList->getUser() === $this) {
                $animeList->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserMangaList>
     */
    public function getMangaLists(): Collection
    {
        return $this->mangaLists;
    }

    public function addMangaList(UserMangaList $mangaList): static
    {
        if (!$this->mangaLists->contains($mangaList)) {
            $this->mangaLists->add($mangaList);
            $mangaList->setUser($this);
        }

        return $this;
    }

    public function removeMangaList(UserMangaList $mangaList): static
    {
        if ($this->mangaLists->removeElement($mangaList)) {
            if ($mangaList->getUser() === $this) {
                $mangaList->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TopList>
     */
    public function getTopLists(): Collection
    {
        return $this->topLists;
    }

    public function addTopList(TopList $topList): static
    {
        if (!$this->topLists->contains($topList)) {
            $this->topLists->add($topList);
            $topList->setCreatedBy($this);
        }

        return $this;
    }

    public function removeTopList(TopList $topList): static
    {
        if ($this->topLists->removeElement($topList)) {
            if ($topList->getCreatedBy() === $this) {
                $topList->setCreatedBy(null);
            }
        }

        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('ROLE_ADMIN');
    }

    public function isModerator(): bool
    {
        return $this->hasRole('ROLE_MODERATOR');
    }

    public function __toString(): string
    {
        return $this->username ?? '';
    }
}