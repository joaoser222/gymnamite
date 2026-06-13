<?php

namespace App\AccessControl;

final class RolePermissionMap
{
    private static function actionsToString(array $actions): array
    {
        return array_map(fn (AccessAction $action) => $action->value, $actions);
    }

    private static function filterActions(array $actions, array $except): array
    {
        return array_values(array_filter(
            $actions,
            fn (AccessAction $action) => ! in_array($action, $except, true),
        ));
    }

    private static function generate(array $exceptModules = [], array $exceptActions = []): array
    {
        $result = [];

        foreach (AccessModule::cases() as $key) {
            if (in_array($key, $exceptModules, true)) {
                continue;
            }
            $result[$key->value] = self::actionsToString(
                self::filterActions($key->actions(), $exceptActions)
            );
        }

        return $result;
    }

    public function adminPermissions(): array
    {
        return self::generate();
    }

    public function managerPermissions(): array
    {
        return self::generate(
            [AccessModule::SETTING],
            [AccessAction::DELETE]
        );
    }

    public function billingPermissions(): array
    {
        $exceptActions = [
            AccessAction::DELETE,
            AccessAction::VISIBILITY,
        ];

        $basePermissions = self::generate(
            [
                AccessModule::DASHBOARD,
                AccessModule::REPORT,
                AccessModule::SETTING,
                AccessModule::COST_CENTER,
                AccessModule::USER,
                AccessModule::MODALITY,
                AccessModule::PLAN_CATEGORY,
                AccessModule::TRANSFER,
                AccessModule::MOVEMENT,
                AccessModule::FINANCIAL_ACCOUNT,
                AccessModule::FINANCIAL_CATEGORY,
            ],
            $exceptActions
        );

        return [
            ...$basePermissions,
            AccessModule::CONTRACT->value => self::actionsToString(
                self::filterActions(AccessModule::CONTRACT->actions(), [
                    ...$exceptActions,
                    AccessAction::CANCEL,
                ])
            ),
            AccessModule::COUPON->value => self::actionsToString(
                self::filterActions(AccessModule::COUPON->actions(), [
                    ...$exceptActions,
                    AccessAction::CREATE,
                    AccessAction::UPDATE,
                ])
            ),
            AccessModule::PAYABLE->value => self::actionsToString(
                self::filterActions(AccessModule::PAYABLE->actions(), [
                    ...$exceptActions,
                    AccessAction::CREATE,
                    AccessAction::UPDATE,
                ])
            ),
            AccessModule::RECEIVABLE->value => self::actionsToString(
                self::filterActions(AccessModule::RECEIVABLE->actions(), [
                    ...$exceptActions,
                    AccessAction::CREATE,
                    AccessAction::UPDATE,
                ])
            ),
        ];
    }

    public function getMap(): array
    {
        return [
            AccessRole::ADMINISTRATOR->value => self::adminPermissions(),
            AccessRole::MANAGER->value => self::managerPermissions(),
            AccessRole::BILLING->value => self::billingPermissions(),
        ];
    }
}
