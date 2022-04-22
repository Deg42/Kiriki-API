<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ApiController extends AbstractController
{
    function index()
    {
        $result = array();
        $result['players'] = $this->generateUrl(
            'admin_get_players',
            array(),
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $result['games'] = $this->generateUrl(
            'admin_get_games',
            array(),
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        return new JsonResponse($result);
    }
}
