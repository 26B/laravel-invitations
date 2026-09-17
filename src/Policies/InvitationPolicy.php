<?php

namespace TwentySixB\LaravelInvitations\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;
use TwentySixB\LaravelInvitations\Models\Invitation;

/**
 * Grants `view` and `delete` to the invitation's sender and recipient, and
 * `create` to everyone; every other ability is denied.
 *
 * Extend the rules without reimplementing them by subclassing this policy and
 * overriding {@see before()}:
 *
 *     class AppInvitationPolicy extends InvitationPolicy
 *     {
 *         public function before(Model $user, string $ability, mixed ...$arguments): ?bool
 *         {
 *             return $user->hasRole('admin') ? true : null;
 *         }
 *     }
 *
 * Register the subclass with `Gate::policy(Invitation::class, AppInvitationPolicy::class)`
 * from a service provider that boots after the package's.
 */
class InvitationPolicy
{
    use HandlesAuthorization;

    /**
     * Run before every ability check to widen or tighten the rules below.
     *
     * Return `true` to allow, `false` to deny, or `null` to fall through to the
     * ability method. `$arguments` holds the arguments the ability was checked
     * with: an `Invitation` for `view`/`delete`, the class string for
     * `create`/`viewAny`.
     */
    public function before(Model $user, string $ability, mixed ...$arguments): ?bool
    {
        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Model $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Model $user, Invitation $invitation): bool
    {
        return $this->isRecipient($user, $invitation)
            || $this->isSender($user, $invitation);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Model $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Model $user, Invitation $invitation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Model $user, Invitation $invitation): bool
    {
        return $this->isRecipient($user, $invitation)
            || $this->isSender($user, $invitation);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Model $user, Invitation $invitation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Model $user, Invitation $invitation): bool
    {
        return false;
    }

    /**
     * Whether the user is the model the invitation is addressed to.
     */
    protected function isRecipient(Model $user, Invitation $invitation): bool
    {
        return $this->matches($user, $invitation->recipient_type, $invitation->recipient_id);
    }

    /**
     * Whether the user sent the invitation.
     */
    protected function isSender(Model $user, Invitation $invitation): bool
    {
        return $this->matches($user, $invitation->sender_type, $invitation->sender_id);
    }

    protected function matches(Model $user, ?string $type, mixed $id): bool
    {
        return $type === $user->getMorphClass() && (string) $id === (string) $user->getKey();
    }
}
