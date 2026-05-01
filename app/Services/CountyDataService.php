<?php

namespace App\Services;

class CountyDataService
{
    protected array $counties = [];

    public function __construct()
    {
        $file = __DIR__ . '/../Data/kenya-counties.json';
        $this->counties = json_decode(file_get_contents($file), true);
    }

    public function getCounties(): array
    {
        return array_keys($this->counties);
    }

    public function getSubCounties(string $county): array
    {
        return $this->counties[$county] ?? [];
    }

    public function getAllData(): array
    {
        return $this->counties;
    }
}