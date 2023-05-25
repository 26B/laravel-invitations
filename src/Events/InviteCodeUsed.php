<?php

namespace  TwentySixB\LaravelInvitations\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Used when a user scans an invite code.
 */
class InviteCodeUsed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        protected Model $model
    ) {
    }

    /**
     * Returns the model.
     */
    public function getModel(): Model
    {
        return $this->model;
    }
}
