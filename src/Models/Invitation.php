<?php

namespace TwentySixB\LaravelInvitations\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use TwentySixB\LaravelInvitations\Database\Factories\InvitationFactory;
use TwentySixB\LaravelInvitations\Events\InvitationAccepted;
use TwentySixB\LaravelInvitations\Events\InvitationRejected;
use TwentySixB\LaravelInvitations\Exceptions\InvitationAlreadyAcceptedException;
use TwentySixB\LaravelInvitations\Exceptions\InvitationAlreadyRejectedException;
use TwentySixB\LaravelInvitations\Exceptions\InvitationExpiredException;

/**
 * Undocumented class
 *
 * @property string $code
 * @property string $author_id
 * @property array $data
 * @property Carbon $accepted_at
 * @property Carbon $rejected_at
 * @property Carbon $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Invitation extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => AsArrayObject::class,
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Indicates properties that should not be user set.
     *
     * @var array<string>
     */
    protected $guarded = [
        'created_at',
        'id',
        'modified_at',
    ];

    protected static function newFactory(): Factory
    {
        return InvitationFactory::new();
    }

    public function accept(): self
    {
        if ($this->isExpired() === true) {
            throw new InvitationExpiredException;
        }

        if ($this->isAccepted() === true) {
            throw new InvitationAlreadyAcceptedException;
        }

        if ($this->isRejected() === true) {
            throw new InvitationAlreadyRejectedException;
        }

        $this->accepted_at = now();
        $this->save();
        InvitationAccepted::dispatch($this);

        return $this;
    }

    public function reject(): self
    {
        if ($this->isExpired() === true) {
            throw new InvitationExpiredException;
        }

        if ($this->isAccepted() === true) {
            throw new InvitationAlreadyAcceptedException;
        }

        if ($this->isRejected() === true) {
            throw new InvitationAlreadyRejectedException;
        }

        $this->rejected_at = now();
        $this->save();
        InvitationRejected::dispatch($this);

        return $this;
    }

    public function expire(): self
    {
        $this->expires_at = now()->subHour();

        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->lt(now());
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isRejected(): bool
    {
        return $this->rejected_at !== null;
    }

    public function isResolved(): bool
    {
        return $this->isAccepted() || $this->isRejected();
    }

    /**
     * Get the parent invitable model (event or group).
     */
    public function invitable()
    {
        return $this->morphTo();
    }

    public function author()
    {
        return $this->belongsTo(config('invitations.models.user'));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('accepted_at')
            ->whereNull('rejected_at')
            ->where('expires_at', '>=', now());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query
            ->whereNull('accepted_at')
            ->whereNull('rejected_at')
            ->where('expires_at', '<', now());
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->whereNotNull('accepted_at');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->whereNotNull('rejected_at');
    }
}
