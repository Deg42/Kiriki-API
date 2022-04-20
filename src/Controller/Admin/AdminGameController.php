<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Game;
use App\Entity\Player;
use App\Entity\PlayerGame;

class AdminGameController extends AbstractController
{
    function getAllGames(ManagerRegistry $doctrine, Request $request)
    {
        if ($request->get('token') != $this->getParameter('app.API_KEY')) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        $entityManager = $doctrine->getManager();
        $games = $entityManager->getRepository(Game::class)->findAll();

        $results  = new \stdClass();
        $results->count = count($games);
        $results->results = array();

        foreach ($games as $game) {
            $result = new \stdClass();
            $result->id = $game->getId();
            $result->host = $game->getHost()->getUsername();
            $result->winner = $game->getWinner() ? $game->getWinner()->getUsername() : null;
            $result->name = $game->getName();
            $result->created_at = $game->getDate();

            $result->players = new \stdClass();
            $result->players->count = count($game->getPlayers() ?? []);
            $result->players->results = array();

            if ($game->getPlayers()) {
                foreach ($game->getPlayers() as $playerInGame) {
                    $result->players->results[] = $this->generateUrl('admin_get_player', [
                        'id' => $playerInGame->getPlayer()->getId(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL);
                }
            }


            array_push($results->results, $result);
        }

        return new JsonResponse($results, 200);
    }

    function getGame(ManagerRegistry $doctrine, Request $request)
    {
        if ($request->get('token') != $this->getParameter('app.API_KEY')) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        $id = $request->get('id');

        $entityManager = $doctrine->getManager();
        $game = $entityManager->getRepository(Game::class)->find($id);

        if (is_null($game)) {
            return new JsonResponse([
                'error' => 'Game not found'
            ], 404);
        }

        $result = new \stdClass();
        $result->name = $game->getName();
        $result->host = $game->getHost()->getUsername();
        $result->winner = $game->getWinner() ? $game->getWinner()->getUsername() : null;
        $result->created_at = $game->getDate();

        $result->players = new \stdClass();
        $result->players->count = count($game->getPlayers() ?? []);
        $result->players->results = array();

        if ($game->getPlayers()) {
            foreach ($game->getPlayers() as $playerInGame) {
                $result->players->results[] = $this->generateUrl('admin_get_player', [
                    'id' => $playerInGame->getPlayer()->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            }
        }

        return new JsonResponse($result, 200);
    }

    function postGame(ManagerRegistry $doctrine, Request $request)
    {
        if ($request->get('token') != $this->getParameter('app.API_KEY')) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        $name = $request->get("name");
        $password = $request->get("password");
        $hostId = $request->get("host_id");

        $entityManager = $doctrine->getManager();

        $gameByName = $entityManager->getRepository(Game::class)->findOneBy(['name' => $name]);

        $host = $entityManager->getRepository(Player::class)->find($hostId);

        if ($gameByName) {
            return new JsonResponse(['error' => 'There is already a game with that name'], 409);
        }

        if (is_null($host)) {
            return new JsonResponse(['error' => 'Host not found'], 404);
        }

        $game = new Game();
        $game->setName($name);
        $game->setPassword(password_hash($password, PASSWORD_DEFAULT));
        $game->setDate(new \DateTime());
        $game->setHost($host);
        $game->setIsInProgress(false);

        $playerInGame = new PlayerGame();
        $playerInGame->setPlayer($host);
        $playerInGame->setGame($game);
        $playerInGame->setTurnOrder(0);
        $playerInGame->setPoints(9);
        $playerInGame->setIsTurn(true);

        $entityManager->persist($game);
        $entityManager->persist($playerInGame);
        $entityManager->flush();

        $result = new \stdClass();
        $result->id = $game->getId();
        $result->host = $this->generateUrl('admin_get_players', [
            'id' => $host->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $result->name = $game->getName();
        $result->created_at = $game->getDate();

        return new JsonResponse($result, 201);
    }

    function patchGame(ManagerRegistry $doctrine, Request $request)
    {
        if ($request->get('token') != $this->getParameter('app.API_KEY')) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        $id = $request->get('id');
        $newName = $request->get("new_name");
        $newPassword = $request->get("new_password");

        $entityManager = $doctrine->getManager();
        $game = $entityManager->getRepository(Game::class)->find($id);

        $gameByName = $entityManager->getRepository(Game::class)->findOneBy(['name' => $newName]);

        if (is_null($game)) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }
        if ($gameByName) {
            return new JsonResponse(['error' => 'There is already a game with that name'], 409);
        }
        if (is_null($newName) && is_null($newPassword)) {
            return new JsonResponse(['error' => 'No data to update'], 400);
        }

        if ($newName) {
            $game->setName($newName);
        }
        if ($newPassword) {
            $game->setPassword(password_hash($newPassword, PASSWORD_DEFAULT));
        }

        $entityManager->persist($game);
        $entityManager->flush();

        $result = new \stdClass();
        $result->id = $game->getId();
        $result->host = $this->generateUrl('admin_get_players', [
            'id' => $game->getHost()->getId()
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $result->name = $game->getName();
        $result->winner =
            $game->getWinner()
            ? $this->generateUrl('admin_get_players', ['id' => $game->getWinner()->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
            : null;
        $result->created_at = $game->getDate();

        $result->players = new \stdClass();
        $result->players->count = count($game->getPlayers() ?? []);
        $result->players->results = array();

        if ($game->getPlayers()) {
            foreach ($game->getPlayers() as $player) {
                $result->players->results[] = $this->generateUrl('admin_get_players', [
                    'id' => $player->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            }
        }

        return new JsonResponse($result, 200);
    }

    function deleteGame(ManagerRegistry $doctrine, Request $request)
    {
        if ($request->get('token') != $this->getParameter('app.API_KEY')) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        $id = $request->get('id');

        $entityManager = $doctrine->getManager();
        $game = $entityManager->getRepository(Game::class)->find($id);

        if (is_null($game)) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        $entityManager->remove($game);
        $entityManager->flush();

        return new JsonResponse(null, 204);
    }
}
