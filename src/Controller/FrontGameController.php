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

    const SECUENCE = ['4', '5', '6', '7', '8', '9', '10', 'tocho', 'blacks', 'reds', 'jacks', 'queens', 'kings', 'snake eyes', 'kiriki'];

    private function calculateRollValues($dice1, $dice2)
    {
        $sum = $dice1 + $dice2;

        $value = FrontGameController::SECUENCE[$sum - 4];

        if ($sum == 11) {
            $value = FrontGameController::SECUENCE[7];
        }

        if ($dice1 == $dice2) {
            switch ($dice1) {
                case 1:
                    $value = FrontGameController::SECUENCE[8];
                    break;
                case 2:
                    $value = FrontGameController::SECUENCE[9];
                    break;
                case 3:
                    $value = FrontGameController::SECUENCE[10];
                    break;
                case 4:
                    $value = FrontGameController::SECUENCE[11];
                    break;
                case 5:
                    $value = FrontGameController::SECUENCE[12];
                    break;
                case 6:
                    $value = FrontGameController::SECUENCE[13];
                    break;
                default:
                    break;
            }
        }

        if ($sum == 3) {
            $value = FrontGameController::SECUENCE[14];
        }

        return $value;
    }

    private function valuesAreGreaterOrEqualThanLast($value1, $value2, $previousValue1, $previousValue2)
    {
        return $this->calculateRollValues($value1, $value2) >= $this->calculateRollValues($previousValue1, $previousValue2);
    }

    private function getPreviousPlayer(ManagerRegistry $doctrine, $game, $player)
    {
        $entityManager = $doctrine->getManager();

        $previousPlayerInGame = $entityManager->getRepository(PlayerGame::class)->findOneBy(['game' => $game, 'turn_order' => $player->getTurnOrder() - 1]);
        if (is_null($previousPlayerInGame)) {
            $previousPlayerInGame = $entityManager->getRepository(PlayerGame::class)->findOneBy(['game' => $game, 'turn_order' => $game->getPlayers()->count() - 1]);
        }

        return $previousPlayerInGame;
    }

    private function getNextPlayer(ManagerRegistry $doctrine, $game, $player)
    {
        $entityManager = $doctrine->getManager();

        $nextPlayerInGame = $entityManager->getRepository(PlayerGame::class)->findOneBy(['game' => $game, 'turn_order' => $player->getTurnOrder() + 1]);
        if (is_null($nextPlayerInGame)) {
            $nextPlayerInGame = $entityManager->getRepository(PlayerGame::class)->findOneBy(['game' => $game, 'turn_order' => 0]);
        }

        return $nextPlayerInGame;
    }

    private function checkIfWinner($game)
    {
        if ($game->getPlayers()->count() == 1) {
            $winner = $game->getPlayers()->first();
            $game->setWinner($winner);
            return $winner;
        }

        return false;
    }




    public function getLastBid(ManagerRegistry $doctrine, Request $request)
    {
        $playerId = $request->get('player_id');
        $gameId = $request->get('game_id');
        $token = $request->get('token');

        $entityManager = $doctrine->getManager();
        $player = $entityManager->getRepository(Player::class)->find($playerId);
        $game = $entityManager->getRepository(Game::class)->find($gameId);

        if (is_null($game)) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        if (is_null($player)) {
            return new JsonResponse(['error' => 'Player not found'], 404);
        }


        $playerInGame = $entityManager->getRepository(PlayerGame::class)->findOneBy(['player' => $player, 'game' => $game]);

        $previousPlayerInGame = $this->getPreviousPlayer($doctrine, $game, $playerInGame);
        $previousBid1 = $previousPlayerInGame->getBid1();
        $previousBid2 = $previousPlayerInGame->getBid2();


        if (is_null($playerInGame)) {
            return new JsonResponse(['error' => 'Player not in game'], 400);
        }

        if (is_null($token) || $token != $player->getSessionToken() || $player->getTokenExpiration() < new \DateTime()) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
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

        if (is_null($previousBid1) && is_null($previousBid2)) {
            return new JsonResponse(['error' => 'No bids yet or its the first turn'], 400);
        }

        return new JsonResponse(['bid1' => $previousBid1, 'bid2' => $previousBid2, 'value' => $this->calculateRollValues($previousBid1, $previousBid2)], 200);
    }

    public function getLastRoll(ManagerRegistry $doctrine, Request $request)
    {
        $playerId = $request->get('player_id');
        $gameId = $request->get('game_id');
        $token = $request->get('token');

        $entityManager = $doctrine->getManager();
        $player = $entityManager->getRepository(Player::class)->find($playerId);
        $game = $entityManager->getRepository(Game::class)->find($gameId);
        $playerInGame = $entityManager->getRepository(PlayerGame::class)->findOneBy(['player' => $player, 'game' => $game]);

        $previousPlayerInGame = $this->getPreviousPlayer($doctrine, $game, $playerInGame);

        $lastRoll1 = $previousPlayerInGame->getRoll1();
        $lastRoll2 = $previousPlayerInGame->getRoll2();
        $lastBid1 = $previousPlayerInGame->getBid1();
        $lastBid2 = $previousPlayerInGame->getBid2();

        if (is_null($game)) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        if (is_null($player)) {
            return new JsonResponse(['error' => 'Player not found'], 404);
        }

        if (is_null($playerInGame)) {
            return new JsonResponse(['error' => 'Player not in game'], 400);
        }

        if (is_null($token) || $token != $player->getSessionToken() || $player->getTokenExpiration() < new \DateTime()) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
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

        if (is_null($lastRoll1) && is_null($lastRoll2)) {
            return new JsonResponse(['error' => 'No rolls yet or its the first turn'], 400);
        }

        if ($playerInGame->getIsLastAccepted()) {
            return new JsonResponse(['error' => 'You have already accepted the last bid'], 400);
        }

        $playerInGame->setIsLastAccepted(true);

        $pointLoser = "";
        if ($this->valuesAreGreaterOrEqualThanLast($lastRoll1, $lastRoll2, $lastBid1, $lastBid2)) {
            $playerInGame->setPoints($playerInGame->getPoints() - 1);
            if ($playerInGame->getPoints() == 0) {
                $entityManager->remove($player);
            }
            $entityManager->persist($playerInGame);
            $pointLoser = $player->getUsername();
        } else {
            $playerInGame->setIsTurn(false);
            $entityManager->persist($playerInGame);
            $previousPlayerInGame->setPoints($previousPlayerInGame->getPoints() - 1);
            if ($previousPlayerInGame->getPoints() == 0) {
                $entityManager->remove($previousPlayerInGame);
            }
            $previousPlayerInGame->setIsTurn(true);
            $previousPlayerInGame->setRoll1(null);
            $previousPlayerInGame->setRoll2(null);
            $previousPlayerInGame->setBid1(null);
            $previousPlayerInGame->setBid2(null);
            $entityManager->persist($previousPlayerInGame);
            $pointLoser = $previousPlayerInGame->getPlayer()->getUsername();
        }

        $entityManager->flush();

        if ($this->checkIfWinner($game)) {
            return new JsonResponse(['success' => 'Game finished', 'winner' => $game->getWinner()], 200);
        } else {
            return new JsonResponse(
                [
                    'roll1' => $lastRoll1,
                    'roll2' => $lastRoll2,
                    'roll_value' => $this->calculateRollValues($lastRoll1, $lastRoll2),
                    'bid1' => $lastBid1,
                    'bid2' => $lastBid2,
                    'bid_value' => $this->calculateRollValues($lastBid1, $lastBid2),
                    'point_loser' => $pointLoser
                ],
                200
            );
        }
    }

    function rollDices(ManagerRegistry $doctrine, Request $request)
    {
        $playerId = $request->get('player_id');
        $gameId = $request->get('game_id');
        $token = $request->get('token');

        $entityManager = $doctrine->getManager();
        $player = $entityManager->getRepository(Player::class)->find($playerId);
        $game = $entityManager->getRepository(Game::class)->find($gameId);

        if (is_null($game)) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        if (is_null($player)) {
            return new JsonResponse(['error' => 'Player not found'], 404);
        }

        $playerInGame = $entityManager->getRepository(PlayerGame::class)->findOneBy(['player' => $player, 'game' => $game]);

        $previousPlayerInGame = $this->getPreviousPlayer($doctrine, $game, $playerInGame);

        $nextPlayerInGame = $this->getNextPlayer($doctrine, $game, $playerInGame);


        if (is_null($playerInGame)) {
            return new JsonResponse(['error' => 'Player not in game'], 400);
        }

        if (is_null($token) || $token != $player->getSessionToken() || $player->getTokenExpiration() < new \DateTime()) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
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

        if (!is_null($playerInGame->getRoll1()) && !is_null($playerInGame->getRoll2())) {
            return new JsonResponse(
                [
                    'error' => 'You already rolled, make a bid',
                    'dice1' => $playerInGame->getRoll1(),
                    'dice2' => $playerInGame->getRoll2(),
                    'roll_value' => $this->calculateRollValues($playerInGame->getRoll1(), $playerInGame->getRoll2())
                ],
                400
            );
        }

        $dice1 = rand(1, 6);
        $dice2 = rand(1, 6);

        $playerInGame->setRoll1($dice1);
        $playerInGame->setRoll2($dice2);


        if ($this->calculateRollValues($dice1, $dice2) == 'kiriki') {
            $playerInGame->setIsTurn(false);
            $playerInGame->setBid1(null);
            $playerInGame->setBid2(null);
            $nextPlayerInGame->setPoints($nextPlayerInGame->getPoints() - 1);
            $nextPlayerInGame->setIsLastAccepted(true);
            $nextPlayerInGame->setIsTurn(true);
            $entityManager->persist($nextPlayerInGame);
            $entityManager->persist($playerInGame);
            $entityManager->flush();

            return new JsonResponse(['roll1' => $dice1, 'roll2' => $dice2, 'value' => $this->calculateRollValues($dice1, $dice2), 'point_loser' => $nextPlayerInGame->getPlayer()->getUsername()], 200);
        }

        $entityManager->persist($playerInGame);
        $entityManager->flush();

        if ($this->checkIfWinner($game)) {
            return new JsonResponse(['success' => 'Game finished', 'winner' => $game->getWinner()], 200);
        } else {
            return new JsonResponse(['roll1' => $dice1, 'roll2' => $dice2, 'value' => $this->calculateRollValues($dice1, $dice2)], 200);
        }
    }

    function setBid(ManagerRegistry $doctrine, Request $request)
    {
        $playerId = $request->get('player_id');
        $gameId = $request->get('game_id');
        $token = $request->get('token');
        $bid1 = $request->get('bid_1');
        $bid2 = $request->get('bid_2');

        $entityManager = $doctrine->getManager();
        $player = $entityManager->getRepository(Player::class)->find($playerId);
        $game = $entityManager->getRepository(Game::class)->find($gameId);
        $playerInGame = $entityManager->getRepository(PlayerGame::class)->findOneBy(['player' => $player, 'game' => $game]);

        $previousPlayerInGame = $this->getPreviousPlayer($doctrine, $game, $playerInGame);
        $previousBid1 = $previousPlayerInGame->getBid1();
        $previousBid2 = $previousPlayerInGame->getBid2();

        $nextPlayerInGame = $this->getNextPlayer($doctrine, $game, $playerInGame);

        if (is_null($game)) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        if (is_null($player)) {
            return new JsonResponse(['error' => 'Player not found'], 404);
        }

        if (is_null($playerInGame)) {
            return new JsonResponse(['error' => 'Player not in game'], 400);
        }

        if (is_null($token) || $token != $player->getSessionToken() || $player->getTokenExpiration() < new \DateTime()) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
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

        if (is_null($playerInGame->getRoll1()) && is_null($playerInGame->getRoll2())) {
            return new JsonResponse(['error' => 'You have not rolled yet'], 400);
        }


        if ($previousBid1 && $previousBid2 && (!$this->valuesAreGreaterOrEqualThanLast($bid1, $bid2, $previousBid1, $previousBid2))) {
            return new JsonResponse(['error' => 'Bid is not greater or equal than last bid'], 400);
        }

        // Borrar turno anterior (roll, bid, isLastAccepted)
        $previousPlayerInGame->setRoll1(null);
        $previousPlayerInGame->setRoll2(null);
        $previousPlayerInGame->setBid1(null);
        $previousPlayerInGame->setBid2(null);
        $previousPlayerInGame->setIsLastAccepted(null);
        $entityManager->persist($previousPlayerInGame);

        $playerInGame->setBid1($bid1);
        $playerInGame->setBid2($bid2);
        $playerInGame->setIsTurn(false);
        $entityManager->persist($playerInGame);

        $nextPlayerInGame->setIsTurn(true);
        $entityManager->persist($nextPlayerInGame);

        $entityManager->flush();

        return new JsonResponse(['bid1' => $bid1, 'bid2' => $bid2, 'value' => $this->calculateRollValues($bid1, $bid2)]);
    }
}
