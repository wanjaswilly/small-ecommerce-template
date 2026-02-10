<?php

namespace App\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use App\Middleware\CsrfMiddleware;

class CsrfExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('csrf_token', [$this, 'getCsrfToken']),
            new TwigFunction('csrf_field', [$this, 'getCsrfField'], ['is_safe' => ['html']]),
        ];
    }

    public function getCsrfToken(): string
    {
        return (new CsrfMiddleware())->getToken();
    }

    public function getCsrfField(): string
    {
        return '<input type="hidden" name="_token" value="' . $this->getCsrfToken() . '">';
    }
}
