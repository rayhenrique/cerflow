<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'agenda.visualizar',
            'agenda.gerenciar',
            'recepcao.checkin',
            'pacientes.visualizar',
            'pacientes.gerenciar',
            'pep.visualizar',
            'pep.evoluir',
            'pep.assinar',
            'pep.adendo',
            'faturamento.auditar',
            'faturamento.parametrizar',
            'faturamento.exportar',
            'salas.gerenciar',
            'profissionais.gerenciar',
            'usuarios.gerenciar',
            'auditoria.visualizar',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $recepcao = Role::firstOrCreate(['name' => 'recepcao', 'guard_name' => 'web']);
        $recepcao->syncPermissions([
            'agenda.visualizar',
            'agenda.gerenciar',
            'recepcao.checkin',
            'pacientes.visualizar',
            'pacientes.gerenciar',
        ]);

        $terapeuta = Role::firstOrCreate(['name' => 'terapeuta', 'guard_name' => 'web']);
        $terapeuta->syncPermissions([
            'agenda.visualizar',
            'pacientes.visualizar',
            'pep.visualizar',
            'pep.evoluir',
            'pep.assinar',
            'pep.adendo',
        ]);

        $faturamento = Role::firstOrCreate(['name' => 'faturamento', 'guard_name' => 'web']);
        $faturamento->syncPermissions([
            'pacientes.visualizar',
            'faturamento.auditar',
            'faturamento.parametrizar',
            'faturamento.exportar',
        ]);

        $coordenacao = Role::firstOrCreate(['name' => 'coordenacao', 'guard_name' => 'web']);
        $coordenacao->syncPermissions(Permission::all());
    }
}
