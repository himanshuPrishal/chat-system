<?php

use App\Http\Controllers\CallController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StickerController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Enable broadcasting authentication
Broadcast::routes(['middleware' => ['web', 'auth']]);

Route::get('/', function () {
    return redirect()->route('chat.index');
});

Route::middleware('auth')->group(function () {
    // Chat interface
    Route::get('/chat', function () {
        return view('chat.index');
    })->name('chat.index');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Conversation routes
    Route::prefix('conversations')->group(function () {
        Route::get('/', [ConversationController::class, 'index'])->name('conversations.index');
        Route::post('/', [ConversationController::class, 'store'])->name('conversations.store');
        Route::get('/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
        Route::patch('/{conversation}', [ConversationController::class, 'update'])->name('conversations.update');
        Route::delete('/{conversation}', [ConversationController::class, 'destroy'])->name('conversations.destroy');
        Route::post('/{conversation}/read', [ConversationController::class, 'markAsRead'])->name('conversations.read');
        
        // Messages within conversation
        Route::get('/{conversation}/messages', [MessageController::class, 'index'])->name('conversations.messages.index');
        Route::post('/{conversation}/messages', [MessageController::class, 'store'])->name('conversations.messages.store');
        Route::post('/{conversation}/typing', [MessageController::class, 'typing'])->name('conversations.typing');
        Route::get('/{conversation}/search', [MessageController::class, 'search'])->name('conversations.search');
        
        // Calls
        Route::post('/{conversation}/calls', [CallController::class, 'initiate'])->name('conversations.calls.initiate');
        Route::get('/{conversation}/calls', [CallController::class, 'history'])->name('conversations.calls.history');
    });

    // Message routes
    Route::prefix('messages')->group(function () {
        Route::patch('/{message}', [MessageController::class, 'update'])->name('messages.update');
        Route::delete('/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
        Route::post('/{message}/react', [MessageController::class, 'react'])->name('messages.react');
        Route::delete('/{message}/react/{emoji}', [MessageController::class, 'removeReaction'])->name('messages.unreact');
    });

    // Media upload
    Route::post('/media/upload', [MessageController::class, 'uploadMedia'])->name('media.upload');

    // Call management
    Route::prefix('calls')->group(function () {
        Route::post('/{callLog}/answer', [CallController::class, 'answer'])->name('calls.answer');
        Route::post('/{callLog}/reject', [CallController::class, 'reject'])->name('calls.reject');
        Route::post('/{callLog}/end', [CallController::class, 'end'])->name('calls.end');
        Route::post('/{callLog}/signal', [CallController::class, 'signal'])->name('calls.signal');
    });

    // Stickers
    Route::prefix('stickers')->group(function () {
        Route::get('/', [StickerController::class, 'index'])->name('stickers.index');
        Route::post('/', [StickerController::class, 'store'])->name('stickers.store');
        Route::get('/packs', [StickerController::class, 'packs'])->name('stickers.packs');
    });

    // User search
    Route::get('/users/search', [ConversationController::class, 'searchUsers'])->name('users.search');

    // Group management
    Route::prefix('groups')->group(function () {
        Route::post('/{conversation}/members', [GroupController::class, 'addMembers'])->name('groups.members.add');
        Route::delete('/{conversation}/members', [GroupController::class, 'removeMembers'])->name('groups.members.remove');
        Route::patch('/{conversation}/members/{user}/role', [GroupController::class, 'updateMemberRole'])->name('groups.members.role');
        Route::post('/{conversation}/avatar', [GroupController::class, 'updateAvatar'])->name('groups.avatar');
        Route::post('/{conversation}/leave', [GroupController::class, 'leaveGroup'])->name('groups.leave');
    });
});

require __DIR__.'/auth.php';
