<?php

namespace App\Http\Controllers;

use App\Events\MessageReacted;
use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageMention;
use App\Models\MessageReaction;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    use AuthorizesRequests;
    
    public function index(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $messages = $conversation->messages()
            ->with(['user', 'attachments', 'reactions.user', 'mentions', 'replyTo'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($messages);
    }

    public function store(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $request->validate([
            'content' => 'required_without:attachments|string|max:10000',
            'type' => 'required|in:text,image,video,audio,sticker,file',
            'reply_to_id' => 'nullable|exists:messages,id',
            'mentions' => 'nullable|array',
            'mentions.*' => 'exists:users,id',
            'attachments' => 'nullable|array',
            'attachments.*.file' => 'required|file|max:51200', // 50MB
        ]);

        DB::beginTransaction();
        try {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $request->user()->id,
                'type' => $request->type,
                'content' => $request->content,
                'reply_to_id' => $request->reply_to_id,
            ]);

            // Handle attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('attachments', 'public');
                    
                    Attachment::create([
                        'message_id' => $message->id,
                        'type' => $this->getAttachmentType($file->getMimeType()),
                        'path' => $path,
                        'filename' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ]);
                }
            }

            // Handle mentions
            if ($request->mentions) {
                foreach ($request->mentions as $userId) {
                    MessageMention::create([
                        'message_id' => $message->id,
                        'user_id' => $userId,
                    ]);
                }
            }

            DB::commit();

            // Broadcast the message
            broadcast(new MessageSent($message))->toOthers();

            // Update conversation timestamp
            $conversation->touch();

            $message->load(['user', 'attachments', 'reactions', 'mentions']);
            return response()->json($message, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to send message'], 500);
        }
    }

    public function update(Request $request, Message $message)
    {
        $this->authorize('update', $message);

        if (!$message->canBeEditedBy($request->user())) {
            return response()->json(['error' => 'Message can no longer be edited'], 403);
        }

        $request->validate([
            'content' => 'required|string|max:10000',
        ]);

        $message->update([
            'content' => $request->content,
            'edited_at' => now(),
        ]);

        return response()->json($message);
    }

    public function destroy(Request $request, Message $message)
    {
        $this->authorize('delete', $message);

        $request->validate([
            'delete_for_everyone' => 'boolean',
        ]);

        if ($request->delete_for_everyone) {
            // Soft delete (delete for everyone)
            $message->delete();
        } else {
            // Just hide for this user (would need additional logic)
            $message->delete();
        }

        return response()->json(['message' => 'Message deleted successfully']);
    }

    public function react(Request $request, Message $message)
    {
        $request->validate([
            'emoji' => 'required|string|max:10',
        ]);

        $reaction = MessageReaction::updateOrCreate(
            [
                'message_id' => $message->id,
                'user_id' => $request->user()->id,
                'emoji' => $request->emoji,
            ]
        );

        broadcast(new MessageReacted($reaction, $message->conversation_id))->toOthers();

        return response()->json($reaction, 201);
    }

    public function removeReaction(Request $request, Message $message, string $emoji)
    {
        MessageReaction::where('message_id', $message->id)
            ->where('user_id', $request->user()->id)
            ->where('emoji', $emoji)
            ->delete();

        return response()->json(['message' => 'Reaction removed']);
    }

    public function typing(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $request->validate([
            'is_typing' => 'required|boolean',
        ]);

        broadcast(new UserTyping(
            $conversation->id,
            $request->user(),
            $request->is_typing
        ))->toOthers();

        return response()->json(['status' => 'ok']);
    }

    public function uploadMedia(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:102400', // 100MB
            'type' => 'required|in:image,video,audio,file',
        ]);

        $file = $request->file('file');
        $path = $file->store('media/' . $request->type, 'public');

        // Generate thumbnail for videos
        $thumbnailPath = null;
        if ($request->type === 'video') {
            // You would implement video thumbnail generation here
            // $thumbnailPath = $this->generateVideoThumbnail($path);
        }

        return response()->json([
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'thumbnail_path' => $thumbnailPath,
            'url' => asset('storage/' . $path),
        ]);
    }

    public function search(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $query = $request->input('q');

        $messages = $conversation->messages()
            ->where('content', 'ilike', "%{$query}%")
            ->with(['user', 'attachments'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($messages);
    }

    private function getAttachmentType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) return 'image';
        if (str_starts_with($mimeType, 'video/')) return 'video';
        if (str_starts_with($mimeType, 'audio/')) return 'audio';
        return 'file';
    }
}
