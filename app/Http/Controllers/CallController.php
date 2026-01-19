<?php

namespace App\Http\Controllers;

use App\Events\CallInitiated;
use App\Models\CallLog;
use App\Models\Conversation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class CallController extends Controller
{
    use AuthorizesRequests;
    public function initiate(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $request->validate([
            'type' => 'required|in:audio,video',
            'participant_ids' => 'required|array',
            'participant_ids.*' => 'exists:users,id',
        ]);

        $callLog = CallLog::create([
            'conversation_id' => $conversation->id,
            'initiated_by' => $request->user()->id,
            'participants' => $request->participant_ids,
            'type' => $request->type,
            'status' => 'initiated',
            'started_at' => now(),
        ]);

        // Broadcast to target users
        broadcast(new CallInitiated($callLog, $request->participant_ids));

        return response()->json($callLog, 201);
    }

    public function answer(Request $request, CallLog $callLog)
    {
        if (!in_array($request->user()->id, $callLog->participants)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $callLog->update(['status' => 'ongoing']);

        return response()->json($callLog);
    }

    public function reject(Request $request, CallLog $callLog)
    {
        if (!in_array($request->user()->id, $callLog->participants)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $callLog->update([
            'status' => 'rejected',
            'ended_at' => now(),
        ]);

        return response()->json($callLog);
    }

    public function end(Request $request, CallLog $callLog)
    {
        $request->validate([
            'duration' => 'required|integer|min:0',
        ]);

        $callLog->update([
            'status' => 'completed',
            'duration' => $request->duration,
            'ended_at' => now(),
        ]);

        return response()->json($callLog);
    }

    public function signal(Request $request, CallLog $callLog)
    {
        // WebRTC signaling relay
        $request->validate([
            'type' => 'required|in:offer,answer,ice-candidate',
            'data' => 'required',
            'target_user_id' => 'required|exists:users,id',
        ]);

        // Broadcast signal to target user
        broadcast(new \App\Events\WebRTCSignal(
            $callLog->id,
            $request->user()->id,
            $request->target_user_id,
            $request->type,
            $request->data
        ))->toOthers();

        return response()->json(['status' => 'signal sent']);
    }

    public function history(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $calls = $conversation->callLogs()
            ->with('initiator')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($calls);
    }
}
