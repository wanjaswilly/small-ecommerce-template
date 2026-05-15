<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';
    public $timestamps = true;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group'
    ];

    /**
     * Get a setting value by key
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value
     */
    public static function setValue(string $key, $value, string $group = 'general', string $type = 'string')
    {
        $setting = static::firstOrNew(['key' => $key]);
        $setting->value = $value;
        $setting->group = $group;
        $setting->type = $type;
        $setting->save();

        return $setting;
    }

    /**
     * Get all settings as an array
     */
    public static function getAll(): array
    {
        return static::all()->pluck('value', 'key')->toArray();
    }

    /**
     * Get settings by group
     */
    public static function getByGroup(string $group): array
    {
        return static::where('group', $group)->pluck('value', 'key')->toArray();
    }

    /**
     * Get settings with default values
     */
    public static function getWithDefaults(): array
    {
        $settings = static::getAll();
        return array_merge(static::getDefaultSettings(), $settings);
    }

    /**
     * Get OCR credentials for a specific provider
     */
    public static function getOAuthConfig(string $provider): array
    {
        $all = static::getAll();

        return [
            'client_id'     => $all["{$provider}_client_id"]     ?? '',
            'client_secret' => $all["{$provider}_client_secret"] ?? '',
            'redirect_uri'  => $all["{$provider}_redirect_uri"]  ?? '',
        ];
    }

    /**
     * Get payment configuration for a specific method
     */
    public static function getPaymentConfig(string $method): array
    {
        $paymentSettings = static::getByGroup('payment');

        # Method-specific configuration from JSON
        $paymentMethods = json_decode($paymentSettings['payment_methods'] ?? '{}', true) ?? [];

        return $paymentMethods[$method] ?? [];
    }

    /**
     * Get all available payment methods with their configurations
     */
    public static function getPaymentMethods(): array
    {
        $paymentSettings = static::getByGroup('payment');

        # Get enabled methods from settings
        $enabledMethods = [];

        if (filter_var($paymentSettings['mpesa_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
            $enabledMethods[] = 'mpesa';
        }
        if (filter_var($paymentSettings['pesapal_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
            $enabledMethods[] = 'pesapal';
        }

        if (filter_var($paymentSettings['cash_on_delivery'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
            $enabledMethods[] = 'cash_on_delivery';
        }

        if (filter_var($paymentSettings['card_payments'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $enabledMethods[] = 'card';
        }

        # Get detailed configuration for each method
        $methods = [];
        $paymentMethodsConfig = json_decode($paymentSettings['payment_methods'] ?? '{}', true) ?? [];

        foreach ($enabledMethods as $method) {
            $methods[$method] = $paymentMethodsConfig[$method] ?? [
                'enabled' => true,
                'name' => ucfirst(str_replace('_', ' ', $method)),
                'description' => self::getPaymentMethodDescription($method)
            ];
        }

        return $methods;
    }

    /**
     * Update payment method configuration
     */
    public static function updatePaymentMethod(string $method, array $config): bool
    {
        try {
            $paymentSettings = static::getByGroup('payment');
            $paymentMethods = json_decode($paymentSettings['payment_methods'] ?? '{}', true) ?? [];

            $paymentMethods[$method] = array_merge($paymentMethods[$method] ?? [], $config);

            static::setValue('payment_methods', json_encode($paymentMethods), 'payment', 'json');

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Initialize payment methods configuration
     */
    public static function initializePaymentMethods(): void
    {
        $defaultPaymentMethods = [
            'mpesa' => [
                'enabled' => true,
                'name' => 'M-Pesa',
                'description' => 'Pay via M-Pesa STK Push',
                'environment' => $_ENV['MPESA_ENVIRONMENT'] ?? 'sandbox',
                'consumer_key' => $_ENV['MPESA_CONSUMER_KEY'] ?? '',
                'consumer_secret' => $_ENV['MPESA_CONSUMER_SECRET'] ?? '',
                'shortcode' => $_ENV['MPESA_SHORTCODE'] ?? '',
                'passkey' => $_ENV['MPESA_PASSKEY'] ?? '',
                'callback_url' => $_ENV['MPESA_CALLBACK_URL'] ?? '',
            ],
            'pesapal' => [
                'enabled' => true,
                'name' => 'Pesa Pal',
                'description' => 'Pay via Pesapal Mpesa STK Push',
                'environment' => $_ENV['PESAPAL_ENVIRONMENT'] ?? 'sandbox',
                'consumer_key' => $_ENV['PESAPAL_CONSUMER_KEY'] ?? '',
                'consumer_secret' => $_ENV['PESAPAL_CONSUMER_SECRET'] ?? '',
                'callback_url' => $_ENV['PESAPAL_CALLBACK_URL'] ?? '',
            ],
            'cash_on_delivery' => [
                'enabled' => true,
                'name' => 'Cash on Delivery',
                'description' => 'Pay when you receive your order',
                'fee' => 0,
            ],
            'card' => [
                'enabled' => false,
                'name' => 'Credit/Debit Card',
                'description' => 'Pay with your credit or debit card',
            ]
        ];

        static::setValue('payment_methods', json_encode($defaultPaymentMethods), 'payment', 'json');
    }

    private static function getPaymentMethodDescription(string $method): string
    {
        $descriptions = [
            'mpesa' => 'Pay via M-Pesa STK Push',
            'pesapal' => 'Pay via Pesapal M-Pesa STK Push',
            'cash_on_delivery' => 'Pay when you receive your order',
            'card' => 'Pay with your credit or debit card'
        ];

        return $descriptions[$method] ?? 'Payment method';
    }

    /**
     * Default settings structure with ENV fallbacks
     */
    public static function getDefaultSettings(): array
    {
        return [

            # General Settings
            'store_name' => $_ENV['STORE_NAME'] ?? 'Small Ecommerce',
            'store_email' => $_ENV['STORE_EMAIL'] ?? 'info@smallecommerce.co.ke',
            'store_phone' => $_ENV['STORE_PHONE'] ?? '+254 7414 00 006',
            'store_active' => filter_var($_ENV['STORE_ACTIVE'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'currency' => $_ENV['STORE_CURRENCY'] ?? 'KES',

            # Store Information
            'store_address' => $_ENV['STORE_ADDRESS'] ?? 'Ngara Road, Next to Ngara Market',
            'store_city' => $_ENV['STORE_CITY'] ?? 'Nairobi',
            'opening_hours' => $_ENV['OPENING_HOURS'] ?? 'Monday - Saturday: 8:00 AM - 6:00 PM',
            'store_description' => $_ENV['STORE_DESCRIPTION'] ?? 'Your trusted partner for quality electronics and car parts with free CBD delivery.',

            # Shipping & Delivery
            'free_cbd_delivery' => filter_var($_ENV['FREE_CBD_DELIVERY'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'cbd_delivery_fee' => (float) ($_ENV['CBD_DELIVERY_FEE'] ?? 0),
            'outside_cbd_delivery_fee' => (float) ($_ENV['OUTSIDE_CBD_DELIVERY_FEE'] ?? 300),
            'delivery_time' => $_ENV['DELIVERY_TIME'] ?? '1-2 business days',

            # Payment Methods
            'mpesa_enabled' => filter_var($_ENV['MPESA_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'pesapal_enabled' => filter_var($_ENV['PESAPAL_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'cash_on_delivery' => filter_var($_ENV['CASH_ON_DELIVERY'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'card_payments' => filter_var($_ENV['CARD_PAYMENTS'] ?? false, FILTER_VALIDATE_BOOLEAN),

            # Notifications
            'email_notifications' => filter_var($_ENV['EMAIL_NOTIFICATIONS'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'sms_notifications' => filter_var($_ENV['SMS_NOTIFICATIONS'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'low_stock_alerts' => filter_var($_ENV['LOW_STOCK_ALERTS'] ?? true, FILTER_VALIDATE_BOOLEAN),

            # SEO & Social
            'meta_description' => $_ENV['META_DESCRIPTION'] ?? 'Premium products for you',
            'facebook_url' => $_ENV['FACEBOOK_URL'] ?? 'https:#facebook.com/smallecommerce',
            'instagram_handle' => $_ENV['INSTAGRAM_HANDLE'] ?? '@smallecoomerce',
            'twitter_handle' => $_ENV['TWITTER_HANDLE'] ?? '@smallecommerce',

            # Additional ENV-based settings
            'admin_email' => $_ENV['ADMIN_EMAIL'] ?? 'admin@smallecommerce.co.ke',
            'support_phone' => $_ENV['SUPPORT_PHONE'] ?? '+254 7414 00 006',
            'store_country' => $_ENV['STORE_COUNTRY'] ?? 'Kenya',
            'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Africa/Nairobi',

            # OAuth / Social Login
            'google_client_id'     => $_ENV['GOOGLE_CLIENT_ID']     ?? '',
            'google_client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? '',
            'google_redirect_uri'  => $_ENV['GOOGLE_REDIRECT_URI']  ?? 'http://localhost/login/google/callback',
            'google_enabled'       => filter_var($_ENV['GOOGLE_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),

            'apple_client_id'     => $_ENV['APPLE_CLIENT_ID']     ?? '',
            'apple_client_secret' => $_ENV['APPLE_CLIENT_SECRET'] ?? '',
            'apple_redirect_uri'  => $_ENV['APPLE_REDIRECT_URI']  ?? 'http://localhost/login/apple/callback',
            'apple_enabled'       => filter_var($_ENV['APPLE_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),

            'facebook_client_id'     => $_ENV['FB_CLIENT_ID']     ?? '',
            'facebook_client_secret' => $_ENV['FB_CLIENT_SECRET'] ?? '',
            'facebook_redirect_uri'  => $_ENV['FB_REDIRECT_URI']  ?? 'http://localhost/login/facebook/callback',
            'facebook_enabled'       => filter_var($_ENV['FACEBOOK_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /**
     * Initialize default settings if not exists
     */
    public static function initializeDefaults(): void
    {
        $defaults = static::getDefaultSettings();

        foreach ($defaults as $key => $value) {
            if (!static::where('key', $key)->exists()) {
                $group = static::getGroupForKey($key);
                $type = gettype($value);

                static::setValue($key, $value, $group, $type);
            }
        }

        # Initialize payment methods separately
        static::initializePaymentMethods();
    }

    /**
     * Determine group for a setting key
     */
    private static function getGroupForKey(string $key): string
    {
        $groups = [
            'store_name|store_email|store_phone|store_active|currency' => 'general',
            'mpesa_enabled|pesapal_enabled|cash_on_delivery|card_payments|payment_methods' => 'payment',
            'store_address|store_city|opening_hours|store_description' => 'store',
            'free_cbd_delivery|cbd_delivery_fee|outside_cbd_delivery_fee|delivery_time' => 'shipping',
            'mpesa_enabled|pesapal_enabled|cash_on_delivery|card_payments' => 'payment',
            'email_notifications|sms_notifications|low_stock_alerts' => 'notifications',
            'meta_description|facebook_url|instagram_handle|twitter_handle' => 'seo',
        ];

        foreach ($groups as $keys => $group) {
            if (in_array($key, explode('|', $keys))) {
                return $group;
            }
        }

        return 'general';
    }
}
