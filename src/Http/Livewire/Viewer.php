<?php

namespace TwentySixB\LaravelInvitations\Http\Livewire;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use TwentySixB\LaravelInvitations\Models\Invitation;

/**
 * Livewire Component to accept invitations.
 */
class Viewer extends Component
{
    use AuthorizesRequests;

    /**
     * Contains the active invitation.
     */
    public Invitation|null $invitation;

	/**
	 * Route to redirect when no other is found.
	 */
	protected string $fallback_route;

	/**
     * {@inheritDoc}
     */
	public function mount(string $invitation_id)
	{
		$this->fallback_route = config('invitations.fallback_route');
		$this->invitation = Invitation::find($invitation_id);

		if (!$this->invitation instanceof Invitation) {
			return;
		}

		$this->authorize('view', $this->invitation);

		if ($this->invitation->isExpired()) {
			return config('invitations.actions.expired')::handle($this->invitation);
		}
	}

    /**
     * {@inheritDoc}
     */
    public function render()
    {
		return $this->invitation
			? view('livewire.invitations.viewer')
			: view('livewire.invitations.viewer-invitation-missing');
    }

    /**
     * Accept the invitation.
     */
    public function accept()
    {
        $this->authorize('view', $this->invitation);

        try {

			return config('invitations.actions.accept')::handle($this->invitation);

        } catch (\Throwable $th) {
            // TODO: Throw specific exception.
            throw $th;
        }
    }

    /**
     * Reject invitation.
     */
    public function reject()
    {
        $this->authorize('delete', $this->invitation);

		return config('invitations.actions.reject')::handle($this->invitation);
    }
}
