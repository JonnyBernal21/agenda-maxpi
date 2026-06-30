<?php

namespace App\Console\Commands;

use App\Models\Instructor;
use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;

class ResetDemoPasswords extends Command
{
    protected $signature = 'app:reset-demo-passwords';

    protected $description = 'Restablece la contraseña de demo a "password" para admin, alumnos e instructores';

    public function handle(): int
    {
        $this->resetPasswords(User::query());
        $this->resetPasswords(Student::query());
        $this->resetPasswords(Instructor::query());

        $this->info('Contraseñas restablecidas a "password" (bcrypt) para todos los usuarios.');

        return self::SUCCESS;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    private function resetPasswords($query): void
    {
        $query->each(function ($model): void {
            $model->password = 'password';
            $model->save();
        });
    }
}
