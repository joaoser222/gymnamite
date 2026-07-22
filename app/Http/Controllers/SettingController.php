<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Setting;
use App\Traits\HasModule;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    use HasModule;

    protected function accessModule(): AccessModule
    {
        return AccessModule::SETTING;
    }

    protected function modelClass(): string
    {
        return Setting::class;
    }

    public function index(): Response
    {
        $this->authorizeAccess(AccessAction::VIEW);

        return Inertia::render('settings/Details', [
            'settings' => $this->settingsPayload(),
            'routes' => [
                'update' => route('settings.update'),
            ],
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $this->authorizeAccess(AccessAction::UPDATE);

        $validated = $request->validated();
        $values = $validated['settings'];

        Setting::query()
            ->select(['id', 'name'])
            ->orderBy('id')
            ->get()
            ->each(function (Setting $setting) use ($values): void {
                if (! array_key_exists($setting->name, $values)) {
                    return;
                }

                $setting->update([
                    'content' => $values[$setting->name] ?? '',
                ]);
            });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Configurações atualizadas com sucesso.',
        ]);

        return redirect()->route('settings.show');
    }

    /**
     * @return array<int, array{id: int, name: string, label: string, content: mixed, object_type: string, input_type: string, select_object_name: string|null}>
     */
    private function settingsPayload(): array
    {
        return Setting::query()
            ->select(['id', 'name', 'label', 'content', 'object_type'])
            ->orderBy('id')
            ->get()
            ->map(fn (Setting $setting): array => [
                'id' => $setting->id,
                'name' => $setting->name,
                'label' => $setting->label,
                'content' => $setting->content,
                'object_type' => $setting->object_type,
                'input_type' => $setting->isSelection() ? 'select' : $setting->object_type,
                'select_object_name' => $setting->selectObjectName(),
            ])
            ->all();
    }
}
