<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type'];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value, string $type = 'string'): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type]
        );
        Cache::forget("setting.{$key}");
    }

    /**
     * Get the logo URL
     */
    public static function getLogoUrl(): string
    {
        $logo = self::get('site_logo');
        
        if ($logo && Storage::disk('public')->exists($logo)) {
            return Storage::disk('public')->url($logo);
        }
        
        // Fallback to default logo
        return asset('logo.png');
    }

    /**
     * Get the site name
     */
    public static function getSiteName(): string
    {
        return self::get('site_name', config('app.name', 'LaRockaKids'));
    }
}
