<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class SettingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $setting = false;
        if (Schema::hasTable('settings')) {
            $setting = Setting::where('status', Setting::STATUS_ACTIVE)->first();
        }

        if ($setting) {
            $settings = $setting->settings;

            if ($settings['email']) {
                Config::set('general.email', (int) $settings['email']);
            }

            if ($settings['sms']) {
                Config::set('general.sms', (int) $settings['sms']);
            }

            if ($settings['jwt_expiration_time']) {
                Config::set('sanctum.expiration', (int) $settings['jwt_expiration_time']);
            }

            if ($settings['auth']) {
                Config::set('general.auth', $settings['auth']);
            }

            if ($settings['otp']) {
                Config::set('general.otp', $settings['otp']);
            }
        }
    }
}
