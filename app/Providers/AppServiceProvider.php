<?php

namespace App\Providers;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\Reservas;
use App\Models\Vehicle;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer([
            'layouts.admin',
            'layouts.student',
            'admin.partials.add-student-modal',
            'admin.partials.add-instructor-modal',
            'admin.partials.add-vehicle-modal',
            'admin.partials.schedule-class-modal',
            'student.partials.book-class-modal',
        ], function ($view) {
            $view->with([
                'courses' => Course::query()->orderBy('num_classes')->get(),
                'instructors' => Instructor::query()->orderBy('name')->get(),
                'vehicles' => Vehicle::query()->orderBy('modelo')->get(),
                'timeSlots' => Reservas::availableTimes(),
            ]);
        });
    }
}
