<?php

namespace Tests\Feature;

use App\AccessControl\AccessRole;
use App\AccessControl\RolePermissionMap;
use Tests\TestCase;

class GatewayModuleAccessTest extends TestCase
{
    public function test_administrator_receives_all_actions_for_gateway_modules(): void
    {
        $map = (new RolePermissionMap)->getMap();
        $admin = $map[AccessRole::ADMINISTRATOR->value];

        foreach ($this->allGatewayModules() as $module) {
            $this->assertArrayHasKey($module, $admin);
            $this->assertContains('view', $admin[$module]);
            if (in_array($module, ['gateway_accounts', 'gateway_transfers', 'gateway_transfer_recipients'], true)) {
                $this->assertContains('create', $admin[$module]);
            }
        }
    }

    public function test_manager_receives_gateway_modules_without_delete(): void
    {
        $map = (new RolePermissionMap)->getMap();
        $manager = $map[AccessRole::MANAGER->value];

        foreach ($this->allGatewayModules() as $module) {
            $this->assertArrayHasKey($module, $manager);
            $this->assertNotContains('delete', $manager[$module]);
        }
    }

    public function test_billing_does_not_receive_gateway_modules(): void
    {
        $map = (new RolePermissionMap)->getMap();
        $billing = $map[AccessRole::BILLING->value];

        foreach ($this->allGatewayModules() as $module) {
            $this->assertArrayNotHasKey($module, $billing);
        }
    }

    /**
     * @return array<int, string>
     */
    private function allGatewayModules(): array
    {
        return [
            'gateway_accounts',
            'gateway_payments',
            'gateway_transfers',
            'gateway_transfer_recipients',
            'gateway_postbacks',
            'gateway_customers',
            'gateway_credit_cards',
            'gateway_invoices',
        ];
    }
}
