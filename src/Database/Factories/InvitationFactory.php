<?php

namespace TwentySixB\LaravelInvitations\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use TwentySixB\LaravelInvitations\Models\Invitation;

class InvitationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Invitation>
     */
    protected $model = Invitation::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->uuid(),
            'expires_at' => Carbon::instance($this->faker->dateTimeBetween('now', '+1 year')),
            'accepted_at' => null,
            'rejected_at' => null,
        ];
    }

    /**
     * Invitation that hasn't expired.
     */
    public function pending(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'expires_at' => Carbon::instance($this->faker->dateTimeBetween('now', '+1 year')),
            ];
        });
    }

    /**
     * Address the invitation to a recipient model.
     */
    public function forRecipient(Model $recipient): Factory
    {
        return $this->state(fn () => [
            'recipient_type' => $recipient->getMorphClass(),
            'recipient_id' => $recipient->getKey(),
        ]);
    }

    /**
     * Set the model that sends the invitation.
     */
    public function fromSender(Model $sender): Factory
    {
        return $this->state(fn () => [
            'sender_type' => $sender->getMorphClass(),
            'sender_id' => $sender->getKey(),
        ]);
    }

    /**
     * Expired invitation.
     */
    public function expired(): Factory
    {
        return $this->state(fn () => ['expires_at' => now()->subHour()]);
    }

    /**
     * Accepted invitation.
     */
    public function accepted(): Factory
    {
        return $this->state(fn () => ['accepted_at' => now()]);
    }

    /**
     * Rejected invitation.
     */
    public function rejected(): Factory
    {
        return $this->state(fn () => ['rejected_at' => now()]);
    }
}
