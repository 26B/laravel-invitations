<?php

namespace TwentySixB\LaravelInvitations\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TwentySixB\LaravelInvitations\Models\Invitation;

class InvitationFactory extends Factory
{
	/**
	 * The name of the factory's corresponding model.
	 *
	 * @var string
	 */
	protected $model = Invitation::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        /** @var HasFactory $invitable */
        $invitable = $this->faker->randomElement(config('invitations.invitables'));

		$user = config('invitations.models.user');

        return [
            'author_id' => $user::factory(),
            'code' => $this->faker->uuid(),
            'expires_at' => $this->faker->dateTime(now()->addYear()),
            'invitable_type' => $invitable,
            'invitable_id' => $invitable::factory(),
			'used' => $this->faker->boolean(),
        ];
    }

    /**
     * Invitation that hasn't expired.
     */
    public function unused(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
				'used' => false,
                'expires_at' => now()->addYear(),
            ];
        });
    }

    /**
     * Expired invitation.
     */
    public function expired(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'expires_at' => $this->faker->dateTime(now()->addYear()),
            ];
        });
    }
}
