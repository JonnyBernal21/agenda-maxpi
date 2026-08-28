<?php

use App\Http\Controllers\Admin\AvailabilityController as AdminAvailabilityController;
use App\Http\Controllers\Admin\CalendarController as AdminCalendarController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailPreviewController;
use App\Http\Controllers\Admin\InstructorController as AdminInstructorController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ReservaController as AdminReservaController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\VehicleController as AdminVehicleController;
use App\Http\Controllers\Auth\InstructorLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Instructor\CalendarController as InstructorCalendarController;
use App\Http\Controllers\Instructor\DashboardController as InstructorDashboardController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest:web')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth:web')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/admin', DashboardController::class)->name('admin.dashboard');
    Route::get('/admin/calendar/events', [AdminCalendarController::class, 'events'])->name('admin.calendar.events');

    Route::get('/admin/alumnos', [AdminStudentController::class, 'index'])->name('admin.students.index');
    Route::get('/admin/instructores', [AdminInstructorController::class, 'index'])->name('admin.instructors.index');
    Route::post('/admin/instructors', [InstructorController::class, 'store'])->name('admin.instructors.store');
    Route::put('/admin/instructors/{instructor}', [InstructorController::class, 'update'])->name('admin.instructors.update');
    Route::delete('/admin/instructors/{instructor}', [InstructorController::class, 'destroy'])->name('admin.instructors.destroy');
    Route::get('/admin/reportes', [AdminReportController::class, 'index'])->name('admin.reports.index');
    Route::get('/admin/correos', [EmailPreviewController::class, 'index'])->name('admin.emails.index');
    Route::get('/admin/correos/{template}/html', [EmailPreviewController::class, 'html'])
        ->where('template', '[A-Za-z0-9-]+')
        ->name('admin.emails.html');

    Route::get('/admin/vehiculos', [AdminVehicleController::class, 'index'])->name('admin.vehicles.index');
    Route::post('/admin/vehicles', [VehicleController::class, 'store'])->name('admin.vehicles.store');
    Route::put('/admin/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('admin.vehicles.update');
    Route::delete('/admin/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('admin.vehicles.destroy');

    Route::get('/admin/students/search', [StudentController::class, 'search'])->name('admin.students.search');
    Route::get('/admin/students/{student}/horarios', [StudentController::class, 'schedule'])->name('admin.students.schedule');
    Route::post('/admin/students', [StudentController::class, 'store'])->name('admin.students.store');
    Route::put('/admin/students/{student}', [StudentController::class, 'update'])->name('admin.students.update');
    Route::delete('/admin/students/{student}', [StudentController::class, 'destroy'])->name('admin.students.destroy');
    Route::post('/admin/students/{student}/enviar-horarios', [StudentController::class, 'sendSchedule'])->name('admin.students.schedule-email');
    Route::post('/admin/reservas', [AdminReservaController::class, 'store'])->name('admin.reservas.store');
    Route::post('/admin/reservas/horarios', [AdminReservaController::class, 'storeSchedule'])->name('admin.reservas.schedule');
    Route::patch('/admin/reservas/{reserva}/confirm', [AdminReservaController::class, 'confirm'])->name('admin.reservas.confirm');
    Route::patch('/admin/reservas/{reserva}/complete', [AdminReservaController::class, 'complete'])->name('admin.reservas.complete');
    Route::patch('/admin/reservas/{reserva}/cancel', [AdminReservaController::class, 'cancel'])->name('admin.reservas.cancel');
    Route::patch('/admin/reservas/{reserva}/reschedule', [AdminReservaController::class, 'reschedule'])->name('admin.reservas.reschedule');
    Route::get('/admin/reservas/options', [AdminAvailabilityController::class, 'options'])->name('admin.reservas.options');
    Route::get('/admin/reservas/check', [AdminAvailabilityController::class, 'check'])->name('admin.reservas.check');
    Route::get('/admin/reservas/instructor-conflicts', [AdminAvailabilityController::class, 'instructorConflicts'])->name('admin.reservas.instructor-conflicts');
});

Route::middleware('guest:instructor')->group(function () {
    Route::get('/instructor/login', [InstructorLoginController::class, 'create'])->name('instructor.login');
    Route::post('/instructor/login', [InstructorLoginController::class, 'store']);
});

Route::middleware('auth:instructor')->prefix('instructor')->name('instructor.')->group(function () {
    Route::post('/logout', [InstructorLoginController::class, 'destroy'])->name('logout');
    Route::get('/', InstructorDashboardController::class)->name('dashboard');
    Route::get('/calendar/events', [InstructorCalendarController::class, 'events'])->name('calendar.events');
});
