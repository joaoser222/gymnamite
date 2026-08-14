<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Traits\AuthorizesAccessControl;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use AuthorizesAccessControl;

    public function __invoke(): Response
    {
        $this->authorizeAccess(AccessAction::VIEW);

        return Inertia::render('Dashboard');
    }

    protected function accessModule(): AccessModule
    {
        return AccessModule::DASHBOARD;
    }
}
