<?php

namespace App\Controller\External;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Player;
use App\Entity\Game;
use App\Entity\PlayerGame;

class ExternalGameController extends AbstractController
{

    const SECUENCE = ['4', '5', '6', '7', '8', '9', '10', 'tocho', 'blacks', 'reds', 'jacks', 'queens', 'kings', 'snake eyes', 'kiriki'];

    public function getLastBid(ManagerRegistry $doctrine, Request $request)
    {
        $playerUsername = $request->get('player_name');
        $gameId = $request->get('game_id');

        $entityManager = $doctrine->getManager();
        $player = $entityManager->getRepository(Player::class)->findOneBy(['username' => $playerUsername]);
        $game = $entityManager->getRepository(Game::class)->find($gameId);

        if (is_null($game)) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        if (is_null($player)) {
            return new JsonResponse(['error' => 'Player not found'], 404);
        }

        $playerInGame = $entityManager->getRepository(PlayerGame::class)->findOneBy(['player' => $player, 'game' => $game]);

        if (is_null($playerInGame)) {
            return new JsonResponse(['error' => 'Player not in game'], 400);
        }

        $previousPlayerInGame = $this->getPreviousPlayer($doctrine, $game, $playerInGame);
        $previousBid1 = $previousPlayerInGame->getBid1();
        $previousBid2 = $previousPlayerInGame->getBid2();

        if (!$game->getIsInProgress()) {
            return new JsonResponse(['error' => 'Game is not in progress'], 400);
        }

        if ($game->getWinner()) {
            return new JsonResponse(['error' => 'Game is already finished', 'winner' => $game->getWinner()->getUsername()], 400);
        }

        if (is_null($previousBid1) && is_null($previousBid2)) {
            return new JsonResponse(['error' => 'No bids yet or its the first turn'], 400);
        }

        return new JsonResponse(['player' => $previousPlayerInGame->getPlayer()->getUsername(), 'bid1' => $previousBid1, 'bid2' => $previousBid2, 'value' => $this->calculateRollValues($previousBid1, $previousBid2)], 200);
    }

    public function getLastRoll(ManagerRegistry $doctrine, Request $request)
    {
        $playerUsername = $request->get('player_name');
        $gameId = $request->get('game_id');

        $entityManager = $doctrine->getManager();
        $player = $entityManager->getRepository(Player::class)->findOneBy(['username' => $playerUsername]);
        $game = $entityManager->getRepository(Game::class)->find($gameId);

        if (is_null($game)) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        if (is_null($player)) {
            return new JsonResponse(['error' => 'Player not found'], 404);
        }

        $playerInGame = $entityManager->getRepository(PlayerGame::class)->findOneBy(['player' => $player, 'game' => $game]);

        if (is_null($playerInGame)) {
            return new JsonResponse(['error' => 'Player not in game'], 400);
        }

        $previousPlayerInGame = $this->getPreviousPlayer($doctrine, $game, $playerInGame);

        $lastRoll[0] = $previousPlayerInGame->getRoll1();
        $lastRoll[1] = $previousPlayerInGame->getRoll2();
        $lastRoll[2] = $this->calculateRollValues($lastRoll[0], $lastRoll[1]);
        $lastBid[0] = $previousPlayerInGame->getBid1();
        $lastBid[1] = $previousPlayerInGame->getBid2();
        $lastBid[2] = $this->calculateRollValues($lastBid[0], $lastBid[1]);

        if (!$game->getIsInProgress()) {
            return new JsonResponse(['error' => 'Game is not in progress'], 400);
        }

        if ($game->getWinner()) {
            return new JsonResponse(['error' => 'Game is already finished', 'winner' => $game->getWinner()->getUsername()], 400);
        }

        if (!$playerInGame->getIsTurn()) {
            return new JsonResponse(['error' => 'It is not your turn'], 400);
        }

        if (is_null($lastRoll[0]) && is_null($lastRoll[1])) {
            return new JsonResponse(['error' => 'No rolls yet or its the first turn'], 400);
        }

        $playerInGame->setIsTurn(false);

        $pointLoser = "";
        if ($this->valuesAreGreaterOrEqualThanLast($lastRoll, $lastBid)) {
            $this->removePoint($playerInGame);
            $pointLoser = $player->getUsername();
        } else {
            $this->removePoint($previousPlayerInGame);
            $this->deleteTurnInfo($previousPlayerInGame);
            $entityManager->persist($previousPlayerInGame);
            $pointLoser = $previousPlayerInGame->getPlayer()->getUsername();
        }

        $entityManager->persist($playerInGame);
        $entityManager->flush();
        
        if ($this->checkIfWinner($game)) {
            $this->finishGame($game);
            $this->finishGame($game);
            $entityManager->persist($game);
            $entityManager->flush();
        }

        return new JsonResponse(
            [
                'roll_1' => $lastRoll[0],
                'roll_2' => $lastRoll[1],
                'roll_value' => $lastRoll[2],
                'bid_1' => $lastBid[0],
                'bid_2' => $lastBid[1],
                'bid_value' => $lastBid[2],
                'point_loser' => $pointLoser
            ],
            200
        );
    }

    public function rollDices(ManagerRegistry $doctrine, Request $request)
    {
        $playerUsername = $request->get('player_name');
        $gameId = $request->get('game_id');

        $entityManager = $doctrine->getManager();
        $player = $entityManager->getRepository(Player::class)->findOneBy(['username' => $playerUsername]);
        $game = $entityManager->getRepository(Game::class)->find($gameId);

        if (is_null($game)) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        if (is_null($player)) {
            return new JsonResponse(['error' => 'Player not found'], 404);
        }

        $playerInGame = $entityManager->getRepository(PlayerGame::class)->findOneBy(['player' => $player, 'game' => $game]);

        if (is_null($playerInGame)) {
            return new JsonResponse(['error' => 'Player not in game'], 400);
        }

        $nextPlayerInGame = $this->getNextPlayer($doctrine, $game, $playerInGame);

        if (!$game->getIsInProgress()) {
            return new JsonResponse(['error' => 'Game is not in progress'], 400);
        }

        if ($game->getWinner()) {
            return new JsonResponse(['error' => 'Game is already finished', 'winner' => $game->getWinner()->getUsername()], 400);
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

        $roll = [];
        $roll = $this->randomRoll();

        $playerInGame->setRoll1($roll[0]);
        $playerInGame->setRoll2($roll[1]);


        if ($roll[2] == 'kiriki') {
            $playerInGame->setIsTurn(false);
            $playerInGame->setBid1(null);
            $playerInGame->setBid2(null);
            $nextPlayerInGame->setPoints($nextPlayerInGame->getPoints() - 1);
            $nextPlayerInGame->setIsTurn(true);
            $entityManager->persist($nextPlayerInGame);
            $entityManager->persist($playerInGame);
            $entityManager->flush();

            return new JsonResponse([
                'roll1' => $roll[0],
                'roll2' => $roll[1],
                'value' => $roll[2],
                'point_loser' => $nextPlayerInGame->getPlayer()->getUsername()
            ], 200);
        }

        $entityManager->persist($playerInGame);
        $entityManager->flush();

        if ($this->checkIfWinner($game)) {
            $this->finishGame($game);
            $entityManager->persist($game);
            $entityManager->flush();
            return new JsonResponse(['success' => 'Game finished', 'winner' => $game->getWinner()], 200);
        } else {
            return new JsonResponse(['roll1' => $roll[0], 'roll2' => $roll[1], 'value' => $roll[2]], 200);
        }
    }

    public function setBid(ManagerRegistry $doctrine, Request $request)
    {
        $playerUsername = $request->get('player_name');
        $gameId = $request->get('game_id');
        $actualBid = [];
        array_push(
            $actualBid,
            intval($request->get('bid_1')),
            intval($request->get('bid_2')),
            $this->calculateRollValues($request->get('bid_1'), $request->get('bid_2'))
        );

        $entityManager = $doctrine->getManager();
        $player = $entityManager->getRepository(Player::class)->findOneBy(['username' => $playerUsername]);
        $game = $entityManager->getRepository(Game::class)->find($gameId);

        if (is_null($game)) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        if (is_null($player)) {
            return new JsonResponse(['error' => 'Player not found'], 404);
        }

        $playerInGame = $entityManager->getRepository(PlayerGame::class)->findOneBy(['player' => $player, 'game' => $game]);

        if (is_null($playerInGame)) {
            return new JsonResponse(['error' => 'Player not in game'], 400);
        }

        $previousPlayerInGame = $this->getPreviousPlayer($doctrine, $game, $playerInGame);

        $previousBid = [];
        if ($previousPlayerInGame->getBid1() && $previousPlayerInGame->getBid2()) {

            array_push(
                $previousBid,
                $previousPlayerInGame->getBid1(),
                $previousPlayerInGame->getBid2(),
                $this->calculateRollValues($previousPlayerInGame->getBid1(), $previousPlayerInGame->getBid2())
            );
        }

        $nextPlayerInGame = $this->getNextPlayer($doctrine, $game, $playerInGame);

        if (is_null($actualBid[0]) || is_null($actualBid[1])) {
            return new JsonResponse(['error' => 'Bid not set'], 400);
        }

        if ($actualBid[0] > 6  || $actualBid[1] > 6 || $actualBid[0] < 1 || $actualBid[1] < 1 || $actualBid[2] == 'kiriki') {
            return new JsonResponse(['error' => 'Not a valid bid'], 400);
        }

        if (!$game->getIsInProgress()) {
            return new JsonResponse(['error' => 'Game is not in progress'], 400);
        }

        if ($game->getWinner()) {
            return new JsonResponse(['error' => 'Game is already finished', 'winner' => $game->getWinner()->getUsername()], 400);
        }

        if (!$playerInGame->getIsTurn()) {
            return new JsonResponse(['error' => 'It is not your turn'], 400);
        }

        if (is_null($playerInGame->getRoll1()) && is_null($playerInGame->getRoll2())) {
            return new JsonResponse(['error' => 'You have not rolled yet'], 400);
        }

        if (!empty($previousBid) && (!$this->valuesAreGreaterOrEqualThanLast($actualBid, $previousBid))) {
            return new JsonResponse(['error' => 'Bid is not greater or equal than last bid', 'your_bid' => $actualBid, 'last_bid' => $previousBid], 400);
        }

        $this->deleteTurnInfo($previousPlayerInGame);
        $entityManager->persist($previousPlayerInGame);


        $playerInGame->setBid1($actualBid[0]);
        $playerInGame->setBid2($actualBid[1]);
        $playerInGame->setIsTurn(false);
        $entityManager->persist($playerInGame);

        $nextPlayerInGame->setIsTurn(true);
        $entityManager->persist($nextPlayerInGame);

        $entityManager->flush();

        return new JsonResponse(['bid_1' => $actualBid[0], 'bid_2' => $actualBid[1], 'bid_value' => $actualBid[2]], 200);
    }

    private function randomRoll()
    {
        $roll = [];
        $dice1 = rand(1, 6);
        $dice2 = rand(1, 6);

        array_push($roll, $dice1, $dice2, $this->calculateRollValues($dice1, $dice2));
        return $roll;
    }

    private function calculateRollValues($dice1, $dice2)
    {

        if (($dice1 + $dice2) == 11) {
            return ExternalGameController::SECUENCE[7];
        }

        if ($dice1 == $dice2) {
            switch ($dice1) {
                case 1:
                    return ExternalGameController::SECUENCE[8];
                    break;
                case 2:
                    return ExternalGameController::SECUENCE[9];
                    break;
                case 3:
                    return ExternalGameController::SECUENCE[10];
                    break;
                case 4:
                    return ExternalGameController::SECUENCE[11];
                    break;
                case 5:
                    return ExternalGameController::SECUENCE[12];
                    break;
                case 6:
                    return ExternalGameController::SECUENCE[13];
                    break;
                default:
                    break;
            }
        }

        if (($dice1 + $dice2) == 3) {
            return ExternalGameController::SECUENCE[14];
        }

        return ExternalGameController::SECUENCE[array_search($dice1 + $dice2, ExternalGameController::SECUENCE)];
    }

    private function valuesAreGreaterOrEqualThanLast($actual, $last)
    {
        $actualKey = array_search($this->calculateRollValues($actual[0], $actual[1]), ExternalGameController::SECUENCE);
        $lastKey = array_search($this->calculateRollValues($last[0], $last[1]), ExternalGameController::SECUENCE);
        return $actualKey >= $lastKey;
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

    private function getNextPlayer(ManagerRegistry $doctrine, $game, $playerInGame)
    {
        if ($this->checkIfWinner($game)) {
            return $playerInGame;
        }

        $entityManager = $doctrine->getManager();

        do {
            $nextPlayerInGame = $entityManager->getRepository(PlayerGame::class)->findOneBy(['game' => $game, 'turn_order' => $playerInGame->getTurnOrder() + 1]);

            if (is_null($nextPlayerInGame)) {
                $nextPlayerInGame = $entityManager->getRepository(PlayerGame::class)->findOneBy(['game' => $game, 'turn_order' => 0]);
                $playerInGame = $nextPlayerInGame;
            }
        } while ($nextPlayerInGame->getPoints() <= 0);
        return $nextPlayerInGame;
    }

    private function checkIfWinner($game)
    {
        $playersStillInGame = [];
        foreach ($game->getPlayers() as $playerGame) {
            if ($playerGame->getPoints() > 0) {
                array_push($playersStillInGame, $playerGame);
            }
        }

        if (count($playersStillInGame) == 1) {
            return $playersStillInGame[0];
        }

        return false;
    }

    private function finishGame($game)
    {
        $game->setWinner($this->checkIfWinner($game)->getPlayer());
        $game->setIsInProgress(false);
    }

    private function deleteTurnInfo($playerInGame)
    {
        $playerInGame->setRoll1(null);
        $playerInGame->setRoll2(null);
        $playerInGame->setBid1(null);
        $playerInGame->setBid2(null);
    }

    private function removePoint($playerInGame)
    {
        $playerInGame->setPoints($playerInGame->getPoints() - 1);
        $playerInGame->setIsTurn(true);
    }
}
