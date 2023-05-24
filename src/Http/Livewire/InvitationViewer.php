<?php

namespace TwentySixB\LaravelInvitations\Http\Livewire;

use TwentySixB\LaravelInvitations\Models\Invitation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use TwentySixB\LaravelInvitations\Actions\Accept;
use TwentySixB\LaravelInvitations\Actions\Expired;
use TwentySixB\LaravelInvitations\Actions\Reject;

/**
 * Livewire Component to accept invitations.
 */
class InvitationViewer extends Component
{
    use AuthorizesRequests;

    /**
     * Undocumented variable
     */
    public Invitation|null $invitation;

	protected string $fallback_route;

	public function mount(string $invitation_id)
	{
		$this->fallback_route = config('invitations.fallback_route');
		$this->invitation = Invitation::find($invitation_id);

		if (!$this->invitation instanceof Invitation) {
			return;
		}

		$this->authorize('view', $this->invitation);

		if ($this->invitation->isExpired()) {
			return Expired::handle($this->invitation);
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

			Accept::handle($this->invitation);

        } catch (\Throwable $th) {
            // TODO: Throw specific exception.
            throw $th;
        }
    }

    /**
     * Delete invitation.
     */
    public function reject()
    {
        $this->authorize('delete', $this->invitation);

		Reject::handle($this->invitation);
    }
}
