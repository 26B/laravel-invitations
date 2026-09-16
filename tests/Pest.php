<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use TwentySixB\LaravelInvitations\Tests\Invitable;
use TwentySixB\LaravelInvitations\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('.');

function invitable(): Invitable
{
    $invitable = new Invitable;
    $invitable->id = (string) Str::uuid();
    $invitable->save();

    return $invitable;
}
