<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FriendRequestController;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::middleware(['AuthMiddleware'])->get('/users', [AuthController::class, 'getAllUsers']);

Route::middleware(['AuthMiddleware'])->get('/user', [AuthController::class, 'getUserInfo']);

Route::middleware(['AuthMiddleware'])->post('/profile/update', [AuthController::class, 'updateProfile']);

Route::middleware(['AuthMiddleware'])->group(function () {
    Route::post('/send-friend-request', [FriendRequestController::class, 'sendFriendRequest']);
    Route::post('/accept-friend-request', [FriendRequestController::class, 'acceptFriendRequest']);
    Route::post('/reject-friend-request', [FriendRequestController::class, 'rejectFriendRequest']);
    Route::post('/cancel-friend-request', [FriendRequestController::class, 'cancelFriendRequest']);
    Route::get('/list-pending-friend-requests', [FriendRequestController::class, 'listPendingFriendRequests']);
    Route::get('/list-friends', [FriendRequestController::class, 'listFriends']);
});
