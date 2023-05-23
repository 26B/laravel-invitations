<?php

namespace TwentySixB\LaravelInvitations\Http\Livewire;

use TwentySixB\LaravelInvitations\Events\InvitationAccepted;
use TwentySixB\LaravelInvitations\Models\Invitation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Livewire Component to accept invitations.
 */
class InvitationViewer extends Component
{
    use AuthorizesRequests;

    /**
     * Undocumented variable
     */
    public Invitation $invitation;

	public function mount()
	{
		$this->authorize('view', $this->invitation);

		if ($this->invitation->isExpired()) {

			// TODO: Must allow customization for this.
			return redirect('dashboard')
                ->with('toaster', new \App\ToasterNotification(
                    'temporary',
                    __('Invitation expired'),
                    __('Your invitation has expired :time', ['time' => $this->invitation->expires_at->diffForHumans()])

                ));
		}
	}

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        return view('livewire.invitation-viewer');
    }

    /**
     * Accept the invitation.
     */
    public function accept()
    {
        $this->authorize('view', $this->invitation);

        try {
            $invite_model = $this->invitation->invitable();
            InvitationAccepted::dispatch($this->invitation);
            $this->invitation->delete();

            // Automaticaly detect a redirect to route.
            $model_name = Str::lower(class_basename($invite_model->getRelated()));

            return redirect()
                ->route("{$model_name}.show", [$model_name => $invite_model->invitable()->first()]);
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
        $this->invitation->delete();

        return redirect()->route('dashboard');
    }
}
