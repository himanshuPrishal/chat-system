<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->participants()->where('user_id', $user->id)->exists();
    }

    public function update(User $user, Conversation $conversation): bool
    {
        if ($conversation->type === 'direct') {
            return false; // Direct chats can't be updated
        }

        $participant = $conversation->participants()
            ->where('user_id', $user->id)
            ->first();

        return $participant && $participant->pivot->role === 'admin';
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        return $conversation->created_by === $user->id;
    }

    public function addMembers(User $user, Conversation $conversation): bool
    {
        if ($conversation->type !== 'group') {
            return false;
        }

        $participant = $conversation->participants()
            ->where('user_id', $user->id)
            ->first();

        return $participant && $participant->pivot->role === 'admin';
    }

    public function removeMembers(User $user, Conversation $conversation): bool
    {
        return $this->addMembers($user, $conversation);
    }
}
