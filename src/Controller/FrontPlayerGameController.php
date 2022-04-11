<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Game;
use App\Entity\Player;
use App\Entity\PlayerGame;

class FrontPlayerGameController extends AbstractController
{
    function createGame(ManagerRegistry $doctrine, Request $request)
    {
        $token = $request->get('token');
        $playerId = $request->get('player_id');
        $gameName = $request->get('game_name');
        $gamePass = $request->get('game_password');

        $entityManager = $doctrine->getManager();
        $player = $entityManager->getRepository(Player::class)->find($playerId);

        if (is_null($player)) {
            return new JsonResponse(['error' => 'Player not found'], 404);
        }

        if (is_null($token) || $token != $player->getSessionToken() || $player->getTokenExpiration() < new \DateTime()) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        foreach ($player->getGames() as $game) {
            if ($game->getIsInProgress()) {
                return new JsonResponse(['error' => 'Player is already in a game'], 400);
            }
        }

        $game = new Game();
        $game->setName($gameName);
        $game->setPassword(password_hash($gamePass, PASSWORD_DEFAULT));
        $game->setDate(new \DateTime());
        $game->setHost($player);
        $game->setIsInProgress(false);
        

        $playerInGame = new PlayerGame();
        $playerInGame->setPlayer($player);
        $playerInGame->setGame($game);
        $playerInGame->setTurnOrder(0);
        $playerInGame->setPoints(9);
        $playerInGame->setIsTurn(true);

        $game->addPlayer($playerInGame);

        $entityManager->persist($game);
        $entityManager->persist($playerInGame);
        $entityManager->flush();

        return new JsonResponse(['success' => 'Game created successfully'], 200);
    }

    // function joinGame(ManagerRegistry $doctrine, Request $request)
    // {
    //     $playerId = $request->get('id');
    //     $token = $request->get('token');
    //     $gameName = $request->get('game_name');
    //     $gamePass = $request->get('game_pass');

    //     $entityManager = $doctrine->getManager();
    //     $player = $entityManager->getRepository(Player::class)->find($playerId);
    //     $game = $entityManager->getRepository(Game::class)->findOneBy(['name' => $gameName]);

    //     if (is_null($game)) {
    //         return new JsonResponse(['error' => 'Game not found'], 404);
    //     }

    //     if (is_null($player)) {
    //         return new JsonResponse(['error' => 'Player not found'], 404);
    //     }

    //     if (is_null($token) || $token != $player->getSessionToken() || $player->getTokenExpiration() < new \DateTime()) {
    //         return new JsonResponse(['error' => 'Invalid token'], 401);
    //     }

    //     if (is_null($gamePass) || !password_verify($gamePass, $game->getPassword())) {
    //         return new JsonResponse(['error' => 'Wrong password'], 401);
    //     }

    //     $playerInGame = new PlayerGame();
    //     $playerInGame->setPlayer($player);
    //     $playerInGame->setGame($game);
    //     $playerInGame->setTurnOrder(count($game->getPlayers()));
    //     $playerInGame->setPoints(9);

    //     $entityManager->persist($playerInGame);
    //     $entityManager->flush();

    //     return new JsonResponse(['success' => 'Player joined game successfully'], 200);
    // }
}