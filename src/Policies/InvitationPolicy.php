<?php

namespace TwentySixB\LaravelInvitations\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use TwentySixB\LaravelInvitations\Models\Invitation;

class InvitationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny($user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view($user, Invitation $invitation): bool
    {
        if (empty($invitation->data)) {
            return false;
        }

        if (($invitation->data['email'] ?? '') === $user->email) {
            return true;
        }

        if (($invitation->data['user']['id'] ?? '') === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create($user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update($user, Invitation $invitation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete($user, Invitation $invitation): bool
    {
        return $this->view($user, $invitation)
            || $invitation->author_id === $user->getKey();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore($user, Invitation $invitation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete($user, Invitation $invitation): bool
    {
        return false;
    }
}
