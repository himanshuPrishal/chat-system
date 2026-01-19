<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallLog extends Model
{
    protected $fillable = [
        'conversation_id',
        'initiated_by',
        'participants',
        'type',
        'status',
        'duration',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'participants' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function scopeAudio($query)
    {
        return $query->where('type', 'audio');
    }

    public function scopeVideo($query)
    {
        return $query->where('type', 'video');
    }

    public function scopeMissed($query)
    {
        return $query->where('status', 'missed');
    }
}
