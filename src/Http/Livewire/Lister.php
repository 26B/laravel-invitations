<?php

namespace TwentySixB\LaravelInvitations\Http\Livewire;

use TwentySixB\LaravelInvitations\Models\Invitation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use TwentySixB\LaravelInvitations\Actions\Delete;

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
	 */
	protected function loadForModel(iterable|null $models, bool $user) : void
	{
		$scope = null;

		if ($user === true) {
			$scope = function (Builder $query, $type) {
				$query->where('data->email', '=', Auth::user()->email);
				$query->orWhere('data->user->id', '=', Auth::user()->id);
			};
		}

		$this->invitations = Invitation::whereHasMorph(
				'invitable',
				is_iterable($models) ? $models : '*',
				$scope
			)
			->where('used', false)
			->where('expires_at', '>', now())
			->get();
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
		Delete::handle($invitation);

		$this->loadForModel(
			$this->target,
			$this->mode === 'received' ? true : false
		);
	}
}
