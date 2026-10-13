<?php

namespace App\Providers;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\Reservas;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AdminNotificationService;
use App\Services\SameDayScheduleCutoff;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\Gate;
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

        $this->bootPermissions();

        View::composer('layouts.partials.admin-navbar', function ($view) {
            $user = auth()->user();
            $notifications = app(AdminNotificationService::class);
            $canSee = $notifications->canAccess($user);

            $unread = 0;

            if ($canSee && $user && Schema::hasTable('admin_notification_reads')) {
                $unread = $notifications->feed($user)['unread'];
            }

            $view->with([
                'canSeeNotifications' => $canSee,
                'notificationUnread' => $unread,
                'notificationsIndexUrl' => $canSee ? route('admin.notifications.index') : null,
                'notificationsReadUrl' => $canSee ? route('admin.notifications.read') : null,
                'notificationsDestroyUrl' => $canSee ? route('admin.notifications.destroy') : null,
            ]);
        });

        View::composer([
            'layouts.admin',
            'admin.partials.add-student-modal',
            'admin.partials.add-instructor-modal',
            'admin.partials.add-vehicle-modal',
            'admin.partials.add-course-modal',
            'admin.partials.add-expense-modal',
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

    private function bootPermissions(): void
    {
        Gate::before(function ($user) {
            return $user instanceof User && $user->role?->isAdmin() ? true : null;
        });

        foreach (PermissionCatalog::keys() as $key) {
            Gate::define($key, fn (User $user) => $user->hasPermission($key));
        }
    }
}
