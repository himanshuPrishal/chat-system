<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    public function update(User $user, Message $message): bool
    {
        return $message->user_id === $user->id;
    }

    public function delete(User $user, Message $message): bool
    {
        return $message->user_id === $user->id;
    }

    public function react(User $user, Message $message): bool
    {
        // User must be a participant of the conversation
        return $message->conversation->participants()
            ->where('user_id', $user->id)
            ->exists();
    }
}
