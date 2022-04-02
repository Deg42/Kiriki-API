<?php

namespace App\Entity;

use App\Repository\PlayerGameRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerGameRepository::class)]
class PlayerGame
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer')]
    private $turn_order;

    #[ORM\Column(type: 'boolean')]
    private $is_turn;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $is_last_accepted;

    #[ORM\Column(type: 'integer')]
    private $points;

    #[ORM\OneToOne(inversedBy: 'players', targetEntity: Game::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private $game;

    #[ORM\OneToOne(inversedBy: 'games', targetEntity: Player::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private $player;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTurnOrder(): ?int
    {
        return $this->turn_order;
    }

    public function setTurnOrder(int $turn_order): self
    {
        $this->turn_order = $turn_order;

        return $this;
    }

    public function getIsTurn(): ?bool
    {
        return $this->is_turn;
    }

    public function setIsTurn(bool $is_turn): self
    {
        $this->is_turn = $is_turn;

        return $this;
    }

    public function getIsLastAccepted(): ?bool
    {
        return $this->is_last_accepted;
    }

    public function setIsLastAccepted(?bool $is_last_accepted): self
    {
        $this->is_last_accepted = $is_last_accepted;

        return $this;
    }

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(int $points): self
    {
        $this->points = $points;

        return $this;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(Game $game): self
    {
        $this->game = $game;

        return $this;
    }

    public function getPlayer(): ?Player
    {
        return $this->player;
    }

    public function setPlayer(Player $player): self
    {
        $this->player = $player;

        return $this;
    }
}
