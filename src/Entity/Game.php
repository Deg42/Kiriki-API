<?php

namespace App\Entity;

use App\Repository\GameRepository;
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

    #[ORM\Column(type: 'date')]
    private $date;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'hosted_games')]
    #[ORM\JoinColumn(nullable: false)]
    private $host;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'games_won')]
    private $winner;

    #[ORM\OneToOne(targetEntity: PlayerGame::class, cascade: ['persist', 'remove'])]
    private $players;

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

    public function getPlayers(): ?PlayerGame
    {
        return $this->players;
    }

    public function setPlayers(PlayerGame $players): self
    {
        // set the owning side of the relation if necessary
        if ($players->getGame() !== $this) {
            $players->setGame($this);
        }

        $this->players = $players;

        return $this;
    }    
}
