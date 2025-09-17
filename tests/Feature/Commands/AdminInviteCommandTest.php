<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Mail\AdminInviteEmail;
use App\Models\AdminInvite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminInviteCommandTest extends TestCase {
    use RefreshDatabase;

    #[Test]
    public function it_creates_invites(): void
    {
        Mail::fake();

        $choices = $this->getChoices();

        $this->artisan('admin:invite')
            ->expectsQuestion('Select an action', $choices['action'])
            ->expectsQuestion('Invite Name (optional)', $choices['name'])
            ->expectsQuestion('Invite Description (optional)', $choices['description'])
            ->expectsQuestion('Invite Message (optional)', $choices['message'])
            ->expectsQuestion('Max uses', $choices['max_uses'])
            ->expectsQuestion('Set an invite expiry date?', $choices['expiry_answer'])
            ->expectsQuestion('Skip email verification', 'Yes')
            ->expectsConfirmation('Send invitation email to user?', 'no')
            ->assertExitCode(0);

        $this->assertDatabaseHas('admin_invites', [
            'name' => $choices['name'],
            'description' => $choices['description'],
            'message' => $choices['message'],
            'max_uses' => $choices['max_uses'],
            'skip_email_verification' => true,
        ]);

        $invite = AdminInvite::first();
        $this->assertNotNull($invite->invite_code);
        $this->assertTrue($invite->expires_at->isAfter(now()->addHours(23)));
        $this->assertTrue($invite->expires_at->isBefore(now()->addHours(25)));

        Mail::assertNothingSent();
    }

    #[Test]
    public function it_creates_invite_and_sends_email(): void
    {
        Mail::fake();

        $choices = $this->getChoices();

        $this->artisan('admin:invite')
            ->expectsQuestion('Select an action', $choices['action'])
            ->expectsQuestion('Invite Name (optional)', $choices['name'])
            ->expectsQuestion('Invite Description (optional)', $choices['description'])
            ->expectsQuestion('Invite Message (optional)', $choices['message'])
            ->expectsQuestion('Max uses', $choices['max_uses'])
            ->expectsQuestion('Set an invite expiry date?', $choices['expiry_answer'])
            ->expectsQuestion('Skip email verification', 'Yes')
            ->expectsConfirmation('Send invitation email to user?', 'yes')
            ->expectsQuestion('What email should the invite be sent to?', $choices['email'])
            ->assertExitCode(0);

        $this->assertDatabaseCount('admin_invites', 1);

        $invite = AdminInvite::first();

        Mail::assertSent(AdminInviteEmail::class, function (AdminInviteEmail $mail) use ($invite, $choices) {
            return $mail->hasTo($choices['email']) && $mail->invite->id === $invite->id;
        });
    }

    #[Test]
    public function it_reprompts_for_valid_email_on_validation_failure(): void
    {
        Mail::fake();

        $choices = $this->getChoices();

        $this->artisan('admin:invite')
            ->expectsQuestion('Select an action', $choices['action'])
            ->expectsQuestion('Invite Name (optional)', $choices['name'])
            ->expectsQuestion('Invite Description (optional)', $choices['description'])
            ->expectsQuestion('Invite Message (optional)', $choices['message'])
            ->expectsQuestion('Max uses', $choices['max_uses'])
            ->expectsQuestion('Set an invite expiry date?', $choices['expiry_answer'])
            ->expectsQuestion('Skip email verification', 'Yes')
            ->expectsConfirmation('Send invitation email to user?', 'yes')
            ->expectsQuestion('What email should the invite be sent to?', 'invalid-email')
            ->expectsOutput('The email must be a valid email address.')
            ->expectsQuestion('What email should the invite be sent to?', 'another@invalid')
            ->expectsOutput('The email must be a valid email address.')
            ->expectsQuestion('What email should the invite be sent to?', 'another@example.com')
            ->expectsOutput('The email must be a valid email address.')
            ->expectsQuestion('What email should the invite be sent to?', $choices['email'])
            ->assertExitCode(0);

        $this->assertDatabaseCount('admin_invites', 1);

        Mail::assertSent(AdminInviteEmail::class, function (AdminInviteEmail $mail) use ($choices) {
            return $mail->hasTo($choices['email']);
        });
    }

    protected function getChoices(): array {
        return [
            'action' => 'Create invite',
            'name' => fake()->name(),
            'description' => fake()->sentence(),
            'message' => fake()->sentence(),
            'max_uses' => fake()->numberBetween(1, 10),
            'expiry_answer' => 'Yes - expire after 24 hours',
            'email' => fake()->freeEmail(),
        ];
    }

}
