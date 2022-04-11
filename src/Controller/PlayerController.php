<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Player;


class PlayerController extends AbstractController
{
    function getAllPlayers(ManagerRegistry $doctrine, Request $request)
    {
        if ($request->get('token') != $this->getParameter('app.API_KEY')) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        $entityManager = $doctrine->getManager();
        $players = $entityManager->getRepository(Player::class)->findAll();

        $results  = new \stdClass();
        $results->count = count($players);
        $results->results = array();

        foreach ($players as $player) {
            $subresult = new \stdClass();
            $subresult->id = $player->getId();
            $subresult->username = $player->getUsername();
            $subresult->email = $player->getEmail();
            $subresult->created_at = $player->getRegDate();

            array_push($results->results, $subresult);
        }

        return new JsonResponse($results, 200);
    }

    function getPlayer(ManagerRegistry $doctrine, Request $request){
        if ($request->get('token') != $this->getParameter('app.API_KEY')) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        $entityManager = $doctrine->getManager();
        $player = $entityManager->getRepository(Player::class)->find($request->get('id'));

        if (!$player) {
            return new JsonResponse(['error' => 'Player not found'], 404);
        }

        $result = new \stdClass();
        $result->username = $player->getUsername();
        $result->email = $player->getEmail();
        $result->created_at = $player->getRegDate();

        return new JsonResponse($result, 200);
    }

    function getPlayerGames(ManagerRegistry $doctrine, Request $request)
    {
        if ($request->get('token') != $this->getParameter('app.API_KEY')) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        $id = $request->get('id');

        $entityManager = $doctrine->getManager();
        $player = $entityManager->getRepository(Player::class)->find($id);

        if (is_null($player)) {
            return new JsonResponse(['error' => 'Player not found for id ' . $id], 404);
        }

        $result = new \stdClass();
        $result->games_played = new \stdClass();
        $result->games_played->count = count($player->getGamesPlayed() ?? []);
        $result->games_played->results = array();

        $result->games_hosted = new \stdClass();
        $result->games_hosted->count = count($player->getHostedGames());
        $result->games_hosted->results = array();

        $result->games_won = new \stdClass();
        $result->games_won->count = count($player->getGamesWon());
        $result->games_won->results = array();

        if ($player->getGamesPlayed()) {
            foreach ($player->getGamesPlayed() as $played_game) {
                $result->games_played->results[] = $this->generateUrl('admin_get_games', [
                    'id' => $played_game->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            }
        }

        foreach ($player->getHostedGames() as $hosted_game) {
            $result->games_hosted->results[] = $this->generateUrl('admin_get_games', [
                'id' => $hosted_game->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        foreach ($player->getGamesWon() as $game_won) {
            $result->games_won->results[] = $this->generateUrl('admin_get_games', [
                'id' => $game_won->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return new JsonResponse($result, 200);
    }

    function postPlayer(ManagerRegistry $doctrine, Request $request)
    {
        if (is_null($request->get('token')) || $request->get('token') != $this->getParameter('app.API_KEY')) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        $username = $request->get("username");
        $email = $request->get("email");
        $password = $request->get("password");

        $entityManager = $doctrine->getManager();

        if (is_null($username) || is_null($email) || is_null($password)) {
            return new JsonResponse(['error' => 'Missing parameters'], 400);
        }

        $playerByUser = $entityManager->getRepository(Player::class)->findOneBy(['username' => $username]);
        $playerByEmail = $entityManager->getRepository(Player::class)->findOneBy(['email' => $email]);

        if ($playerByUser) {
            return new JsonResponse(['error' => 'Username already exists'], 400);
        }
        if ($playerByEmail) {
            return new JsonResponse(['error' => 'Email already exists'], 400);
        }

        $player = new Player();
        $player->setUsername($username);
        $player->setEmail($email);
        $player->setPassword(password_hash($password, PASSWORD_DEFAULT));
        $player->setRegDate(new \DateTime());

        $entityManager->persist($player);
        $entityManager->flush();

        $result = new \stdClass();
        $result->id = $player->getId();
        $result->username = $player->getUsername();
        $result->email = $player->getEmail();
        $result->created_at = $player->getRegDate();

        return new JsonResponse($result, 201);
    }

    function patchPlayer(ManagerRegistry $doctrine, Request $request)
    {
        if ($request->get('token') != $this->getParameter('app.API_KEY')) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        $id = $request->get('id');
        $newUsername = $request->get("new_username");
        $newEmail =  $request->get("new_email");
        $newPassword = $request->get("new_password");

        $entityManager = $doctrine->getManager();
        $player = $entityManager->getRepository(Player::class)->find($id);

        $playerByUser = $entityManager->getRepository(Player::class)->findOneBy(['username' => $newUsername]);
        $playerByEmail = $entityManager->getRepository(Player::class)->findOneBy(['email' => $newEmail]);
        
        if (is_null($player)) {
            return new JsonResponse(['error' => 'Player not found for id ' . $id], 404);
        }

        if ($newUsername == $player->getUsername()) {
            return new JsonResponse(['error' => 'Username not changed'], 400);
        }
        if ($newEmail == $player->getEmail()) {
            return new JsonResponse(['error' => 'Email not changed'], 400);
        }
        if (password_verify($newPassword, $player->getPassword())) {
            return new JsonResponse(['error' => 'Password not changed'], 400);
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

        $result = new \stdClass();
        $result->username = $player->getUsername();
        $result->email = $player->getEmail();
        $result->created_at = $player->getRegDate();

        return new JsonResponse($result, 200);
    }

    function deletePlayer(ManagerRegistry $doctrine, Request $request)
    {
        if ($request->get('token') != $this->getParameter('app.API_KEY')) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        $id = $request->get('id');

        $entityManager = $doctrine->getManager();
        $player = $entityManager->getRepository(Player::class)->find($id);

        if (is_null($player)) {
            return new JsonResponse(['error' => 'Player not found for id ' . $id], 404);
        }

        $entityManager->remove($player);
        $entityManager->flush();

        return new JsonResponse(null, 204);
    }
}
