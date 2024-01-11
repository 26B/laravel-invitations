<?php

namespace TwentySixB\LaravelInvitations\Livewire;

use App\Models\Model;
use App\Models\User;
use App\Support\UserSuggestions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use TwentySixB\LaravelInvitations\Events\InviteByEmail;
use TwentySixB\LaravelInvitations\Events\UserInvited;

class InviteUsers extends Component
{
    /**
     * Determine if management options should be displayed
     */
    public bool $is_admin = false;

    /**
     * Determine to what the user is being invited to.
     */
    public Model $target;

    /**
     * The search query.
     */
    public string $query = '';

    public bool $is_email = false;

    public bool $is_valid = false;

    protected array $rules = [
        'query' => ['required', 'min:3'],
    ];

    /**
     * List of suggestions
     *
     * @todo Rename to suggestions.
     */
    public Collection $recommended;

    /**
     * Internal suggestions.
     */
    public Collection $suggestions;

    public function mount($target)
    {
        $this->recommended = new Collection();
        $this->target = $target;

        // TODO: Cache the results for a minute or two.
        $suggested = (new UserSuggestions())
            ->fromContacts()
            ->fromFollowers()
            ->fromGroups()
            ->fromEvents(2)
            ->exclude(
                $this->target->users()
                    ->get(['users.id'])
                    ->pluck('id')
            )
            ->get(limit: 6, random: true);

        $this->suggestions = $suggested;
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application
    {
        return view('livewire.invite-users');
    }

    /**
     * Action to search for users.
     *
     * @todo Filter out users already on target.
     */
    public function search(): void
    {
        $this->recommended = collect();
        $this->is_valid = false;
        $this->is_email = false;

        $this->validate();
        $this->is_valid = true;

        $this->is_email = filter_var($this->query, FILTER_VALIDATE_EMAIL);
        $include = [];

        if ($this->is_email === false) {
            $include = Auth::user()
                ->followers()
                ->where('name', 'like', '%'.$this->query.'%')
                ->get()
                ->modelKeys();

            $contacts = Auth::user()
                ->quickContacts()
                ->where('name', 'like', '%'.$this->query.'%')
                ->get()
                ->modelKeys();

            $include = array_merge($include, $contacts);
        }

        $this->recommended = User::where('email', $this->query)
            ->orWhere('name', $this->query)
            ->orWhereIn('id', $include)
            ->limit(6)
            ->get();
    }

    /**
     * Action to invite a user.
     *
     * Triggers an UserInvited event.
     */
    public function invite(User $user): void
    {
        UserInvited::dispatch($user, $this->target);

        $this->recommended = $this->recommended->reject(fn (User $row) => $row->id === $user->getKey());
        $this->suggestions = $this->suggestions->reject(fn (User $row) => $row->id === $user->getKey());

        session()->flash(
            'message',
            __('Invitation sent to :name', ['name' => $user->name])
        );

        $this->reset([
            'is_email',
            'query',
            'is_valid',
        ]);
    }

    /**
     * Action to send invitation to a non-existing user.
     */
    public function sendInvite(): void
    {
        InviteByEmail::dispatch($this->query, $this->target);
        session()->flash('message', __('Invitation sent.'));

        $this->reset([
            'is_email',
            'query',
            'is_valid',
        ]);
    }
}
