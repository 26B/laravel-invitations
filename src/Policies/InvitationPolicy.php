<?php

namespace TwentySixB\LaravelInvitations\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;
use TwentySixB\LaravelInvitations\Models\Invitation;

class InvitationPolicy
{
    use HandlesAuthorization;

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
        return $this->isInvitable($user, $invitation)
            || $this->isAuthor($user, $invitation);
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
        return $this->isInvitable($user, $invitation)
            || $this->isAuthor($user, $invitation);
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
    private function isInvitable(Model $user, Invitation $invitation): bool
    {
        return $this->matches($user, $invitation->invitable_type, $invitation->invitable_id);
    }

    /**
     * Whether the user sent the invitation.
     */
    private function isAuthor(Model $user, Invitation $invitation): bool
    {
        return $this->matches($user, $invitation->author_type, $invitation->author_id);
    }

    private function matches(Model $user, ?string $type, mixed $id): bool
    {
        return $type === $user->getMorphClass() && (string) $id === (string) $user->getKey();
    }
}
