<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use TwentySixB\LaravelInvitations\Tests\Recipient;
use TwentySixB\LaravelInvitations\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('.');

function recipient(): Recipient
{
    $recipient = new Recipient;
    $recipient->id = (string) Str::uuid();
    $recipient->save();

    return $recipient;
}
