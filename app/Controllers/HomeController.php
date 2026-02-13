<?php

namespace App\Controllers;

use App\Models\SiteStat;
use App\Services\ContactMessageService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class HomeController extends BaseController
{

    private ContactMessageService $contactMessageService;

    public function about(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'pages/about.twig');
    }


    public function home(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'pages/home.twig');
    }


    public function contact(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'pages/contact.twig');
    }


    public function terms(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'pages/terms.twig');
    }


    public function privacy(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'pages/privacy.twig');
    }

    

    public function developer(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'pages/developer.twig');
    }

     /**
     * Display contact page
     */
    public function saveContact(Request $request, Response $response)
    {
        return $this->render($request, $response,'pages/contact-success.twig', $this->contactMessageService->saveMessage($request));
    }

}
