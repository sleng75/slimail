<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public User $user
    ) {}

    public function handle(): void
    {
        $user = $this->user;
        $tenant = $user->tenant;
        $subscription = $tenant?->subscription;

        if (!$user->email) {
            return;
        }

        Mail::send('emails.welcome', [
            'user' => $user,
            'tenant' => $tenant,
            'subscription' => $subscription,
        ], function ($message) use ($user) {
            $message->to($user->email, $user->name)
                ->subject('Bienvenue sur SliMail !');
        });
    }

    public function tags(): array
    {
        return ['email', 'welcome', "user:{$this->user->id}"];
    }
}
