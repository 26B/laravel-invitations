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
    private function isRecipient(Model $user, Invitation $invitation): bool
    {
        return $this->matches($user, $invitation->recipient_type, $invitation->recipient_id);
    }

    /**
     * Whether the user sent the invitation.
     */
    private function isSender(Model $user, Invitation $invitation): bool
    {
        return $this->matches($user, $invitation->sender_type, $invitation->sender_id);
    }

    private function matches(Model $user, ?string $type, mixed $id): bool
    {
        return $type === $user->getMorphClass() && (string) $id === (string) $user->getKey();
    }
}
