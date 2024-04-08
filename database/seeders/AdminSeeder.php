<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userId = DB::table('users')->insertGetId([
            'Documento' => '123',
            'password' => Hash::make('Admin.123'),
            'Rol' => 1
        ]);

        DB::table('personals')->insert([
            'user_id' => $userId,
            'asociado_id' => null,
            "Nombre" => "Admin",
            "Apellidos" => "Admin",
            "Correo" => "Admin@gmail.com",
            "Telefono" => "3108355539",
            "FechaNacimiento" => "22-07-1985",
            "LugarNacimiento" => "Sincelejo",
            "TipoDocumento" => "CC",
            "Documento" => "123",
            "Sexo" => "Masculino",
            "DireccionResidencia" => "CRA 8 2 - 34",
            "CiudadResidencia" => "Sincelejo",
            "TiempoResidencia" => "3 años",
            "EstadoCivil" => "Soltero",
            "Profesion" => "Ingeniero",
            "Trabajo" => "TicSoft",
            "cargo" => "Desarrollador",
            "TiempoServicio" => "1 año",
            "TelOficina" => "3125569648",
            "DireccionOficina" => "CRA 8A 2 - 154",
            "CiudadOficina" => "Sincelejo",
            "Estado" => "1",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
