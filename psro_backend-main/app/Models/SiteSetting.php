<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'description'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($setting->value) ? +$setting->value : $default,
            'json' => json_decode($setting->value, true) ?? $default,
            default => $setting->value,
        };
    }

    public static function setValue(string $key, mixed $value, ?string $type = null): void
    {
        $setting = static::where('key', $key)->first();

        $storeValue = is_array($value) ? json_encode($value) : (string) $value;

        if ($setting) {
            $setting->update(array_filter([
                'value' => $storeValue,
                'type' => $type,
            ], fn ($v) => $v !== null));
        } else {
            static::create([
                'key' => $key,
                'value' => $storeValue,
                'type' => $type ?? 'text',
                'group' => 'maintenance',
            ]);
        }
    }
}
