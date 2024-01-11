<?php

namespace TwentySixB\LaravelInvitations\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use TwentySixB\LaravelInvitations\Models\Invitation;

class Filter {

	public static function handle(iterable|null $models, bool $user)
	{
		$scope = null;

		if ($user === true) {
			$scope = function (Builder $query, $type) {
				$query->where('data->email', '=', Auth::user()->email);
				$query->orWhere('data->user->id', '=', Auth::user()->id);
			};
		} elseif ($models->isEmpty() === false) {
			$scope = function (Builder $query, $type) use ($models) {
				$query->where('invitable_id', $models->map(fn($model) => $model->getKey()));
			};
		}

		$query = Invitation::whereHasMorph(
			'invitable',
			$models->isEmpty() ? '*' : $models->map(fn($model) => get_class($model))->toArray(),
			$scope
		)
		->where('used', false)
		->where('expires_at', '>', now());

		return $query->get();
	}
}
