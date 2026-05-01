<?php

namespace App\Controllers;

use App\Models\Coupon;
use App\Services\CouponService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CouponController extends BaseController
{
    private CouponService $couponService;

    public function __construct()
    {
        $this->couponService = new CouponService();
    }

    public function index(Request $request, Response $response): Response
    {
        $coupons = $this->couponService->getAll();
        return $this->render($request, $response, 'admin/coupons/index.twig', ['coupons' => $coupons]);
    }

    public function create(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'admin/coupons/form.twig');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $data['start_date'] = $data['start_date'] ?? null;
        $data['end_date'] = $data['end_date'] ?? null;
        $data['is_active'] = isset($data['is_active']);

        $this->couponService->create($data);
        $_SESSION['success'] = ['Coupon created successfully'];
        return $this->redirect($response, '/admin/coupons');
    }

    public function edit(Request $request, Response $response, $args): Response
    {
        $coupon = Coupon::findOrFail($args['id']);
        return $this->render($request, $response, 'admin/coupons/form.twig', ['coupon' => $coupon]);
    }

    public function update(Request $request, Response $response, $args): Response
    {
        $coupon = Coupon::findOrFail($args['id']);
        $data = $request->getParsedBody();
        $data['start_date'] = $data['start_date'] ?? null;
        $data['end_date'] = $data['end_date'] ?? null;
        $data['is_active'] = isset($data['is_active']);

        $this->couponService->update($coupon, $data);
        $_SESSION['success'] = ['Coupon updated successfully'];
        return $this->redirect($response, '/admin/coupons');
    }

    public function destroy(Request $request, Response $response, $args): Response
    {
        $coupon = Coupon::findOrFail($args['id']);
        $this->couponService->delete($coupon);
        $_SESSION['success'] = ['Coupon deleted successfully'];
        return $this->redirect($response, '/admin/coupons');
    }
}