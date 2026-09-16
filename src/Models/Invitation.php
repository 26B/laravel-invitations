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
use TwentySixB\LaravelInvitations\Events\InvitationCreated;
use TwentySixB\LaravelInvitations\Events\InvitationExpired;
use TwentySixB\LaravelInvitations\Events\InvitationRejected;
use TwentySixB\LaravelInvitations\Exceptions\InvitationAlreadyAcceptedException;
use TwentySixB\LaravelInvitations\Exceptions\InvitationAlreadyExpiredException;
use TwentySixB\LaravelInvitations\Exceptions\InvitationAlreadyRejectedException;

/**
 * Undocumented class
 *
 * @property string $code
 * @property string $recipient_type
 * @property string $recipient_id
 * @property string $sender_type
 * @property string $sender_id
 * @property array $data
 * @property Carbon $accepted_at
 * @property Carbon $rejected_at
 * @property Carbon $expires_at
 * @property Carbon $expired_dispatched_at
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
        'expired_dispatched_at' => 'datetime',
    ];

    /**
     * Indicates properties that should not be user set.
     *
     * @var array<string>
     */
    protected $guarded = [
        'created_at',
        'id',
        'updated_at',
    ];

    protected static function newFactory(): Factory
    {
        return InvitationFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (Invitation $invitation): void {
            InvitationCreated::dispatch($invitation);
        });
    }

    public function accept(): self
    {
        $this->resolve('accepted_at');
        InvitationAccepted::dispatch($this);

        return $this;
    }

    public function reject(): self
    {
        $this->resolve('rejected_at');
        InvitationRejected::dispatch($this);

        return $this;
    }

    private function resolve(string $column): void
    {
        $updated = Invitation::query()
            ->whereKey($this->getKey())
            ->whereNull('accepted_at')
            ->whereNull('rejected_at')
            ->where('expires_at', '>', now())
            ->update([$column => now()]);

        if ($updated > 0) {
            $this->refresh();

            return;
        }

        $this->refresh();

        if ($this->isExpired()) {
            throw new InvitationAlreadyExpiredException($this->expires_at);
        }

        if ($this->isAccepted()) {
            throw new InvitationAlreadyAcceptedException($this->accepted_at);
        }

        throw new InvitationAlreadyRejectedException($this->rejected_at);
    }

    /**
     * Expire the invitation immediately, ahead of its defined `expires_at`.
     *
     * Sets `expires_at` to now, stamps `expired_dispatched_at` and dispatches a
     * single `InvitationExpired` event, so `invitations:dispatch-expired` will
     * not dispatch it again. Invitations that are already accepted or rejected
     * are left untouched.
     */
    public function expire(): self
    {
        if ($this->isResolved()) {
            return $this;
        }

        $now = now();

        $this->expires_at = $now;
        $this->expired_dispatched_at = $now;
        $this->save();

        InvitationExpired::dispatch($this);

        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->lte(now()) && ! $this->isResolved();
    }

    public function isPending(): bool
    {
        return $this->expires_at->gt(now()) && ! $this->isResolved();
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
     * Get the model the invitation is addressed to.
     */
    public function recipient()
    {
        return $this->morphTo();
    }

    /**
     * Get the model that sent the invitation.
     */
    public function sender()
    {
        return $this->morphTo();
    }

    public function scopePending(Builder $query): Builder
    {
        return $query
            ->whereNull('accepted_at')
            ->whereNull('rejected_at')
            ->where('expires_at', '>', now());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query
            ->whereNull('accepted_at')
            ->whereNull('rejected_at')
            ->where('expires_at', '<=', now());
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
