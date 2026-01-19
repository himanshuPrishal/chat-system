<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    use AuthorizesRequests;
    
    public function index(Request $request)
    {
        $conversations = $request->user()
            ->conversations()
            ->with(['participants', 'messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->get()
            ->map(function ($conversation) use ($request) {
                $lastMessage = $conversation->messages->first();
                $unreadCount = $conversation->getUnreadCount($request->user());
                
                return [
                    'id' => $conversation->id,
                    'type' => $conversation->type,
                    'name' => $conversation->type === 'group' 
                        ? $conversation->name 
                        : $this->getDirectChatName($conversation, $request->user()),
                    'avatar' => $conversation->type === 'group'
                        ? $conversation->avatar
                        : $this->getDirectChatAvatar($conversation, $request->user()),
                    'last_message' => $lastMessage,
                    'unread_count' => $unreadCount,
                    'updated_at' => $conversation->updated_at,
                ];
            });

        return response()->json($conversations);
    }

    public function show(Conversation $conversation)
    {
        $this->authorize('view', $conversation);
        
        $conversation->load(['participants', 'messages.user', 'messages.attachments', 'messages.reactions']);
        
        return response()->json($conversation);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:direct,group',
            'name' => 'required_if:type,group|nullable|string|max:255',
            'description' => 'nullable|string',
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'exists:users,id',
        ]);

        // Check for existing direct conversation
        if ($request->type === 'direct' && count($request->participant_ids) === 1) {
            $existingConversation = $this->findDirectConversation(
                $request->user()->id,
                $request->participant_ids[0]
            );

            if ($existingConversation) {
                return response()->json($existingConversation);
            }
        }

        DB::beginTransaction();
        try {
            $conversation = Conversation::create([
                'type' => $request->type,
                'created_by' => $request->user()->id,
                'name' => $request->name,
                'description' => $request->description,
            ]);

            // Add creator
            $conversation->participants()->attach($request->user()->id, [
                'role' => 'admin',
                'joined_at' => now(),
            ]);

            // Add other participants
            foreach ($request->participant_ids as $participantId) {
                if ($participantId != $request->user()->id) {
                    $conversation->participants()->attach($participantId, [
                        'role' => 'member',
                        'joined_at' => now(),
                    ]);
                }
            }

            DB::commit();

            $conversation->load('participants');
            return response()->json($conversation, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create conversation'], 500);
        }
    }

    public function update(Request $request, Conversation $conversation)
    {
        $this->authorize('update', $conversation);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $conversation->update($request->only(['name', 'description']));

        return response()->json($conversation);
    }

    public function destroy(Conversation $conversation)
    {
        $this->authorize('delete', $conversation);

        $conversation->delete();

        return response()->json(['message' => 'Conversation deleted successfully']);
    }

    public function markAsRead(Request $request, Conversation $conversation)
    {
        $request->user()->conversations()->updateExistingPivot($conversation->id, [
            'last_read_at' => now(),
        ]);

        return response()->json(['message' => 'Marked as read']);
    }

    public function searchUsers(Request $request)
    {
        $query = $request->input('q');
        
        $users = User::where('id', '!=', $request->user()->id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'ilike', "%{$query}%")
                  ->orWhere('email', 'ilike', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'email', 'avatar']);

        return response()->json($users);
    }

    private function findDirectConversation(int $userId1, int $userId2)
    {
        return Conversation::where('type', 'direct')
            ->whereHas('participants', function ($query) use ($userId1) {
                $query->where('user_id', $userId1);
            })
            ->whereHas('participants', function ($query) use ($userId2) {
                $query->where('user_id', $userId2);
            })
            ->first();
    }

    private function getDirectChatName(Conversation $conversation, User $currentUser)
    {
        $otherUser = $conversation->participants->where('id', '!=', $currentUser->id)->first();
        return $otherUser ? $otherUser->name : 'Unknown User';
    }

    private function getDirectChatAvatar(Conversation $conversation, User $currentUser)
    {
        $otherUser = $conversation->participants->where('id', '!=', $currentUser->id)->first();
        return $otherUser ? $otherUser->avatar_url : null;
    }
}
