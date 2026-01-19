<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    use AuthorizesRequests;
    public function addMembers(Request $request, Conversation $conversation)
    {
        $this->authorize('addMembers', $conversation);

        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        foreach ($request->user_ids as $userId) {
            if (!$conversation->participants()->where('user_id', $userId)->exists()) {
                $conversation->participants()->attach($userId, [
                    'role' => 'member',
                    'joined_at' => now(),
                ]);
            }
        }

        $conversation->load('participants');
        return response()->json($conversation);
    }

    public function removeMembers(Request $request, Conversation $conversation)
    {
        $this->authorize('removeMembers', $conversation);

        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        // Prevent removing the creator
        $userIds = array_diff($request->user_ids, [$conversation->created_by]);
        
        $conversation->participants()->detach($userIds);

        $conversation->load('participants');
        return response()->json($conversation);
    }

    public function updateMemberRole(Request $request, Conversation $conversation, User $user)
    {
        $this->authorize('addMembers', $conversation);

        $request->validate([
            'role' => 'required|in:member,admin',
        ]);

        $conversation->participants()->updateExistingPivot($user->id, [
            'role' => $request->role,
        ]);

        return response()->json(['message' => 'Role updated successfully']);
    }

    public function updateAvatar(Request $request, Conversation $conversation)
    {
        $this->authorize('update', $conversation);

        $request->validate([
            'avatar' => 'required|image|max:2048',
        ]);

        $path = $request->file('avatar')->store('group-avatars', 'public');
        
        $conversation->update(['avatar' => $path]);

        return response()->json($conversation);
    }

    public function leaveGroup(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        if ($conversation->created_by === $request->user()->id) {
            return response()->json(['error' => 'Group creator cannot leave. Transfer ownership or delete the group.'], 400);
        }

        $conversation->participants()->detach($request->user()->id);

        return response()->json(['message' => 'Left group successfully']);
    }
}
