<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebRTCSignal implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $callLogId,
        public int $fromUserId,
        public int $toUserId,
        public string $type,
        public mixed $data
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->toUserId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'call_log_id' => $this->callLogId,
            'from_user_id' => $this->fromUserId,
            'type' => $this->type,
            'data' => $this->data,
        ];
    }
}


