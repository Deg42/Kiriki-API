<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Player;

class ApiController extends AbstractController
{

    function index()
    {
        $result = array();
        $result['players'] = $this->generateUrl(
            'api_get_players',
            array(),
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $result['games'] = $this->generateUrl(
            'api_get_games',
            array(),
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        return new JsonResponse($result);
    }

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
            $result->created_at = $player->getRegistrationDate();

            $result->games_played = new \stdClass();
            $result->games_played->count = count($player->getPlayedGames());
            $result->games_played->results = array();

            $result->games_hosted = new \stdClass();
            $result->games_hosted->count = count($player->getHostedGames());
            $result->games_hosted->results = array();

            $result->games_won = new \stdClass();
            $result->games_won->count = count($player->getWonGames());
            $result->games_won->results = array();

            foreach ($player->getPlayedGames() as $played_game) {
                $result->games_played->results[] = $this->generateUrl('api_get_games', [
                    'id' => $played_game->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);
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
        $player->setRegistrationDate(new \DateTime());

        $entityManager->persist($player);
        $entityManager->flush();

        $result = new \stdClass();
        $result->id = $player->getId();
        $result->username = $player->getUsername();
        $result->email = $player->getEmail();
        $result->created_at = $player->getRegistrationDate();

        return new JsonResponse($result, 201);
    }

    function putPlayer(ManagerRegistry $doctrine, Request $request)
    {

        $entityManager = $doctrine->getManager();
        $player = $entityManager->getRepository(Player::class)->find($request->get('id'));
 
        $playerByUser = $entityManager->getRepository(Player::class)->findOneBy(['username' => $request->get("username")]);
        $playerByEmail = $entityManager->getRepository(Player::class)->findOneBy(['email' => $request->get("email")]);

        if ($player == null) {
            return new JsonResponse([
                'error' => 'Player not found'
            ], 404);
        }

        if ($playerByUser || $playerByEmail) {
            return new JsonResponse([
                'error' => 'There is already a player with that email or username'
            ], 409);
        }

        if (!password_verify($request->get("old_password"), $player->getPassword())) {
            return new JsonResponse([
                'error' => 'Wrong password'
            ], 401);
        }

        $player->setUsername($request->get('username'));
        $player->setEmail($request->get('email'));
        $player->setPassword(password_hash($request->get("new_password"), PASSWORD_DEFAULT));

        $entityManager->flush();

        $result = new \stdClass();
        $result->id = $player->getId();
        $result->username = $player->getUsername();
        $result->email = $player->getEmail();
        $result->created_at = $player->getRegistrationDate();

        return new JsonResponse($result, 200);
    }

    function deletePlayer(ManagerRegistry $doctrine, Request $request)
    {
        $entityManager = $doctrine->getManager();
        $player = $entityManager->getRepository(Player::class)->find($request->get('id'));

        if ($player == null) {
            return new JsonResponse([
                'error' => 'No player found for that id'
            ], 404);
        }

        $entityManager->remove($player);
        $entityManager->flush();

        return new JsonResponse(null, 204);
    }
}
