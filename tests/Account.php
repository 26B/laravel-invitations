<?php

namespace TwentySixB\LaravelInvitations\Tests;

use Illuminate\Database\Eloquent\Model;
use TwentySixB\LaravelInvitations\Models\Concerns\HasInvitations;

/**
 * @property int $id
 */
class Account extends Model
{
    use HasInvitations;

    protected $guarded = [];

    public $timestamps = false;
}
