<?php

namespace App\Services;

use App\Models\ShippingZone;
use App\Models\TaxRate;
use App\Models\Setting;

class ShippingTaxService
{
    protected array $shippingZones;
    protected array $taxRates;

    public function __construct()
    {
        $this->loadSettings();
    }

    protected function loadSettings(): void
    {
        $this->shippingZones = ShippingZone::all()->keyBy('name')->toArray();
        $this->taxRates = TaxRate::where('is_active', true)->get()->toArray();
    }

    public function calculateShipping(array $items, string $destination): array
    {
        $totalWeight = 0;
        $totalValue = 0;

        foreach ($items as $item) {
            $weight = $item['product']['weight'] ?? $item['weight'] ?? 0.5;
            $totalWeight += $weight * $item['quantity'];
            $totalValue += $item['product']['price'] * $item['quantity'];
        }

        $zone = $this->getShippingZone($destination);

        if (!$zone) {
            return [
                'fee' => 0,
                'method' => 'Standard',
                'estimated_days' => 3,
                'breakdown' => 'No zone configured for ' . $destination
            ];
        }

        $shippingFee = $this->calculateZoneShipping($zone, $totalWeight, $totalValue);

        return [
            'fee' => round($shippingFee, 2),
            'method' => $zone['shipping_method'] ?? 'Standard',
            'estimated_days' => $zone['estimated_days'] ?? 3,
            'breakdown' => $this->getShippingBreakdown($zone, $totalWeight, $totalValue)
        ];
    }

    protected function getShippingZone(string $destination): ?array
    {
        foreach ($this->shippingZones as $zone) {
            $locations = json_decode($zone['locations'] ?? '[]', true);
            if (in_array($destination, $locations)) {
                return $zone;
            }
            $pattern = $zone['name'] ?? '';
            if (stripos($destination, $pattern) !== false) {
                return $zone;
            }
        }
        return null;
    }

    protected function calculateZoneShipping(array $zone, float $weight, float $value): float
    {
        $baseFee = $zone['base_fee'] ?? 0;
        $perKg = $zone['per_kg_fee'] ?? 0;
        $freeThreshold = $zone['free_shipping_threshold'] ?? 0;

        if ($value >= $freeThreshold && $freeThreshold > 0) {
            return 0;
        }

        $fee = $baseFee + ($weight * $perKg);

        return max($fee, 0);
    }

    protected function getShippingBreakdown(array $zone, float $weight, float $value): string
    {
        $base = $zone['base_fee'] ?? 0;
        $perKg = $zone['per_kg_fee'] ?? 0;
        return "Base: " . $base . " + Weight (" . $weight . "kg x " . $perKg . ")";
    }

    public function calculateTax(float $amount, string $destination, string $taxClass = 'standard'): array
    {
        $rates = array_filter($this->taxRates, function ($rate) use ($destination, $taxClass) {
            $applicableZones = json_decode($rate['applicable_zones'] ?? '[]', true);
            if (empty($applicableZones)) {
                return true;
            }
            return in_array($destination, $applicableZones) || in_array('all', $applicableZones);
        });

        $totalTax = 0;
        $taxBreakdown = [];

        foreach ($rates as $rate) {
            if (($rate['tax_class'] ?? 'standard') === $taxClass) {
                $taxAmount = $amount * ($rate['rate'] / 100);
                $totalTax += $taxAmount;
                $taxBreakdown[] = [
                    'name' => $rate['name'],
                    'rate' => $rate['rate'],
                    'amount' => round($taxAmount, 2)
                ];
            }
        }

        return [
            'total_tax' => round($totalTax, 2),
            'tax_rate' => round(($totalTax / $amount) * 100, 2) ?: 0,
            'breakdown' => $taxBreakdown
        ];
    }

    public function getAllShippingZones(): array
    {
        return array_values($this->shippingZones);
    }

    public function getAllTaxRates(): array
    {
        return $this->taxRates;
    }
}