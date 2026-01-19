<?php

namespace App\Events;

use App\Models\CallLog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallInitiated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CallLog $callLog,
        public array $targetUserIds
    ) {
        $this->callLog->load('initiator');
    }

    public function broadcastOn(): array
    {
        $channels = [];
        foreach ($this->targetUserIds as $userId) {
            $channels[] = new PrivateChannel('user.' . $userId);
        }
        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'call' => $this->callLog->toArray(),
            'offer' => null, // Will be set by WebRTC
        ];
    }
}
