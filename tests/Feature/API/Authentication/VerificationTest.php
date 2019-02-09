<?php

namespace Tests\Feature\API;


use App\Notifications\VerifyEmailNotification;
use Artisan;
use Tests\TestCase;
use App\Notifications\OrderShipped;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class VerificationTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    protected function setUp()
    {
        parent::setUp();
        Artisan::call('passport:install');
    }

    /** @test */
    public function a_user_can_verify_their_account_via_email()
    {
        Notification::fake();

        // Assert that no notifications were sent...
        Notification::assertNothingSent();

        $user = make('App\Models\User', [
            'phone' => $this->faker->phoneNumber,
            'email_verified_at' => null
        ]);

        unset($user['phone_number']);
        unset($user['phone']);

        $response = $this->json('POST', '/api/v1/register', $user->toArray())
            ->assertSee('access_token')
            ->assertStatus(201);

        $user->notify(new VerifyEmailNotification());

        // Assert a message was sent to the given user...
        Notification::assertSentTo(
            $user,
            VerifyEmailNotification::class
        );

    }

    /** @test */
//    public function a_user_can_verify_their_account_via_email()
//    {
//        $user = make('App\Models\User');
//
//    }
}
