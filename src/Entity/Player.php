<?php

namespace App\Entity;

use App\Repository\PlayerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: PlayerRepository::class)]

class Player
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private $username;

    #[ORM\Column(type: 'string', length: 255)]
    private $email;

    #[ORM\Column(type: 'string', length: 255)]
    private $password;

    #[ORM\Column(type: 'datetime')]
    private $reg_date;

    #[ORM\OneToMany(mappedBy: 'host', targetEntity: Game::class)]
    private $hosted_games;

    #[ORM\OneToMany(mappedBy: 'winner', targetEntity: Game::class)]
    private $games_won;

    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    private $session_token;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $token_expiration;

    public function __construct()
    {
        $this->hosted_games = new ArrayCollection();
        $this->games_won = new ArrayCollection();
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
    public function getGamesWon(): Collection
    {
        return $this->games_won;
    }

    public function addGamesWon(Game $gamesWon): self
    {
        if (!$this->games_won->contains($gamesWon)) {
            $this->games_won[] = $gamesWon;
            $gamesWon->setWinner($this);
        }

        return $this;
    }

    public function removeGamesWon(Game $gamesWon): self
    {
        if ($this->games_won->removeElement($gamesWon)) {
            // set the owning side to null (unless already changed)
            if ($gamesWon->getWinner() === $this) {
                $gamesWon->setWinner(null);
            }
        }

        return $this;
    }

    public function getSessionToken(): ?string
    {
        return $this->session_token;
    }
    
    public function setSessionToken(string $session_token): self
    {
        $this->session_token = $session_token;

        return $this;
    }

    public function getTokenExpiration(): ?\DateTimeInterface
    {
        return $this->token_expiration;
    }

    public function setTokenExpiration(\DateTimeInterface $token_expiration): self
    {
        $this->token_expiration = $token_expiration;

        return $this;
    }
}
