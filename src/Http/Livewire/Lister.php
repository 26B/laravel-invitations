<?php

namespace TwentySixB\LaravelInvitations\Http\Livewire;

use TwentySixB\LaravelInvitations\Models\Invitation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Displays collections of invitations.
 *
 * @todo Expand to toggle between received and sent.
 */
class Lister extends Component
{
    public Collection $invitations;

	public string $mode = 'received';

    /**
     * {@inheritDoc}
     */
    public function mount(): void
    {

		$
    }

	public function fetch() : Collection
	{
		if ($this->mode === 'received') {

			// TODO: Move query logic to Model.
			$this->invitations = Invitation::whereHasMorph(
				'invitable',
				'*',
				function (Builder $query, $type) {
					$query->where('data->email', '=', Auth::user()->email);
					$query->orWhere('data->user->id', '=', Auth::user()->id);
				}
			)->get();
		}

		if ($this->mode === 'sent') {


		}
	}

	public function loadForModel(Model $model) : Collection
	{
		$this->invitations = Invitation::whereHasMorph(
			'invitable',
			[$model::class],
			function (Builder $query, $type) {
				$query->where('data->email', '=', Auth::user()->email);
				$query->orWhere('data->user->id', '=', Auth::user()->id);
			}
		)->get();
	}

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        return view('livewire.invitations.list');
    }
}
