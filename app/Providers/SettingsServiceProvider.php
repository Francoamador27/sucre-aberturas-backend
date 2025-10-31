<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $s = Cache::remember('app_settings', 300, fn () => Setting::query()->first());
        if (!$s) return;

        // Nombre y branding (OK)
        Config::set('app.name', $s->company_name ?? config('app.name'));
        Config::set('app.branding.logo_url', $s->logo_url ?? config('app.branding.logo_url'));

    

        // Opcional: compartir datos en vistas de mail
        view()->composer('mail::*', function ($view) use ($s) {
            $view->with('company', [
                'name'          => $s->company_name,
                'logo_url'      => $s->logo_url,
                'contact_email' => $s->contact_email,
                'phone'         => $s->phone,
                'address'       => $s->address,
                'instagram'     => $s->instagram,
                'facebook'      => $s->facebook,
                'hours'         => $s->business_hours,
            ]);
        });
    }
}
