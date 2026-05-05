<?php

namespace App\Extensions;

use App\Models\Setting;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use App\Middleware\CsrfMiddleware;

class SettingsExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('setting', function($settingKey)  {
                return $this->getSettingValue($settingKey);
            }),
        ];
    }

    public function getSettingValue(string $settingKey): mixed
    {
        return Setting::getValue($settingKey);
    }
}
