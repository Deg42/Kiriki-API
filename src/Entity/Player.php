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

    #[ORM\OneToMany(mappedBy: 'host', targetEntity: Game::class)]
    private $hosted_games;

    #[ORM\OneToMany(mappedBy: 'winner', targetEntity: Game::class)]
    private $won_games;

    #[ORM\OneToOne(mappedBy: 'player', targetEntity: PlayerGame::class, cascade: ['persist', 'remove'])]
    private $games;

    public function __construct()
    {
        $this->hosted_games = new ArrayCollection();
        $this->won_games = new ArrayCollection();
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

    public function getRegDate(): ?\DateTimeInterface
    {
        return $this->reg_date;
    }

    public function setRegDate(\DateTimeInterface $reg_date): self
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

    public function addHostedGame(Game $hostedGame): self
    {
        if (!$this->hosted_games->contains($hostedGame)) {
            $this->hosted_games[] = $hostedGame;
            $hostedGame->setHost($this);
        }

        return $this;
    }

    public function removeHostedGame(Game $hostedGame): self
    {
        if ($this->hosted_games->removeElement($hostedGame)) {
            // set the owning side to null (unless already changed)
            if ($hostedGame->getHost() === $this) {
                $hostedGame->setHost(null);
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

    public function addWonGame(Game $wonGame): self
    {
        if (!$this->won_games->contains($wonGame)) {
            $this->won_games[] = $wonGame;
            $wonGame->setWinner($this);
        }

        return $this;
    }

    public function removeWonGame(Game $wonGame): self
    {
        if ($this->won_games->removeElement($wonGame)) {
            // set the owning side to null (unless already changed)
            if ($wonGame->getWinner() === $this) {
                $wonGame->setWinner(null);
            }
        }

        return $this;
    }

    public function getGames(): ?PlayerGame
    {
        return $this->games;
    }

    public function setGames(PlayerGame $games): self
    {
        // set the owning side of the relation if necessary
        if ($games->getPlayer() !== $this) {
            $games->setPlayer($this);
        }

        $this->games = $games;

        return $this;
    }
}
