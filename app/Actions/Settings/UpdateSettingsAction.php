<?php

namespace App\Actions\Settings;

use App\Actions\BaseAction;
use App\DTOs\Settings\UpdateSettingsDTO;
use App\Models\Setting;

class UpdateSettingsAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = Setting::class;

    protected function handle(mixed $input): mixed
    {
        if (! $input instanceof UpdateSettingsDTO) {
            throw new \InvalidArgumentException('UpdateSettingsAction requires an UpdateSettingsDTO.');
        }

        Setting::query()
            ->select(['id', 'name'])
            ->orderBy('id')
            ->get()
            ->each(function (Setting $setting) use ($input): void {
                if (! array_key_exists($setting->name, $input->settings)) {
                    return;
                }

                $setting->update([
                    'content' => $input->settings[$setting->name] ?? '',
                ]);
            });
    }
}
