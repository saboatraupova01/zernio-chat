<?php

use App\Http\Controllers\MessageController;
use App\Http\Controllers\WebhookController;
use App\Services\ZernioService;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConversationController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-zernio', function (ZernioService $zernio) {
    return $zernio->getConversations();
});

Route::get('/test-zernio/messages', function (ZernioService $zernio) {
    return $zernio->getMessages('6aa275ece278ec2f42905f32','6a180a034c7f364ffded3c9c');
});

Route::get('/conversations/sync', [ConversationController::class, 'sync']);
Route::get(
    '/conversations/{conversation}/messages/sync',
    [ConversationController::class, 'syncMessages']
);

Route::post('/webhooks/zernio', [WebhookController::class, 'zernio']);
Route::post('/conversations/{conversation}/messages', [MessageController::class, 'send'])->name('messages.send');

Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');

Route::patch('/conversations/{conversation}/status', [ConversationController::class, 'updateStatus'])->name('conversations.status');
Route::patch('/conversations/{conversation}/assign', [ConversationController::class, 'assignOperator'])->name('conversations.assign');
