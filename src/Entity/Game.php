<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $name;

    #[ORM\Column(type: 'string', length: 255)]
    private $password;

    #[ORM\Column(type: 'datetime')]
    private $date;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'hosted_games')]
    #[ORM\JoinColumn(nullable: false)]
    private $host;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'games_won')]
    private $winner;

    #[ORM\OneToMany(mappedBy: 'game', targetEntity: PlayerGame::class, orphanRemoval: true, cascade: ['persist'])]
    private $players;

    #[ORM\Column(type: 'boolean')]
    private $is_in_progress;



    public function __construct()
    {
        $this->player = new ArrayCollection();
        $this->players = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getHost(): ?Player
    {
        return $this->host;
    }

    public function setHost(?Player $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function getWinner(): ?Player
    {
        return $this->winner;
    }

    public function setWinner(?Player $winner): self
    {
        $this->winner = $winner;

        return $this;
    }

    /**
     * @return Collection<int, PlayerGame>
     */
    public function getPlayers(): Collection
    {
        return $this->players;
    }

    public function addPlayer(PlayerGame $player): self
    {
        if (!$this->players->contains($player)) {
            $this->players[] = $player;
            $player->setGame($this);
        }

        return $this;
    }

    public function removePlayer(PlayerGame $player): self
    {
        if ($this->players->removeElement($player)) {
            // set the owning side to null (unless already changed)
            if ($player->getGame() === $this) {
                $player->setGame(null);
            }
        }

        return $this;
    }

    public function getIsInProgress(): ?bool
    {
        return $this->is_in_progress;
    }

    public function setIsInProgress(bool $is_in_progress): self
    {
        $this->is_in_progress = $is_in_progress;

        return $this;
    }


}
