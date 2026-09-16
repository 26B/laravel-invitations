<?php

namespace TwentySixB\LaravelInvitations\Tests;

use Illuminate\Database\Eloquent\Model;

class Invitable extends Model
{
    public $incrementing = false;

    public $timestamps = false;
}