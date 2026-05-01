<?php

namespace App\Services;

class TranslationService
{
    protected string $defaultLocale = 'en';

    protected array $translations = [];

    public function __construct(?string $locale = null)
    {
        $this->loadTranslations($locale ?? $this->defaultLocale);
    }

    public function get(string $key, ?string $locale = null): string
    {
        $lang = $locale ?? $this->defaultLocale;
        $this->loadTranslations($lang);

        return $this->translations[$lang][$key] ?? $key;
    }

    public function setLocale(string $locale): void
    {
        $this->defaultLocale = $locale;
        $this->loadTranslations($locale);
    }

    public function getLocale(): string
    {
        return $this->defaultLocale;
    }

    protected function loadTranslations(string $locale): void
    {
        $file = __DIR__ . '/../Lang/' . $locale . '.php';
        if (!isset($this->translations[$locale])) {
            $this->translations[$locale] = file_exists($file) ? require $file : [];
        }
    }
}