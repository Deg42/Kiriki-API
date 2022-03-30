<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Game;
use App\Entity\Player;

class GameController extends AbstractController
{
    function getAllGames(ManagerRegistry $doctrine)
    {
        $entityManager = $doctrine->getManager();
        $games = $entityManager->getRepository(Game::class)->findAll();

        if ($games == null) {
            return new JsonResponse([
                'error' => 'No games found'
            ], 404);
        }

        $results  = new \stdClass();
        $results->count = count($games);
        $results->results = array();

        foreach ($games as $game) {
            $result = new \stdClass();
            $result->id = $game->getId();
            $result->host = $game->getHostId()->getUsername();
            $result->winner = $game->getWinnerId();
            $result->name = $game->getName();
            $result->created_at = $game->getDate();

            $result->players = new \stdClass();
            $result->players->count = count($game->getPlayers());
            $result->players->results = array();

            foreach ($game->getPlayers() as $player) {
                $result->players->results[] = $this->generateUrl('api_get_players', [
                    'id' => $player->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            }

            array_push($results->results, $result);
        }

        return new JsonResponse($results, 200);
    }

    function getGame(ManagerRegistry $doctrine, Request $request)
    {
        $id = $request->get('id');

        $entityManager = $doctrine->getManager();
        $game = $entityManager->getRepository(Game::class)->find($id);

        if ($game == null) {
            return new JsonResponse([
                'error' => 'No game found for id ' . $id
            ], 404);
        }

        $result = new \stdClass();
        $result->id = $game->getId();
        $result->name = $game->getName();
        $result->host = $game->getHostId()->getUsername();
        $result->winner = $game->getWinnerId();
        $result->created_at = $game->getDate();
        
        $result->players = new \stdClass();
        $result->players->count = count($game->getPlayers());
        $result->players->results = array();

        foreach ($game->getPlayers() as $player) {
            $result->players->results[] = $this->generateUrl('api_get_players', [
                'id' => $player->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }
        
        return new JsonResponse($result, 200);
    }

    function postGame(ManagerRegistry $doctrine, Request $request)
    {
        $entityManager = $doctrine->getManager();

        $gameByName = $entityManager->getRepository(Game::class)->findOneBy(['name' => $request->get("name")]);

        $host = $entityManager->getRepository(Player::class)->findOneBy(['username' => $request->get("host_username")]);

        if ($gameByName) {
            return new JsonResponse([
                'error' => 'There is already a game with that name'
            ], 409);
        }

        if ($host == null) {
            return new JsonResponse([
                'error' => 'There is no player with the username ' . $request->get("host_username")
            ], 404);
        }

        if (!password_verify($request->get("host_password"), $host->getPassword())) {
            return new JsonResponse([
                'error' => 'Wrong password'
            ], 401);
        }

        $game = new Game();
        $game->setName($request->get('name'));
        $game->setPassword(password_hash($request->get("password"), PASSWORD_DEFAULT));
        $game->setDate(new \DateTime());
        $game->setHostId($host);

        $entityManager->persist($game);
        $entityManager->flush();

        $result = new \stdClass();
        $result->id = $game->getId();
        $result->host = $game->getHostId()->getUsername();
        $result->name = $game->getName();
        $result->created_at = $game->getDate();

        return new JsonResponse($result, 201);
    }
}
