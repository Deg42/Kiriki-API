<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Player;
use App\Entity\Game;
use App\Entity\PlayerGame;

class FrontGameController extends AbstractController
{

    function rollDices(ManagerRegistry $doctrine, Request $request)
    {
        $playerId = $request->get('player_id');
        $gameId = $request->get('game_id');
        $token = $request->get('token');

        $entityManager = $doctrine->getManager();
        $player = $entityManager->getRepository(Player::class)->find($playerId);
        $game = $entityManager->getRepository(Game::class)->find($gameId);
        $playerInGame = $entityManager->getRepository(PlayerGame::class)->findOneBy(['player' => $player, 'game' => $game]);
        $nextPlayerInGame = $entityManager->getRepository(PlayerGame::class)->findOneBy(['game' => $game, 'turn_order' => $playerInGame->getTurnOrder() + 1]);
        if(is_null($nextPlayerInGame)){
            $nextPlayerInGame = $entityManager->getRepository(PlayerGame::class)->findOneBy(['game' => $game, 'turn_order' => 0]);
        }

        if (is_null($game)) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        if (is_null($player)) {
            return new JsonResponse(['error' => 'Player not found'], 404);
        }

        if (is_null($token) || $token != $player->getSessionToken() || $player->getTokenExpiration() < new \DateTime()) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        if (is_null($playerInGame)) {
            return new JsonResponse(['error' => 'Player not in game'], 400);
        }

        if (!$game->getIsInProgress()) {
            return new JsonResponse(['error' => 'Game is not in progress'], 400);
        }

        if ($game->getWinner()) {
            return new JsonResponse(['error' => 'Game is already finished'], 400);
        }

        if (!$playerInGame->getIsTurn()) {
            return new JsonResponse(['error' => 'It is not your turn'], 400);
        }

        if (!is_null($playerInGame->getBid1()) && !is_null($playerInGame->getBid2())) {
            return new JsonResponse(['error' => 'You already bid'], 400);
        }

        if (!is_null($playerInGame->getRoll1()) && !is_null($playerInGame->getRoll2())) {
            return new JsonResponse(['error' => 'You already rolled'], 400);
        }

        $dice1 = rand(1, 6);
        $dice2 = rand(1, 6);

        $playerInGame->setRoll1($dice1);
        $playerInGame->setRoll2($dice2);

        if (($dice1 == 1 && $dice2 == 2) || ($dice1 == 2 && $dice2 == 1)) {
            $playerInGame->setIsTurn(false);
            $playerInGame->setBid1(null);
            $playerInGame->setBid2(null);
            $nextPlayerInGame->setPoints($nextPlayerInGame->getPoints() -1);
            $nextPlayerInGame->setIsTurn(true);
        }

        

        // Borrar siguiente turno (roll, bid, isLastAccepted)
        $nextPlayerInGame->setRoll1(null);
        $nextPlayerInGame->setRoll2(null);
        $nextPlayerInGame->setBid1(null);
        $nextPlayerInGame->setBid2(null);
        $nextPlayerInGame->setIsLastAccepted(null);
            // mientras no haya bid function
            $playerInGame->setIsTurn(false);
            $nextPlayerInGame->setIsTurn(true);
            //
        //
        $entityManager->persist($nextPlayerInGame);

        $entityManager->persist($playerInGame);
        $entityManager->flush();
                

        return new JsonResponse(['dice1' => $dice1, 'dice2' => $dice2]);
    }
}
