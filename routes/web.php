<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Livewire\Volt\Volt;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('/tasks', 'tasks')->name('tasks');
    Volt::route('/task/create', 'task-create')->name('task.create');
    Volt::route('/task/edit/{id}', 'task-edit')->name('task.edit');
    Volt::route('/task/view/{id}', 'booking-edit')->name('booking.edit');
});

Route::get('/about', function () {
    return view('about');
})->name('about');

Route::prefix('admin')->middleware(['auth', 'verified', 'check.email'])->group(function () {
    Volt::route('/', 'admin')->name('admin');

    Volt::route('/tags', 'admin-tags')->name('admin.tags');
    
    Volt::route('/users', 'admin-users')->name('admin.users');
    Volt::route('/user/edit/{id}', 'admin-user-edit')->name('admin.edit.user');
    Volt::route('/user/view/{id}', 'admin-user-view')->name('admin.view.user');

    Volt::route('/buses', 'admin-buses')->name('admin.buses');
    Volt::route('/bus/edit/{id}', 'admin-bus-edit')->name('admin.edit.bus');
    Volt::route('/bus/view/{id}', 'admin-bus-view')->name('admin.view.bus');
    
    
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
