<?php

namespace TwentySixB\LaravelInvitations\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

/**
 * Inviting a non-existent user.
 */
class InviteByEmail
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user that made the invitation.
     */
    protected User $inviter;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        protected string $email,
        protected Model $target
    ) {
        $this->inviter = Auth::user();
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getTarget(): Model
    {
        return $this->target;
    }

    public function getInviter(): User
    {
        return $this->inviter;
    }
}
