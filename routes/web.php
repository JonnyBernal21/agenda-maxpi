<?php

use App\Http\Controllers\Admin\AvailabilityController as AdminAvailabilityController;
use App\Http\Controllers\Admin\CalendarController as AdminCalendarController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InstructorController as AdminInstructorController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ReservaController as AdminReservaController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\VehicleController as AdminVehicleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\Auth\InstructorLoginController;
use App\Http\Controllers\Auth\StudentLoginController;
use App\Http\Controllers\Instructor\CalendarController as InstructorCalendarController;
use App\Http\Controllers\Instructor\DashboardController as InstructorDashboardController;
use App\Http\Controllers\Student\AvailabilityController as StudentAvailabilityController;
use App\Http\Controllers\Student\CalendarController as StudentCalendarController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\ReservaController as StudentReservaController;
use App\Http\Controllers\StudentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/alumno/login');

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
    Route::get('/admin/reportes', [AdminReportController::class, 'index'])->name('admin.reports.index');

    Route::get('/admin/vehiculos', [AdminVehicleController::class, 'index'])->name('admin.vehicles.index');
    Route::post('/admin/vehicles', [VehicleController::class, 'store'])->name('admin.vehicles.store');

    Route::get('/admin/students/search', [StudentController::class, 'search'])->name('admin.students.search');
    Route::post('/admin/students', [StudentController::class, 'store'])->name('admin.students.store');
    Route::post('/admin/reservas', [AdminReservaController::class, 'store'])->name('admin.reservas.store');
    Route::patch('/admin/reservas/{reserva}/confirm', [AdminReservaController::class, 'confirm'])->name('admin.reservas.confirm');
    Route::patch('/admin/reservas/{reserva}/complete', [AdminReservaController::class, 'complete'])->name('admin.reservas.complete');
    Route::get('/admin/reservas/options', [AdminAvailabilityController::class, 'options'])->name('admin.reservas.options');
    Route::get('/admin/reservas/check', [AdminAvailabilityController::class, 'check'])->name('admin.reservas.check');
});

Route::middleware('guest:student')->group(function () {
    Route::get('/alumno/login', [StudentLoginController::class, 'create'])->name('student.login');
    Route::post('/alumno/login', [StudentLoginController::class, 'store']);
});

Route::middleware('auth:student')->prefix('alumno')->name('student.')->group(function () {
    Route::post('/logout', [StudentLoginController::class, 'destroy'])->name('logout');
    Route::get('/', StudentDashboardController::class)->name('dashboard');
    Route::get('/calendar/events', [StudentCalendarController::class, 'events'])->name('calendar.events');
    Route::get('/reservas/check', [StudentAvailabilityController::class, 'check'])->name('reservas.check');
    Route::get('/reservas/options', [StudentAvailabilityController::class, 'options'])->name('reservas.options');
    Route::post('/reservas', [StudentReservaController::class, 'store'])->name('reservas.store');
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
