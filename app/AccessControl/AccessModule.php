<?php

namespace App\AccessControl;

use App\Traits\HasMetadata;

enum AccessModule: string
{
    use HasMetadata;

    // Visualização
    case DASHBOARD = 'dashboard';
    case REPORT = 'reports';

    // Pessoas
    case CLIENT = 'clients';
    case TRAINER = 'trainers';
    case SUPPLIER = 'suppliers';

    // Catálogo
    case PRODUCT = 'products';
    case MODALITY = 'modalities';
    case PLAN = 'plans';
    case PLAN_CATEGORY = 'plan_categories';

    // Faturamento
    case CONTRACT = 'contracts';
    case COUPON = 'coupons';
    case SALE = 'sales';
    case PURCHASE = 'purchases';
    case DIRECT_LESSON = 'direct_lessons';

    // Financeiro
    case COST_CENTER = 'cost_centers';
    case FINANCIAL_CATEGORY = 'financial_categories';
    case PAYABLE = 'payables';
    case RECEIVABLE = 'receivables';
    case MOVEMENT = 'movements';
    case TRANSFER = 'transfers';

    // Avançado
    case FINANCIAL_ACCOUNT = 'financial_accounts';
    case USER = 'users';
    case SETTING = 'settings';

    public function label(): string
    {
        return match ($this) {
            self::DASHBOARD => 'Dashboard',
            self::REPORT => 'Relatórios',
            self::CLIENT => 'Clientes',
            self::TRAINER => 'Treinadores',
            self::SUPPLIER => 'Fornecedores',
            self::PRODUCT => 'Produtos',
            self::MODALITY => 'Modalidades',
            self::PLAN => 'Planos',
            self::PLAN_CATEGORY => 'Categorias de Planos',
            self::CONTRACT => 'Contratos',
            self::COUPON => 'Cupons',
            self::SALE => 'Vendas',
            self::PURCHASE => 'Compras',
            self::DIRECT_LESSON => 'Aula Direta',
            self::COST_CENTER => 'Centros de Custo',
            self::FINANCIAL_CATEGORY => 'Categorias Financeiras',
            self::PAYABLE => 'Pagamentos',
            self::RECEIVABLE => 'Recebimentos',
            self::MOVEMENT => 'Caixa',
            self::TRANSFER => 'Transferências',
            self::FINANCIAL_ACCOUNT => 'Contas',
            self::USER => 'Usuários',
            self::SETTING => 'Configurações'
        };
    }

    public function actions(): array
    {
        $default_actions = [
            AccessAction::VIEW,
            AccessAction::CREATE,
            AccessAction::UPDATE,
            AccessAction::DELETE,
            AccessAction::VISIBILITY,
        ];

        return match ($this) {
            self::DASHBOARD => [AccessAction::VIEW],
            self::REPORT => [AccessAction::VIEW],
            self::CLIENT => $default_actions,
            self::TRAINER => $default_actions,
            self::SUPPLIER => $default_actions,
            self::PRODUCT => $default_actions,
            self::MODALITY => $default_actions,
            self::PLAN => $default_actions,
            self::PLAN_CATEGORY => $default_actions,
            self::CONTRACT => [
                ...$default_actions,
                AccessAction::CANCEL,
            ],
            self::COUPON => $default_actions,
            self::SALE => [
                ...$default_actions,
                AccessAction::MARK_PAID,
                AccessAction::MARK_UNPAID,
            ],
            self::PURCHASE => [
                ...$default_actions,
                AccessAction::MARK_PAID,
                AccessAction::MARK_UNPAID,
            ],
            self::DIRECT_LESSON => [
                ...$default_actions,
                AccessAction::MARK_PAID,
                AccessAction::MARK_UNPAID,
            ],
            self::COST_CENTER => $default_actions,
            self::FINANCIAL_CATEGORY => $default_actions,
            self::PAYABLE => [
                ...$default_actions,
                AccessAction::MARK_PAID,
                AccessAction::MARK_UNPAID,
            ],
            self::RECEIVABLE => [
                ...$default_actions,
                AccessAction::MARK_PAID,
                AccessAction::MARK_UNPAID,
            ],
            self::MOVEMENT => $default_actions,
            self::TRANSFER => [
                ...$default_actions,
                AccessAction::CANCEL,
            ],
            self::FINANCIAL_ACCOUNT => $default_actions,
            self::USER => $default_actions,
            self::SETTING => [
                AccessAction::VIEW,
                AccessAction::UPDATE,
            ]
        };
    }
}
