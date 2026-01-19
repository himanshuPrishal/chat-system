<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'type',
        'content',
        'reply_to_id',
        'edited_at',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
    ];

    // Relationships
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(MessageMention::class);
    }

    public function mentionedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'message_mentions');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    // Scopes
    public function scopeText($query)
    {
        return $query->where('type', 'text');
    }

    public function scopeMedia($query)
    {
        return $query->whereIn('type', ['image', 'video', 'audio', 'file']);
    }

    // Helper methods
    public function isEdited(): bool
    {
        return !is_null($this->edited_at);
    }

    public function canBeEditedBy(User $user): bool
    {
        return $this->user_id === $user->id 
            && $this->created_at->diffInMinutes(now()) <= 15;
    }
}
