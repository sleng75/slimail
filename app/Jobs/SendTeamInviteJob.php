<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendTeamInviteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public string $email,
        public string $role,
        public Tenant $tenant,
        public User $inviter,
        public string $invitationUrl
    ) {}

    public function handle(): void
    {
        Mail::send('emails.team-invite', [
            'email' => $this->email,
            'role' => $this->role,
            'tenant' => $this->tenant,
            'inviter' => $this->inviter,
            'invitationUrl' => $this->invitationUrl,
        ], function ($message) {
            $message->to($this->email)
                ->subject("Invitation à rejoindre {$this->tenant->name} sur SliMail");
        });
    }

    public function tags(): array
    {
        return ['email', 'invitation', "tenant:{$this->tenant->id}"];
    }
}
