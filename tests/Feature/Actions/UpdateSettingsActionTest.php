<?php

namespace Tests\Feature\Actions;

use App\Actions\Settings\UpdateSettingsAction;
use App\DTOs\Settings\UpdateSettingsDTO;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateSettingsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_existing_settings(): void
    {
        Setting::query()->create(['name' => 'company_name', 'label' => 'Nome da Empresa', 'content' => 'Old Name', 'object_type' => 'text']);
        Setting::query()->create(['name' => 'company_email', 'label' => 'Email', 'content' => 'old@example.com', 'object_type' => 'text']);

        $action = app(UpdateSettingsAction::class);
        $dto = new UpdateSettingsDTO(settings: [
            'company_name' => 'New Name',
            'company_email' => 'new@example.com',
        ]);

        $result = $action->execute($dto);

        $this->assertSame(2, $result);
        $this->assertSame('New Name', Setting::query()->where('name', 'company_name')->value('content'));
        $this->assertSame('new@example.com', Setting::query()->where('name', 'company_email')->value('content'));
    }

    public function test_returns_count_of_updated_settings(): void
    {
        Setting::query()->create(['name' => 'key1', 'label' => 'Key 1', 'content' => 'old1', 'object_type' => 'text']);
        Setting::query()->create(['name' => 'key2', 'label' => 'Key 2', 'content' => 'old2', 'object_type' => 'text']);
        Setting::query()->create(['name' => 'key3', 'label' => 'Key 3', 'content' => 'old3', 'object_type' => 'text']);

        $action = app(UpdateSettingsAction::class);
        $dto = new UpdateSettingsDTO(settings: [
            'key1' => 'new1',
            'key3' => 'new3',
        ]);

        $result = $action->execute($dto);

        $this->assertSame(2, $result);
    }

    public function test_ignores_settings_not_in_database(): void
    {
        Setting::query()->create(['name' => 'existing', 'label' => 'Existing', 'content' => 'old', 'object_type' => 'text']);

        $action = app(UpdateSettingsAction::class);
        $dto = new UpdateSettingsDTO(settings: [
            'existing' => 'new',
            'nonexistent' => 'value',
        ]);

        $result = $action->execute($dto);

        $this->assertSame(1, $result);
    }

    public function test_returns_zero_when_no_settings_match(): void
    {
        Setting::query()->create(['name' => 'key', 'label' => 'Key', 'content' => 'old', 'object_type' => 'text']);

        $action = app(UpdateSettingsAction::class);
        $dto = new UpdateSettingsDTO(settings: ['nonexistent' => 'value']);

        $result = $action->execute($dto);

        $this->assertSame(0, $result);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(UpdateSettingsAction::class);
        $action->execute('not-a-dto');
    }
}
