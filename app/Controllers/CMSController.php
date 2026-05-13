<?php

namespace App\Controllers;

use App\Services\CMSService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CMSController extends BaseController
{
    private CMSService $cmsService;

    public function __construct()
    {
        $this->cmsService = new CMSService();
    }

    public function index(Request $request, Response $response): Response
    {
        $pages = $this->cmsService->getAllPages();
        return $this->render($request, $response, 'admin/cms/index.twig', ['pages' => $pages]);
    }

    public function create(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'admin/cms/form.twig');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $this->cmsService->createPage($data);
        $_SESSION['success'] = 'Page created successfully';
        return $this->redirect($response, '/admin/cms');
    }

    public function edit(Request $request, Response $response, $args): Response
    {
        $page = \App\Models\Page::findOrFail($args['id']);
        return $this->render($request, $response, 'admin/cms/form.twig', ['page' => $page]);
    }

    public function update(Request $request, Response $response, $args): Response
    {
        $data = $request->getParsedBody();
        $this->cmsService->updatePage($args['id'], $data);
        $_SESSION['success'] = 'Page updated successfully';
        return $this->redirect($response, '/admin/cms');
    }

    public function destroy(Request $request, Response $response, $args): Response
    {
        $this->cmsService->deletePage($args['id']);
        $_SESSION['success'] = 'Page deleted successfully';
        return $this->redirect($response, '/admin/cms');
    }
}