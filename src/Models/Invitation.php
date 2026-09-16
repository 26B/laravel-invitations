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
 * @property string $invitable_type
 * @property string $invitable_id
 * @property string $author_type
 * @property string $author_id
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
        'modified_at',
    ];

    protected static function newFactory(): Factory
    {
        return InvitationFactory::new();
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
            ->where('expires_at', '>=', now())
            ->update([$column => now()]);

        if ($updated > 0) {
            $this->refresh();

            return;
        }

        $this->refresh();

        if ($this->isExpired()) {
            throw new InvitationExpiredException;
        }

        if ($column === 'accepted_at') {
            if ($this->isAccepted()) {
                throw new InvitationAlreadyAcceptedException;
            }

            throw new InvitationAlreadyRejectedException;
        }

        if ($this->isRejected()) {
            throw new InvitationAlreadyRejectedException;
        }

        throw new InvitationAlreadyAcceptedException;
    }

    public function expire(): self
    {
        $this->expires_at = now()->subHour();

        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->lt(now()) && ! $this->isResolved();
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
     * Get the invited model.
     */
    public function invitable()
    {
        return $this->morphTo();
    }

    /**
     * Get the model that sent the invitation.
     */
    public function author()
    {
        return $this->morphTo();
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
