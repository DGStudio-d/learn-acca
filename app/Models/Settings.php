<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    protected $casts = [
        'value' => 'string',
    ];

    /**
     * Scope to get a setting by key.
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    /**
     * Scope to get settings by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get a setting value by key.
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = static::byKey($key)->first();
        
        if (!$setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value by key.
     */
    public static function setValue(string $key, $value, string $type = 'string', string $description = null): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => (string) $value,
                'type' => $type,
                'description' => $description,
            ]
        );
    }

    /**
     * Cast the value to the appropriate type.
     */
    protected static function castValue($value, string $type)
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'array' => json_decode($value, true),
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Get guest access settings.
     */
    public static function getGuestAccessSettings(): array
    {
        return [
            'allow_guest_languages' => static::getValue('allow_guest_languages', false),
            'allow_guest_teachers' => static::getValue('allow_guest_teachers', false),
            'allow_guest_quizzes' => static::getValue('allow_guest_quizzes', false),
        ];
    }

    /**
     * Check if guest access is allowed for a specific feature.
     */
    public static function isGuestAccessAllowed(string $feature): bool
    {
        $key = "allow_guest_{$feature}";
        return static::getValue($key, false);
    }

    /**
     * Update guest access settings.
     */
    public static function updateGuestAccessSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            if (str_starts_with($key, 'allow_guest_')) {
                static::setValue($key, $value, 'boolean', "Allow guest access to {$key}");
            }
        }
    }
}