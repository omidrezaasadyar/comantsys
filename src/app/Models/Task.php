<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    protected $fillable = [
        'user_id',
        'created_by',
        'title',
        'due_date',
        'is_done',
        'completion_note',
        'done_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'is_done'  => 'boolean',
        'done_at'  => 'datetime',
    ];

    protected static function booted(): void
    {
        // Auto-manage done_at based on is_done transitions.
        static::saving(function (Task $task): void {
            if ($task->is_done && $task->done_at === null) {
                $task->done_at = now();
            }

            if (! $task->is_done) {
                $task->done_at = null;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
