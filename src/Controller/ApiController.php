<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ApiController extends AbstractController
{
    function index()
    {
        return new JsonResponse("Welcome to the API", 200);
    }
}
