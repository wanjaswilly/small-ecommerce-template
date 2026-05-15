<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\Setting;
use Psr\Http\Message\ServerRequestInterface;
use Illuminate\Database\Capsule\Manager as DB;

class SettingsService
{
    public function allSettings(): array
    {

        # Initialize defaults if no settings exist
        if (Setting::count() === 0) {
            Setting::initializeDefaults();
        }

        $settings = Setting::getAll();
        return ['settings' => $settings];
    }

    public function updateAllSettings(ServerRequestInterface $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->getParsedBody();

            # Validate required fields
            $required = ['store_name', 'store_email', 'store_phone', 'store_address', 'store_city'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new \Exception("Field {$field} is required");
                }
            }

            # Process boolean fields
            $booleanFields = [
                'store_active',
                'free_cbd_delivery',
                'mpesa_enabled',
                'cash_on_delivery',
                'card_payments',
                'email_notifications',
                'sms_notifications',
                'low_stock_alerts',
                'google_enabled',
                'apple_enabled',
                'facebook_enabled',
            ];

            foreach ($booleanFields as $field) {
                $data[$field] = isset($data[$field]) && $data[$field] === 'on';
            }

            # Process numeric fields
            $numericFields = ['cbd_delivery_fee', 'outside_cbd_delivery_fee'];
            foreach ($numericFields as $field) {
                if (isset($data[$field])) {
                    $data[$field] = (float) $data[$field];
                }
            }

            # Save all settings
            foreach ($data as $key => $value) {
                if (in_array($key, array_keys(Setting::getDefaultSettings()))) {
                    Setting::setValue($key, $value);
                }
            }

            # update payment methods settings
            $this->updatePaymentMethods($data);

            # update OAuth / social login settings
            $this->updateOAuthSettings($data);

            DB::commit();

            # Set success message in session
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['success'] = 'Settings updated successfully!';
            }

        } catch (\Exception $e) {
            DB::rollBack();

            throw new ValidationException("Updating Settings Failed", ['error' => 'Failed to update settings: ' . $e->getMessage()]);
        }
    }



    /**
     * 
     */
    private function updatePaymentMethods(array $data): void
    {
        # Get existing payment methods configuration
        $existingPaymentMethods = json_decode(Setting::getValue('payment_methods', '{}'), true) ?? [];

        # Update M-Pesa configuration
        if (isset($existingPaymentMethods['mpesa'])) {
            $existingPaymentMethods['mpesa']['enabled'] = $data['mpesa_enabled'] ?? false;
            $existingPaymentMethods['mpesa']['environment'] = $data['mpesa_environment'] ?? 'sandbox';
            $existingPaymentMethods['mpesa']['shortcode'] = $data['mpesa_shortcode'] ?? '';
            $existingPaymentMethods['mpesa']['consumer_key'] = $data['mpesa_consumer_key'] ?? '';
            $existingPaymentMethods['mpesa']['consumer_secret'] = $data['mpesa_consumer_secret'] ?? '';
            $existingPaymentMethods['mpesa']['passkey'] = $data['mpesa_passkey'] ?? '';
            $existingPaymentMethods['mpesa']['callback_url'] = $data['mpesa_callback_url'] ?? '';

            # Only update if values are provided (not empty or placeholder)
            $mpesaFields = ['shortcode', 'consumer_key', 'consumer_secret', 'passkey'];
            foreach ($mpesaFields as $field) {
                $inputField = 'mpesa_' . $field;
                if (
                    isset($data[$inputField]) &&
                    !empty($data[$inputField]) &&
                    !str_contains($data[$inputField], 'your_')
                ) {
                    $existingPaymentMethods['mpesa'][$field] = $data[$inputField];
                }
            }

            # Always update callback URL if provided
            if (isset($data['mpesa_callback_url']) && !empty($data['mpesa_callback_url'])) {
                $existingPaymentMethods['mpesa']['callback_url'] = $data['mpesa_callback_url'];
            }
        }
        # Update Pesapal configuration
        if (isset($existingPaymentMethods['pesapal'])) {
            $existingPaymentMethods['pesapal']['enabled'] = $data['pesa_enabled'] ?? false;
            $existingPaymentMethods['pesapal']['environment'] = $data['pesapal_environment'] ?? 'sandbox';
            $existingPaymentMethods['pesapal']['consumer_key'] = $data['pesapal_consumer_key'] ?? '';
            $existingPaymentMethods['pesapal']['consumer_secret'] = $data['pesapal_consumer_secret'] ?? '';
            $existingPaymentMethods['pesapal']['callback_url'] = $data['pesapal_callback_url'] ?? '';

            # Only update if values are provided (not empty or placeholder)
            $mpesaFields = ['consumer_key', 'consumer_secret'];
            foreach ($mpesaFields as $field) {
                $inputField = 'pesapal_' . $field;
                if (
                    isset($data[$inputField]) &&
                    !empty($data[$inputField]) &&
                    !str_contains($data[$inputField], 'your_')
                ) {
                    $existingPaymentMethods['pesapal'][$field] = $data[$inputField];
                }
            }

            # Always update callback URL if provided
            if (isset($data['pesapal_callback_url']) && !empty($data['pesapal_callback_url'])) {
                $existingPaymentMethods['pesapal']['callback_url'] = $data['pesapal_callback_url'];
            }
        }

        # Update Cash on Delivery configuration
        if (isset($existingPaymentMethods['cash_on_delivery'])) {
            $existingPaymentMethods['cash_on_delivery']['enabled'] = $data['cash_on_delivery'] ?? false;
            $existingPaymentMethods['cash_on_delivery']['fee'] = (int) ($data['cod_fee'] ?? 0);
        }

        # Update Card payments configuration
        if (isset($existingPaymentMethods['card'])) {
            $existingPaymentMethods['card']['enabled'] = $data['card_payments'] ?? false;
        }

        # Save updated payment methods configuration
        Setting::setValue('payment_methods', json_encode($existingPaymentMethods), 'payment', 'json');
    }

    /**
     * Update OAuth / social login credentials from admin form submission
     */
    private function updateOAuthSettings(array $data): void
    {
        $providers = ['google', 'apple', 'facebook'];
        $fields    = ['client_id', 'client_secret', 'redirect_uri'];

        foreach ($providers as $provider) {
            foreach ($fields as $field) {
                $inputField = "{$provider}_{$field}";
                if (isset($data[$inputField])) {
                    Setting::setValue($inputField, trim($data[$inputField]), 'oauth');
                }
            }
        }
    }

    /**
     * API or JS Ajax requests: Get settings
     */
    public function getSettings(ServerRequestInterface $request): array
    {
        try {
            $settings = Setting::getWithDefaults();

            return [
                'success' => true,
                'data' => $settings,
                'message' => 'Settings retrieved successfully'
            ];
        } catch (\Exception $e) {
            throw new ValidationException("loading Settings Failed", ['error' => 'Failed to retrieve setting: ' . $e->getMessage()]);
        }
    }

    /**
     * API: Update specific setting
     */
    public function updateSetting(ServerRequestInterface $request): array
    {
        try {
            $data = $request->getParsedBody();
            $key = $data['key'] ?? null;
            $value = $data['value'] ?? null;

            if (!$key) {
                throw new ValidationException('Setting key is required', ['error' => 'Supply altleast one key:pair setting to update']);
            }

            Setting::setValue($key, $value);

            return [
                'success' => true,
                'message' => 'Setting updated successfully'
            ];
        } catch (\Exception $e) {
            throw new ValidationException("Updating Setting Failed", ['error' => 'Failed to update setting: ' . $e->getMessage()]);
        }
    }

    /**
     * Reset settings to defaults
     */
    public function reset(): void
    {
        DB::beginTransaction();

        try {
            # Delete all existing settings
            Setting::truncate();

            # Initialize defaults
            Setting::initializeDefaults();

            DB::commit();

            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['success'] = 'Settings reset to defaults successfully!';
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ValidationException("Failed Ressetting Settings",['error' => 'Failed to reset settings: ' . $e->getMessage()] );
        }
    }

}