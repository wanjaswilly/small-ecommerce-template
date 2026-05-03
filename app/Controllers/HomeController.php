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

    public function track(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'pages/track.twig');
    }

    public function trackOrder(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $orderNumber = $data['order_number'] ?? '';
        $email = $data['email'] ?? '';

        if (empty($orderNumber) || empty($email)) {
            $_SESSION['error'] = 'Please provide both order number and email address';
            return $this->redirect($response, '/track');
        }

        $orderService = new \App\Services\OrderService();
        $order = $orderService->lookupOrder($orderNumber, $email);

        if (!$order) {
            $_SESSION['error'] = 'Order not found. Please check your order number and email address.';
            return $this->redirect($response, '/track');
        }

        // Calculate status index for timeline
        $statusOrder = ['pending' => 1, 'confirmed' => 2, 'processing' => 3, 'shipped' => 4, 'delivered' => 5];
        $statusIndex = $statusOrder[$order['status']] ?? 1;

        return $this->render($request, $response, 'pages/track.twig', [
            'order' => $order,
            'status_index' => $statusIndex,
        ]);
    }

    public function subscribe(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $email = trim($data['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Please provide a valid email address';
            return $this->json($response, ['success' => false, 'message' => 'Invalid email address']);
        }

        $subscriber = \App\Models\NewsletterSubscriber::where('email', $email)->first();

        if ($subscriber) {
            if ($subscriber->unsubscribed_at) {
                // Re-subscribe
                $subscriber->update([
                    'subscribed_at' => now(),
                    'unsubscribed_at' => null,
                ]);
            } else {
                // Already subscribed
                return $this->json($response, ['success' => false, 'message' => 'You are already subscribed to our newsletter']);
            }
        } else {
            // New subscription
            \App\Models\NewsletterSubscriber::create([
                'email' => $email,
                'subscribed_at' => now(),
            ]);
        }

        return $this->json($response, ['success' => true, 'message' => 'Thank you for subscribing to our newsletter!']);
    }

}
