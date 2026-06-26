<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Models\Setting;
use App\Traits\HasModule;

class SettingController extends Controller
{
    use HasModule;

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'created_at'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['name'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'name', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::SETTING;
    }

    protected function modelClass(): string
    {
        return Setting::class;
    }
}
