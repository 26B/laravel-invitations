<?php

namespace TwentySixB\LaravelInvitations\Livewire;

use TwentySixB\LaravelInvitations\Models\Invitation;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

/**
 * Displays collections of invitations.
 *
 * @todo Expand to toggle between received and sent.
 */
class Lister extends Component
{
	/**
	 * Contains the invitations.
	 */
    public Collection $invitations;

	public string $mode = 'received';

	public iterable|null $target = null;

    /**
     * {@inheritDoc}
     */
    public function mount(): void
    {
		$this->loadForModel(
			$this->target,
			$this->mode === 'received' ? true : false
		);
    }

	/**
	 * Populate the invitations collection.
	 *
	 * @param iterable|null $models
	 * @param boolean $user
	 * @return void
	 *
	 * @todo Refactor using actions.
	 */
	protected function loadForModel(iterable|null $models, bool $user) : void
	{
		$models = collect($models);

		$this->invitations = config('invitations.actions.filter')::handle($models, $user);
	}

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        return view('livewire.invitations.list');
    }

	public function delete(Invitation $invitation): void
	{
		config('invitations.actions.delete')::handle($invitation);

		$this->loadForModel(
			$this->target,
			$this->mode === 'received' ? true : false
		);
	}
}
