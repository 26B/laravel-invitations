<?php

namespace TwentySixB\LaravelInvitations\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use TwentySixB\LaravelInvitations\Models\Invitation;

class PurgeInvitations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invitations:purge
        {--all : Purge invitations in any state}
        {--accepted : Purge accepted invitations}
        {--rejected : Purge rejected invitations}
        {--force : Purge past-due invitations even if their expiry was never dispatched}
        {--days= : Only purge invitations whose expiry is this many days past (defaults to purge.expired_days)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purges stale invitations (expired by default)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days') ?? config('invitations.purge.expired_days', false);

        if ($days === false) {
            return Command::SUCCESS;
        }

        $query = Invitation::query()
            ->where('expires_at', '<', Carbon::now()->subDays((int) $days))
            ->orderBy('expires_at', 'asc')
            ->limit(50);

        $query->where(function (Builder $purge) {
            if ($this->option('accepted')) {
                $purge->orWhereNotNull('accepted_at');
            }

            if ($this->option('rejected')) {
                $purge->orWhereNotNull('rejected_at');
            }

            if (! $this->option('all') && ! $this->option('accepted') && ! $this->option('rejected')) {
                $purge->whereNull('accepted_at')->whereNull('rejected_at');

                if (! $this->option('force')) {
                    $purge->whereNotNull('expired_dispatched_at');
                }
            }
        });

        $query->delete();

        return Command::SUCCESS;
    }
}
