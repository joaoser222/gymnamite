<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    /**
     * O nome e assinatura do comando no terminal.
     *
     * @var string
     */
    protected $signature = 'user:create-admin
                            {--email= : E-mail do administrador}
                            {--name= : Nome do administrador}
                            {--password= : Senha do administrador}
                            {--force : Forçar criação mesmo se usuário existir}';

    /**
     * @var string
     */
    protected $description = 'Cria um usuário administrador no sistema';

    /**
     * Execute o comando.
     */
    public function handle()
    {
        $this->info('Criando usuário administrador...');
        $this->newLine();

        // Coleta os dados
        $name = $this->option('name') ?? $this->ask('Nome do administrador');
        $email = $this->option('email') ?? $this->ask('E-mail do administrador');
        $password = $this->option('password') ?? $this->secret('Senha do administrador');
        $role = Role::where('name','admin')->first();
        // Validação
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return Command::FAILURE;
        }

        // Verifica se usuário já existe
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            if (!$this->option('force')) {
                $this->warn("Usuário com e-mail {$email} já existe!");
                
                if (!$this->confirm('Deseja atualizar este usuário para administrador?')) {
                    $this->error('Operação cancelada.');
                    return Command::FAILURE;
                }
            }
            
            // Atualiza usuário existente
            $existingUser->update([
                'name' => $name,
                'password' => Hash::make($password),
                'role_id'=>$role->id
            ]);
            
            $this->info('Usuário atualizado para administrador com sucesso!');
            $this->table(
                ['ID', 'Nome', 'E-mail', 'Admin'],
                [[$existingUser->id, $existingUser->name, $existingUser->email, 'Sim']]
            );
            
            return Command::SUCCESS;
        }

        // Cria novo usuário
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role_id'=>$role->id
        ]);

        $this->info('Usuário administrador criado com sucesso!');
        $this->newLine();
        
        $this->table(
            ['ID', 'Nome', 'E-mail', 'Admin'],
            [[$user->id, $user->name, $user->email, 'Sim']]
        );

        return Command::SUCCESS;
    }
}