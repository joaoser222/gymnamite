<?php

namespace Tests\Feature;

use App\AccessControl\AccessRole;
use App\AccessControl\RolePermissionMap;
use Tests\TestCase;

class GatewayModuleAccessTest extends TestCase
{
    public function test_gateway_modules_are_assigned_to_administrator_by_default(): void
    {
        $map = (new RolePermissionMap)->getMap();

        foreach ($this->gatewayModules() as $module) {
            $this->assertSame(
                ['view'],
                $map[AccessRole::ADMINISTRATOR->value][$module],
            );
        }
    }

    public function test_gateway_modules_are_not_assigned_to_non_admin_roles_by_default(): void
    {
        $map = (new RolePermissionMap)->getMap();

        foreach ([AccessRole::MANAGER, AccessRole::BILLING] as $role) {
            foreach (['gateway_accounts', ...$this->gatewayModules()] as $module) {
                $this->assertArrayNotHasKey($module, $map[$role->value]);
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function gatewayModules(): array
    {
        return [
            'gateway_payments',
            'gateway_transfers',
            'gateway_postbacks',
            'gateway_customers',
            'gateway_credit_cards',
        ];
    }
}
