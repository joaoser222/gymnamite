# Access Control

Este documento descreve a convenção atual de permissões da aplicação e como reaproveitar o trait de autorização em controllers vinculados a models.

## Conceito

O controle de acesso usa três enums em `app/AccessControl`:

- `AccessModule`: representa o recurso funcional, como `clients`, `plans` ou `sales`.
- `AccessAction`: representa a ação permitida, como `view`, `create`, `update` ou `delete`.
- `AccessRole`: representa papéis padrão do sistema, como `administrator`, `manager` e `billing`.

A permissão persistida no banco segue o formato:

```text
{module}.{action}
```

Exemplos:

```text
clients.view
clients.create
clients.update
clients.delete
sales.mark_paid
```

## Trait de Autorização

Controllers que representam um módulo devem usar:

```php
use App\Traits\HasModule;
```

`HasModule` já usa `AuthorizesAccessControl` internamente e centraliza as ações padrão `index`, `store`, `update` e `destroy`.

O controller precisa implementar:

```php
protected function accessModule(): AccessModule
{
    return AccessModule::CLIENT;
}

protected function modelClass(): string
{
    return Client::class;
}
```

Se o usuário autenticado não possuir a permissão, a requisição recebe `403 Forbidden`.

## Exemplo em Controller

```php
use App\AccessControl\AccessModule;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Traits\HasModule;

class ClientController extends Controller
{
    use HasModule;

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['name', 'email', 'document', 'phone'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'created_at', 'updated_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::CLIENT;
    }

    protected function modelClass(): string
    {
        return Client::class;
    }

    protected function storeRequestClass(): ?string
    {
        return StoreClientRequest::class;
    }

    protected function updateRequestClass(): ?string
    {
        return UpdateClientRequest::class;
    }
}
```

Quando o controller usa `HasModule`, as permissões aplicadas automaticamente são:

- `index`: `view`
- `store`: `create`
- `update`: `update`
- `destroy`: `delete`

## Como a Verificação Funciona

O trait procura a permissão em dois lugares:

1. Permissões diretas do usuário, via `permission_user`.
2. Permissões herdadas pela role do usuário, via `role_id` e `permission_role`.

Na prática, um usuário pode receber `clients.view` diretamente ou por meio de uma role que tenha essa permissão associada.

## Checklist Para Novos Controllers

Ao proteger um novo controller:

1. Confirme se existe um caso correspondente em `AccessModule`.
2. Confirme se `AccessModule::actions()` lista as ações aceitas para esse módulo.
3. Adicione `use HasModule;` no controller.
4. Implemente `accessModule()`.
5. Implemente `modelClass()`.
6. Defina `$searchableFields` e `$sortableFields` quando o módulo tiver listagem.
7. Implemente `storeRequestClass()` e `updateRequestClass()` quando o módulo usar Form Requests.
8. Atualize ou crie testes cobrindo acesso permitido, negado e validação.

## Seeds e Sincronização

`RolePermissionMap` define quais ações cada role padrão deve receber. O formato esperado do mapa é:

```php
[
    'administrator' => [
        'clients' => ['view', 'create', 'update', 'delete'],
    ],
]
```

Ao sincronizar isso com o banco, gere permissões no formato `{module}.{action}`:

```php
$permissionName = "{$module}.{$action}";
```

Depois associe a permissão à role correspondente.

### Comando de Sincronização

Use o comando abaixo para sincronizar roles, permissões e permissões de roles com base nos enums e em `RolePermissionMap`:

```bash
php artisan access-control:sync
```

Por padrão, usuários sem `role_id` recebem a role `administrator`.

Para usar outra role padrão:

```bash
php artisan access-control:sync --default-role=manager
```

Para sincronizar apenas roles e permissões, sem atualizar usuários:

```bash
php artisan access-control:sync --without-users
```

O comando é idempotente: pode ser executado mais de uma vez sem duplicar roles, permissões ou vínculos.

## Testes

Os testes principais ficam em:

```text
tests/Feature/AccessControl/AuthorizesAccessControlTest.php
tests/Feature/AccessControl/SyncAccessControlCommandTest.php
```

Eles cobrem:

- Usuário autenticado sem permissão recebe `403`.
- Usuário com permissão direta acessa.
- Usuário com permissão herdada pela role acessa.
- `RolePermissionMap` retorna actions como strings.

Ao alterar autorização, rode:

```bash
php artisan test --compact tests/Feature/AccessControl/AuthorizesAccessControlTest.php
```

Quando aplicar autorização em um controller existente, rode também os testes desse fluxo.

## Próximos Passos Recomendados

- Criar um seeder para sincronizar `AccessRole`, `AccessModule`, `AccessAction` e `RolePermissionMap` com as tabelas `roles`, `permissions` e `permission_role`.
- Adicionar índices ou unicidade em `permissions.name` para evitar permissões duplicadas.
- Avaliar colunas separadas `module` e `action` em `permissions` se o sistema precisar consultar permissões por módulo com frequência.
- Aplicar o trait nos demais controllers de CRUD conforme os módulos forem estabilizados.
