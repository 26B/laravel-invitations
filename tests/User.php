<?php

namespace TwentySixB\LaravelInvitations\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use TwentySixB\LaravelInvitations\Models\Concerns\HasInvitations;

/**
 * @property string $id
 * @property string $email
 */
class User extends Authenticatable
{
    use HasFactory, HasInvitations;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    public $timestamps = false;

    protected static function newFactory(): Factory
    {
        return new class extends Factory
        {
            protected $model = User::class;

            public function definition(): array
            {
                return [
                    'id' => fake()->uuid(),
                    'email' => 'user@example.com',
                ];
            }
        };
    }
}
