<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Feedback from the staff app */
class AppFeedback extends Model
{
    protected $table = 'app_feedback';

    public const TYPES = ['bug' => 'Bug / problem', 'idea' => 'Idea', 'other' => 'Other'];
    public const STATUSES = ['new' => 'New', 'seen' => 'Seen', 'done' => 'Done'];

    protected $fillable = [
        'user_id', 'type', 'message', 'screenshot_path', 'app_version', 'device', 'platform',
        'status', 'admin_note', 'emailed_at',
    ];

    protected $casts = ['emailed_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault(['name' => 'Deleted user']);
    }
}
