<?php

namespace TwentySixB\LaravelInvitations\Tests;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 */
class Recipient extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;
}
