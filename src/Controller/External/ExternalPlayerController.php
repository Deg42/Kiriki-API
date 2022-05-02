<?php

namespace App\Controller\External;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Player;
use App\Entity\Game;
use App\Entity\PlayerGame;

class ExternalPlayerController extends AbstractController
{

    function registerPlayer(ManagerRegistry $doctrine, Request $request)
    {
        $username = $request->get("username");
        $email = $request->get("email");
        $password = $request->get("password");

        $entityManager = $doctrine->getManager();

        if (is_null($username) || is_null($email) || is_null($password)) {
            return new JsonResponse(['error' => 'Missing parameters'], 400);
        }

        $playerByUser = $entityManager->getRepository(Player::class)->findOneBy(['username' =>  $username]);
        $playerByEmail = $entityManager->getRepository(Player::class)->findOneBy(['email' => $email]);

        if ($playerByUser) {
            return new JsonResponse(['error' => 'There is already a player with that username'], 409);
        }
        if ($playerByEmail) {
            return new JsonResponse(['error' => 'There is already a player with that email'], 409);
        }
        if (!preg_match('/^[a-zA-Z0-9]{3,20}$/', $username)) {
            return new JsonResponse(['error' => 'Invalid username'], 400);
        }
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-\.]+$/', $email)) {
            return new JsonResponse(['error' => 'Invalid email'], 400);
        }
        if (!preg_match('/^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$/', $password)) {
            return new JsonResponse([
                'error' => 'Invalid password',
                'message' => 'password must be at least 8 characters long, contain at least one lowercase letter, one uppercase letter and one number'
            ], 400);
        }

        $player = new Player();
        $player->setUsername($username);
        $player->setEmail($email);
        $player->setPassword(password_hash($password, PASSWORD_DEFAULT));
        $player->setRegDate(new \DateTime());

        $entityManager->persist($player);
        $entityManager->flush();

        return new JsonResponse(['success' => 'Player created successfully'], 201);
    }


    function updatePlayer(ManagerRegistry $doctrine, Request $request)
    {
        $id = $request->get('id');
        $password = $request->get('password');

        $newUsername = $request->get("new_username");
        $newEmail =  $request->get("new_email");
        $newPassword = $request->get("new_password");

        $entityManager = $doctrine->getManager();

        $player = $entityManager->getRepository(Player::class)->find($id);

        $playerByUser = $entityManager->getRepository(Player::class)->findOneBy(['username' => $newUsername]);
        $playerByEmail = $entityManager->getRepository(Player::class)->findOneBy(['email' => $newEmail]);

        if (is_null($player)) {
            return new JsonResponse(['error' => 'Player not found'], 404);
        }

        if (!password_verify($password, $player->getPassword())) {
            return new JsonResponse(['error' => 'Wrong password'], 401);
        }

        if ($newUsername == $player->getUsername()) {
            return new JsonResponse(['error' => 'New username is the same as the old one'], 409);
        }
        if ($newEmail == $player->getEmail()) {
            return new JsonResponse(['error' => 'New email is the same as the old one'], 409);
        }
        if (password_verify($newPassword, $player->getPassword())) {
            return new JsonResponse(['error' => 'New password is the same as the old one'], 400);
        }

        if ($playerByUser) {
            return new JsonResponse(['error' => 'There is already a player with that username'], 409);
        }
        if ($playerByEmail) {
            return new JsonResponse(['error' => 'There is already a player with that email'], 409);
        }

        if (is_null($newUsername) && is_null($newEmail) && is_null($newPassword)) {
            return new JsonResponse(['error' => 'No data to update'], 400);
        }

        if ($newUsername && !preg_match('/^[a-zA-Z0-9]{3,20}$/', $newUsername)) {
            return new JsonResponse(['error' => 'Invalid username'], 400);
        }
        if ($newEmail && !preg_match('/^[a-zA-Z0-9_\-\.]+@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-\.]+$/', $newEmail)) {
            return new JsonResponse(['error' => 'Invalid email'], 400);
        }
        if ($newPassword && !preg_match('/^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$/', $newPassword)) {
            return new JsonResponse([
                'error' => 'Invalid password',
                'message' => 'password must be at least 8 characters long, contain at least one lowercase letter, one uppercase letter and one number'
            ], 400);
        }

        if ($newUsername) {
            $player->setUsername($newUsername);
        }
        if ($newEmail) {
            $player->setEmail($newEmail);
        }

        if ($newPassword) {
            $player->setPassword(password_hash($newPassword, PASSWORD_DEFAULT));
        }

        $entityManager->persist($player);
        $entityManager->flush();

        return new JsonResponse(['success' => 'Player updated successfully'], 200);
    }

    function deletePlayerAccount(ManagerRegistry $doctrine, Request $request)
    {
        $id = $request->get('id');
        $password = $request->get('password');

        $entityManager = $doctrine->getManager();
        $player = $entityManager->getRepository(Player::class)->find($id);

        if (is_null($player)) {
            return new JsonResponse(['error' => 'Player not found'], 404);
        }

        if (!password_verify($password, $player->getPassword())) {
            return new JsonResponse(['error' => 'Wrong password'], 401);
        }

        $entityManager->remove($player);
        $entityManager->flush();

        return new JsonResponse(['success' => 'Player deleted successfully'], 200);
    }

    function createGame(ManagerRegistry $doctrine, Request $request)
    {
        $hostName = $request->get('host_name');
        $gameName = $request->get('game_name');
        $gamePass = $request->get('game_password');
        $maxPoints = $request->get('max_points');

        $entityManager = $doctrine->getManager();
        $host = $entityManager->getRepository(Player::class)->findOneBy(['username' => $hostName]);

        $gameByName = $entityManager->getRepository(Game::class)->findOneBy(['name' => $gameName]);

        if (is_null($host)) {
            return new JsonResponse(['error' => 'Host not found'], 404);
        }

        if (is_null($gameName) || is_null($gamePass)) {
            return new JsonResponse(['error' => 'Missing data'], 400);
        }

        if ($gameByName && $gameByName->getWinner() == null) {
            return new JsonResponse(['error' => 'There is already a game with that name'], 409);
        }

        foreach ($host->getHostedGames() as $game) {
            if ($game->getIsInProgress() || (!$game->getIsInProgress() && is_null($game->getWinner()))) {
                return new JsonResponse(['error' => 'You are already hosting a game'], 409);
            }
        }

        $game = new Game();
        $game->setName($gameName);
        $game->setPassword(password_hash($gamePass, PASSWORD_DEFAULT));
        $game->setDate(new \DateTime());
        $game->setHost($host);
        $game->setIsInProgress(false);

        $playerInGame = new PlayerGame();
        $playerInGame->setPlayer($host);
        $playerInGame->setGame($game);
        $playerInGame->setTurnOrder(0);
        $playerInGame->setPoints($maxPoints ? $maxPoints : 9);
        $playerInGame->setIsTurn(true);

        $game->addPlayer($playerInGame);

        $entityManager->persist($game);
        $entityManager->persist($playerInGame);
        $entityManager->flush();

        return new JsonResponse(['success' => 'Game created successfully'], 201);
    }

    function joinGame(ManagerRegistry $doctrine, Request $request)
    {
        $playerName = $request->get('player_name');
        $gameName = $request->get('game_name');
        $gamePass = $request->get('game_pass');

        $entityManager = $doctrine->getManager();
        $player = $entityManager->getRepository(Player::class)->findOneBy(['username' => $playerName]);
        $game = $entityManager->getRepository(Game::class)->findOneBy(['name' => $gameName]);

        if (is_null($game)) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        if (is_null($player)) {
            return new JsonResponse(['error' => 'Player not found'], 404);
        }

        if (is_null($gamePass) || !password_verify($gamePass, $game->getPassword())) {
            return new JsonResponse(['error' => 'Wrong password'], 401);
        }

        if ($game->getIsInProgress()) {
            return new JsonResponse(['error' => 'Game is already in progress'], 400);
        }

        foreach ($game->getPlayers() as $playerInGame) {
            if ($playerInGame->getPlayer()->getId() == $player->getId()) {
                return new JsonResponse(['error' => 'Player is already in the game'], 400);
            }
        }

        // Si está en otra partida, no puede unirse a otra ?

        $playerInGame = new PlayerGame();
        $playerInGame->setPlayer($player);
        $playerInGame->setGame($game);
        $playerInGame->setTurnOrder(count($game->getPlayers()));
        $playerInGame->setPoints($game->getPlayers()->first()->getPoints());
        $playerInGame->setIsTurn(false);

        $entityManager->persist($playerInGame);
        $entityManager->flush();

        return new JsonResponse(['success' => 'Player joined successfully'], 200);
    }

    function startGame(ManagerRegistry $doctrine, Request $request)
    {
        $hostName = $request->get('host_name');
        $gameName = $request->get('game_name');

        $entityManager = $doctrine->getManager();
        $host = $entityManager->getRepository(Player::class)->findOneBy(['username' => $hostName]);
        $game = $entityManager->getRepository(Game::class)->findOneBy(['name' => $gameName]);

        if (is_null($game)) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        if (is_null($host)) {
            return new JsonResponse(['error' => 'Host not found'], 404);
        }

        if ($host != $game->getHost()) {
            return new JsonResponse(['error' => 'Only the host can start the game'], 401);
        }

        if ($game->getIsInProgress()) {
            return new JsonResponse(['error' => 'Game is already in progress'], 400);
        }

        if ($game->getWinner()) {
            return new JsonResponse(['error' => 'Game is already finished'], 400);
        }

        if (count($game->getPlayers()) < 2) {
            return new JsonResponse(['error' => 'Not enough players to start the game'], 400);
        }

        if (count($game->getPlayers()) > 4) {
            return new JsonResponse(['error' => 'Too many players to start the game'], 400);
        }

        $game->setIsInProgress(true);
        $entityManager->flush();

        return new JsonResponse(['success' => 'Game started successfully'], 200);
    }
}
