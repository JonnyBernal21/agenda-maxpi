<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\Reservas;
use App\Models\Student;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\ProductionSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class WipeDemoData extends Command
{
    protected $signature = 'app:wipe-demo-data {--force : Ejecutar sin confirmación}';

    protected $description = 'Vacía alumnos, instructores, vehículos, cursos y reservas. Deja un administrador para producción.';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Esto borrará todos los datos de prueba (alumnos, clases, instructores, vehículos y cursos). ¿Continuar?')) {
            $this->info('Operación cancelada.');

            return self::SUCCESS;
        }

        Schema::disableForeignKeyConstraints();

        Reservas::query()->truncate();
        Student::query()->truncate();
        Instructor::query()->truncate();
        Vehicle::query()->truncate();
        Course::query()->truncate();
        User::query()->truncate();

        Schema::enableForeignKeyConstraints();

        $this->call('db:seed', [
            '--class' => ProductionSeeder::class,
            '--force' => true,
        ]);

        $this->info('Base de datos limpia.');
        $this->line('Admin: admin@agenda-maxpi.test');
        $this->line('Contraseña: password');

        return self::SUCCESS;
    }
}
