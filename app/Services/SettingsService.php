<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    protected const CACHE_KEY = 'fintoko_system_settings_all';
    protected const CACHE_TTL = 3600; // 1 jam

    /**
     * Dapatkan nilai pengaturan berdasarkan key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->getAll();

        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        return $default;
    }

    /**
     * Set atau update nilai pengaturan tunggal.
     */
    public function set(string $key, mixed $value, string $group = 'general', ?string $type = null, bool $isPublic = false): SystemSetting
    {
        $setting = SystemSetting::where('key', $key)->first();

        if ($type === null && $setting) {
            $type = $setting->type;
        } elseif ($type === null) {
            $type = is_bool($value) ? 'boolean' : (is_numeric($value) ? 'integer' : 'string');
        }

        $stringValue = is_bool($value) ? ($value ? '1' : '0') : (string) $value;

        $setting = SystemSetting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stringValue,
                'group' => $group,
                'type' => $type,
                'is_public' => $isPublic,
            ]
        );

        $this->clearCache();

        return $setting;
    }

    /**
     * Ambil seluruh pengaturan yang dikelompokkan ke array associative [key => casted_value].
     */
    public function getAll(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return SystemSetting::all()->mapWithKeys(function ($item) {
                return [$item->key => $item->casted_value];
            })->all();
        });
    }

    /**
     * Ambil seluruh model pengaturan per grup beserta metadata-nya.
     */
    public function getByGroup(string $group): array
    {
        return SystemSetting::where('group', $group)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'key' => $item->key,
                'value' => $item->casted_value,
                'raw_value' => $item->value,
                'group' => $item->group,
                'type' => $item->type,
                'description' => $item->description,
                'is_public' => $item->is_public,
            ];
        })->all();
    }

    /**
     * Ambil seluruh pengaturan yang bersifat publik (dapat diakses frontend tanpa login).
     */
    public function getPublicSettings(): array
    {
        return Cache::remember('fintoko_public_settings', self::CACHE_TTL, function () {
            return SystemSetting::where('is_public', true)
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->key => $item->casted_value];
                })
                ->all();
        });
    }

    /**
     * Update banyak pengaturan sekaligus berdasarkan key => value.
     */
    public function updateBatch(array $settings, ?int $userId = null): void
    {
        $oldSettings = $this->getAll();
        $changed = [];

        foreach ($settings as $key => $value) {
            $setting = SystemSetting::where('key', $key)->first();
            if ($setting) {
                $oldVal = $setting->casted_value;
                $newValString = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
                $setting->update(['value' => $newValString]);

                if ($oldVal !== $setting->fresh()->casted_value) {
                    $changed[$key] = [
                        'old' => $oldVal,
                        'new' => $setting->fresh()->casted_value,
                    ];
                }
            }
        }

        $this->clearCache();

        // Catat Audit Trail
        if (! empty($changed)) {
            app(ActivityLogService::class)->log(
                action: 'settings_update',
                module: 'settings',
                description: 'Memperbarui ' . count($changed) . ' pengaturan sistem',
                properties: ['changes' => $changed]
            );
        }
    }

    /**
     * Hapus cache pengaturan.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('fintoko_public_settings');
    }
}
