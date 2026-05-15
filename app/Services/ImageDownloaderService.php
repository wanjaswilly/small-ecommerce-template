<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ImageDownloaderService
{
    protected Client $http;

    protected string $baseUploadDir;

    public function __construct()
    {
        $this->http = new Client([
            'timeout' => 30,
            'allow_redirects' => true,
            'verify' => false,
        ]);

        # $_SERVER['DOCUMENT_ROOT'] is not populated when running from CLI
        # (e.g. the database seeder).  Fall back to the project's public/ dir
        # so file writes work in both web and CLI contexts.
        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 2) . '/public';

        $this->baseUploadDir = rtrim(
            rtrim($docRoot, '/') . '/images/products/',
            '/'
        ) . '/';
    }

    /**
     * Download an image from a remote URL and save it into
     * /public/images/products/{$categorySlug}/.
     *
     * The saved filename is derived from the product name (slugified) with a
     * uniqid() suffix to guarantee uniqueness across re-seeds.
     *
     * @return string  Relative URL path (e.g. "/images/products/electronics/samsung-galaxy-a54_685f3a1c.jpg")
     */
    public function downloadAndSave(string $imageUrl, string $categorySlug, string $productSlug): ?string
    {
        try {
            $response = $this->http->get($imageUrl);
            $mime = $response->getHeaderLine('Content-Type') ?: 'image/jpeg';
            $extension = $this->mimeToExtension($mime);
            if ($extension === 'bin') {
                $pathInfo = parse_url($imageUrl, PHP_URL_PATH);
                $extension = pathinfo($pathInfo ?: '', PATHINFO_EXTENSION) ?: 'jpg';
            }

            $safeSlug = preg_replace('/[^a-z0-9]+/i', '-', $productSlug);
            $safeSlug = trim($safeSlug, '-');
            if ($safeSlug === '') {
                $safeSlug = 'product';
            }

            $filename  = $safeSlug . '_' . uniqid() . '.' . $extension;
            $uploadDir = $this->baseUploadDir . $categorySlug . '/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filepath = $uploadDir . $filename;
            file_put_contents($filepath, $response->getBody()->getContents());

            return '/images/products/' . $categorySlug . '/' . $filename;
        } catch (GuzzleException $e) {
            return null;
        }
    }

    /**
     * Map a MIME type to a file extension.
     */
    protected function mimeToExtension(string $mime): string
    {
        return match ($mime) {
            'image/jpeg'  => 'jpg',
            'image/png'   => 'png',
            'image/gif'   => 'gif',
            'image/webp'  => 'webp',
            'image/svg+xml' => 'svg',
            default       => 'bin',
        };
    }
}
