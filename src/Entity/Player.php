<?php

namespace App\Entity;

use App\Repository\PlayerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerRepository::class)]
class Player
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $username;

    #[ORM\Column(type: 'string', length: 255)]
    private $email;

    #[ORM\Column(type: 'string', length: 255)]
    private $password;

    #[ORM\Column(type: 'date')]
    private $reg_date;

    #[ORM\OneToMany(mappedBy: 'host_id', targetEntity: Game::class)]
    private $hosted_games;

    #[ORM\OneToMany(mappedBy: 'winner_id', targetEntity: Game::class)]
    private $won_games;

    #[ORM\ManyToMany(targetEntity: Game::class, mappedBy: 'players')]
    private $played_games;

    public function __construct()
    {
        $this->hosted_games = new ArrayCollection();
        $this->won_games = new ArrayCollection();
        $this->played_games = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

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

    public function getRegistrationDate(): ?\DateTimeInterface
    {
        return $this->reg_date;
    }

    public function setRegistrationDate(\DateTimeInterface $reg_date): self
    {
        $this->reg_date = $reg_date;

        return $this;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getHostedGames(): Collection
    {
        return $this->hosted_games;
    }

    public function addHostedGame(Game $hosted_game): self
    {
        if (!$this->hosted_games->contains($hosted_game)) {
            $this->hosted_games[] = $hosted_game;
            $hosted_game->setHostId($this);
        }

        return $this;
    }

    public function removeHostedGame(Game $hosted_game): self
    {
        if ($this->hosted_games->removeElement($hosted_game)) {
            // set the owning side to null (unless already changed)
            if ($hosted_game->getHostId() === $this) {
                $hosted_game->setHostId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getWonGames(): Collection
    {
        return $this->won_games;
    }

    public function addWonGame(Game $won_game): self
    {
        if (!$this->won_games->contains($won_game)) {
            $this->won_games[] = $won_game;
            $won_game->setWinnerId($this);
        }

        return $this;
    }

    public function removeWonGame(Game $won_game): self
    {
        if ($this->won_games->removeElement($won_game)) {
            // set the owning side to null (unless already changed)
            if ($won_game->getWinnerId() === $this) {
                $won_game->setWinnerId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getPlayedGames(): Collection
    {
        return $this->played_games;
    }

    public function addPlayedGame(Game $playedGame): self
    {
        if (!$this->played_games->contains($playedGame)) {
            $this->played_games[] = $playedGame;
            $playedGame->addPlayer($this);
        }

        return $this;
    }

    public function removePlayedGame(Game $playedGame): self
    {
        if ($this->played_games->removeElement($playedGame)) {
            $playedGame->removePlayer($this);
        }

        return $this;
    }
}
