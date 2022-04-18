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

        $playerByUser = $entityManager->getRepository(Player::class)->findOneBy(['username' =>  $username]);
        $playerByEmail = $entityManager->getRepository(Player::class)->findOneBy(['email' => $email]);

        if ($playerByUser) {
            return new JsonResponse(['error' => 'There is already a player with that username'], 409);
        }
        if ($playerByEmail) {
            return new JsonResponse(['error' => 'There is already a player with that email'], 409);
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

    function loginPlayer(ManagerRegistry $doctrine, Request $request)
    {
        $username = $request->get("username");
        $email = $request->get("email");
        $password = $request->get("password");

        $entityManager = $doctrine->getManager();

        $player = $entityManager->getRepository(Player::class)->findOneBy(['username' => $username]);
        if (is_null($player)) {
            $player = $entityManager->getRepository(Player::class)->findOneBy(['email' => $email]);
        }

        if (is_null($player)) {
            return new JsonResponse(['error' => 'Player not found'], 404);
        }

        if (!password_verify($password, $player->getPassword())) {
            return new JsonResponse(['error' => 'Wrong password'], 401);
        }

        $player->setSessionToken(bin2hex(random_bytes(16)));
        $player->setTokenExpiration(new \DateTime('now +12 hours'));

        $entityManager->persist($player);
        $entityManager->flush();
        
        return new JsonResponse(['success' => 'Player logged in successfully', 'token' => $player->getSessionToken()], 200);
    }

    function updatePlayer(ManagerRegistry $doctrine, Request $request)
    {
        
        $id = $request->get('id');
        $token = $request->get('token');
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

        if (is_null($token) || $token != $player->getSessionToken() || $player->getTokenExpiration() < new \DateTime()) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
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
        $token = $request->get('token');
        $password = $request->get('password');

        $entityManager = $doctrine->getManager();
        $player = $entityManager->getRepository(Player::class)->find($id);

        if (is_null($player)) {
            return new JsonResponse(['error' => 'Player not found'], 404);
        }

        if (is_null($token) || $token != $player->getSessionToken() || $player->getTokenExpiration() < new \DateTime()) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
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
        $token = $request->get('token');
        $hostId = $request->get('player_id');
        $gameName = $request->get('game_name');
        $gamePass = $request->get('game_password');

        $entityManager = $doctrine->getManager();
        $host = $entityManager->getRepository(Player::class)->find($hostId);

        $gameByName = $entityManager->getRepository(Game::class)->findOneBy(['name' => $gameName]);

        if (is_null($host)) {
            return new JsonResponse(['error' => 'Host not found'], 404);
        }

        if (is_null($token) || $token != $host->getSessionToken() || $host->getTokenExpiration() < new \DateTime()) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        if (is_null($gameName) || is_null($gamePass)) {
            return new JsonResponse(['error' => 'Missing data'], 400);
        }

        if ($gameByName && $gameByName->getWinner() == null) {
            return new JsonResponse(['error' => 'There is already a game with that name'], 409);
        }

        // Si está hosteando una partida, no puede crear otra ?

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
        $playerInGame->setPoints(9);
        $playerInGame->setIsTurn(true);

        $game->addPlayer($playerInGame);

        $entityManager->persist($game);
        $entityManager->persist($playerInGame);
        $entityManager->flush();

        return new JsonResponse(['success' => 'Game created successfully'], 201);
    }

    function joinGame(ManagerRegistry $doctrine, Request $request)
    {
        $playerId = $request->get('player_id');
        $token = $request->get('token');
        $gameName = $request->get('game_name');
        $gamePass = $request->get('game_pass');

        $entityManager = $doctrine->getManager();
        $player = $entityManager->getRepository(Player::class)->find($playerId);
        $game = $entityManager->getRepository(Game::class)->findOneBy(['name' => $gameName]);

        if (is_null($game)) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        if (is_null($player)) {
            return new JsonResponse(['error' => 'Player not found'], 404);
        }

        if (is_null($token) || $token != $player->getSessionToken() || $player->getTokenExpiration() < new \DateTime()) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
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
        $playerInGame->setPoints(9);
        $playerInGame->setIsTurn(false);

        $entityManager->persist($playerInGame);
        $entityManager->flush();

        return new JsonResponse(['success' => 'Player joined successfully'], 200);
    }

    function startGame(ManagerRegistry $doctrine, Request $request){
        $host = $request->get('host_id');
        $token = $request->get('token');
        $gameId = $request->get('game_id');

        $entityManager = $doctrine->getManager();
        $host = $entityManager->getRepository(Player::class)->find($host);
        $game = $entityManager->getRepository(Game::class)->find($gameId);

        if (is_null($game)) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        if (is_null($host)) {
            return new JsonResponse(['error' => 'Host not found'], 404);
        }

        if ($host != $game->getHost()) {
            return new JsonResponse(['error' => 'Only the host can start the game'], 401);
        }

        if (is_null($token) || $token != $game->getHost()->getSessionToken() || $game->getHost()->getTokenExpiration() < new \DateTime()) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        if ($game->getIsInProgress()) {
            return new JsonResponse(['error' => 'Game is already in progress'], 400);
        }

        // NO TESTEADO
        if ($game->getWinner()) {
            return new JsonResponse(['error' => 'Game is already finished'], 400);
        }
        //
        
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
