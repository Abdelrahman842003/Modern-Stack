<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'due_date',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'date',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    /**
     * The possible status values.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_DONE = 'done';

    /**
     * Get the user that owns the task.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include pending tasks.
     *
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to only include done tasks.
     *
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeDone(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DONE);
    }

    /**
     * Scope a query to filter tasks by status.
     *
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeFilterByStatus(Builder $query, ?string $status = null): Builder
    {
        if ($status && in_array($status, [self::STATUS_PENDING, self::STATUS_DONE])) {
            return $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Scope a query to filter by due date range.
     *
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeFilterByDueDateRange(Builder $query, ?string $dueFrom, ?string $dueTo): Builder
    {
        if ($dueFrom) {
            $query->where('due_date', '>=', $dueFrom);
        }

        if ($dueTo) {
            $query->where('due_date', '<=', $dueTo);
        }

        return $query;
    }

    /**
     * Mark the task as done.
     */
    public function markAsDone(): bool
    {
        return $this->update(['status' => self::STATUS_DONE]);
    }

    /**
     * Check if the task is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the task is done.
     */
    public function isDone(): bool
    {
        return $this->status === self::STATUS_DONE;
    }
}
