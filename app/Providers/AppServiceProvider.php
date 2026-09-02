<?php

namespace App\Providers;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\Reservas;
use App\Models\Vehicle;
use App\Services\SameDayScheduleCutoff;
use Illuminate\Support\Facades\Schema;
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
        Schema::defaultStringLength(191);

        View::composer([
            'layouts.admin',
            'admin.partials.add-student-modal',
            'admin.partials.add-instructor-modal',
            'admin.partials.add-vehicle-modal',
            'admin.partials.schedule-class-modal',
            'admin.partials.assign-schedule-modal',
        ], function ($view) {
            $cutoff = app(SameDayScheduleCutoff::class);

            $view->with([
                'courses' => Course::query()->orderBy('num_classes')->get(),
                'instructors' => Instructor::query()->orderBy('name')->get(),
                'vehicles' => Vehicle::query()->orderBy('modelo')->get(),
                'timeSlots' => Reservas::availableTimes(),
                'minBookableDate' => $cutoff->minBookableDate(),
                'sameDayScheduleBlocked' => $cutoff->isSameDayBlocked(),
                'sameDayScheduleMessage' => $cutoff->message(),
            ]);
        });
    }
}
