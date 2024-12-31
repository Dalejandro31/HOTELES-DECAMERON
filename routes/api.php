<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HotelController;
use App\Http\Controllers\RoomController;

Route::apiResource('hotels', HotelController::class);
Route::apiResource('rooms', RoomController::class);
