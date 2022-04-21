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

    #[ORM\Column(type: 'integer')]
    private $points;

    #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'players')]
    #[ORM\JoinColumn(nullable: false)]
    private $game;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'games_played')]
    #[ORM\JoinColumn(nullable: false)]
    private $player;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $roll_1;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $roll_2;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $bid_1;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $bid_2;

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

    public function setIsTurn(?bool $is_turn): self
    {
        $this->is_turn = $is_turn;

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

    public function getRoll1(): ?int
    {
        return $this->roll_1;
    }

    public function setRoll1(?int $roll_1): self
    {
        $this->roll_1 = $roll_1;

        return $this;
    }

    public function getRoll2(): ?int
    {
        return $this->roll_2;
    }

    public function setRoll2(?int $roll_2): self
    {
        $this->roll_2 = $roll_2;

        return $this;
    }

    public function getBid1(): ?int
    {
        return $this->bid_1;
    }

    public function setBid1(?int $bid_1): self
    {
        $this->bid_1 = $bid_1;

        return $this;
    }

    public function getBid2(): ?int
    {
        return $this->bid_2;
    }

    public function setBid2(?int $bid_2): self
    {
        $this->bid_2 = $bid_2;

        return $this;
    }
}
