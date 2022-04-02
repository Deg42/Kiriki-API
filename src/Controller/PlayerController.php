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
    function getAllPlayers(ManagerRegistry $doctrine)
    {
        $entityManager = $doctrine->getManager();
        $players = $entityManager->getRepository(Player::class)->findAll();

        if ($players == null) {
            return new JsonResponse([
                'error' => 'No players found'
            ], 404);
        }

        $results  = new \stdClass();
        $results->count = count($players);
        $results->results = array();

        foreach ($players as $player) {
            $result = new \stdClass();
            $result->id = $player->getId();
            $result->username = $player->getUsername();
            $result->email = $player->getEmail();
            $result->created_at = $player->getRegDate();

            $result->games_played = new \stdClass();
            $result->games_played->count = count($player->getGames() ?? []);
            $result->games_played->results = array();

            $result->games_hosted = new \stdClass();
            $result->games_hosted->count = count($player->getHostedGames());
            $result->games_hosted->results = array();

            $result->games_won = new \stdClass();
            $result->games_won->count = count($player->getWonGames());
            $result->games_won->results = array();

            if ($player->getGames()) {
                foreach ($player->getGames() as $played_game) {
                    $result->games_hosted->results[] = $this->generateUrl('api_get_games', [
                        'id' => $played_game->getId(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL);
                }
            }

            foreach ($player->getHostedGames() as $hosted_game) {
                $result->games_hosted->results[] = $this->generateUrl('api_get_games', [
                    'id' => $hosted_game->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            }

            foreach ($player->getWonGames() as $won_game) {
                $result->games_won->results[] = $this->generateUrl('api_get_games', [
                    'id' => $won_game->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            }

            array_push($results->results, $result);
        }

        return new JsonResponse($results, 200);
    }

    function getPlayer(ManagerRegistry $doctrine, Request $request)
    {

        $id = $request->get('id');

        $entityManager = $doctrine->getManager();
        $player = $entityManager->getRepository(Player::class)->find($id);

        if ($player == null) {
            return new JsonResponse([
                'error' => 'No player found for id ' . $id
            ], 404);
        }

        $result = new \stdClass();
        $result->id = $player->getId();
        $result->username = $player->getUsername();
        $result->email = $player->getEmail();
        $result->created_at = $player->getRegDate();

        $result->games_played = new \stdClass();
        $result->games_played->count = count($player->getGames() ?? []);
        $result->games_played->results = array();

        $result->games_hosted = new \stdClass();
        $result->games_hosted->count = count($player->getHostedGames());
        $result->games_hosted->results = array();

        $result->games_won = new \stdClass();
        $result->games_won->count = count($player->getWonGames());
        $result->games_won->results = array();

        if ($player->getGames()) {
            foreach ($player->getGames() as $played_game) {
                $result->games_hosted->results[] = $this->generateUrl('api_get_games', [
                    'id' => $played_game->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            }
        }
        
        foreach ($player->getHostedGames() as $hosted_game) {
            $result->games_hosted->results[] = $this->generateUrl('api_get_games', [
                'id' => $hosted_game->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        foreach ($player->getWonGames() as $won_game) {
            $result->games_won->results[] = $this->generateUrl('api_get_games', [
                'id' => $won_game->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return new JsonResponse($result, 200);
    }

    function postPlayer(ManagerRegistry $doctrine, Request $request)
    {
        $entityManager = $doctrine->getManager();

        $playerByUser = $entityManager->getRepository(Player::class)->findOneBy(['username' => $request->get("username")]);
        $playerByEmail = $entityManager->getRepository(Player::class)->findOneBy(['email' => $request->get("email")]);

        if ($playerByUser || $playerByEmail) {
            return new JsonResponse([
                'error' => 'There is already a player with that email or username'
            ], 409);
        }

        $player = new Player();
        $player->setUsername($request->get('username'));
        $player->setEmail($request->get('email'));
        $player->setPassword(password_hash($request->get("password"), PASSWORD_DEFAULT));
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

        $entityManager = $doctrine->getManager();
        $player = $entityManager->getRepository(Player::class)->find($request->get('id'));

        $playerByUser = $entityManager->getRepository(Player::class)->findOneBy(['username' => $request->get("new_username")]);
        $playerByEmail = $entityManager->getRepository(Player::class)->findOneBy(['email' => $request->get("new_email")]);

        if ($player == null) {
            return new JsonResponse([
                'error' => 'Player not found'
            ], 404);
        }

        if (!password_verify($request->get("password"), $player->getPassword())) {
            return new JsonResponse([
                'error' => 'Wrong password'
            ], 401);
        }

        if ($playerByUser) {
            return new JsonResponse([
                'error' => 'There is already a player with that username'
            ], 409);
        }

        if ($playerByEmail) {
            return new JsonResponse([
                'error' => 'There is already a player with that email'
            ], 409);
        }

        if ($request->get("new_username") != null) {
            $player->setUsername($request->get("new_username"));
        }

        if ($request->get("new_email") != null) {
            $player->setEmail($request->get("new_email"));
        }

        if ($request->get("new_password") != null) {
            $player->setPassword(password_hash($request->get("new_password"), PASSWORD_DEFAULT));
        }

        $entityManager->persist($player);
        $entityManager->flush();

        $result = new \stdClass();
        $result->id = $player->getId();
        $result->username = $player->getUsername();
        $result->email = $player->getEmail();
        $result->created_at = $player->getRegDate();

        return new JsonResponse($result, 200);
    }

    function deletePlayer(ManagerRegistry $doctrine, Request $request)
    {
        $id = $request->get('id');

        $entityManager = $doctrine->getManager();
        $player = $entityManager->getRepository(Player::class)->find($id);

        if ($player == null) {
            return new JsonResponse([
                'error' => 'No player found for id ' . $id
            ], 404);
        }

        if (!password_verify($request->get("password"), $player->getPassword())) {
            return new JsonResponse([
                'error' => 'Wrong password'
            ], 401);
        }

        $entityManager->remove($player);
        $entityManager->flush();

        return new JsonResponse(null, 204);
    }
}
