<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Player;

class FrontPlayerController extends AbstractController
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

        return new JsonResponse(['success' => 'Player registered successfully'], 200);
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

        return new JsonResponse(['success' => 'Account deleted successfully'], 200);
    }
    
}
