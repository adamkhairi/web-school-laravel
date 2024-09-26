<?php

namespace Tests\Feature\Api\V1;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    protected User $user;
    private const PASSWORD = 'password'; // Use a constant for the password

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create([
            'password' => Hash::make(self::PASSWORD),
        ]);

        // Assign the Student role to the test user
        $this->user->assignRole(RoleType::Student);
    }

    /**
     * Test successful user login.
     *
     * @return void
     */
    public function testSuccessfulLogin()
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => $this->user->email,
            'password' => self::PASSWORD,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    /**
     * Test login with invalid credentials.
     *
     * @return void
     */
    public function testLoginWithInvalidCredentials()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials',
            ]);
    }

    /**
     * Test successful user registration.
     *
     * @return void
     */
    public function testSuccessfulRegistration()
    {
        $password = self::PASSWORD; // Use the constant for the password
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
                'token',
                'token_type',
                'expires_in',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => $response['user']['email'],
        ]);
    }

    /**
     * Test registration with existing email.
     *
     * @return void
     */
    public function testRegistrationWithExistingEmail()
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => $this->faker->name,
            'email' => $this->user->email,
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test successful user logout.
     *
     * @return void
     */
    public function testSuccessfulLogout()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logout successful',
            ]);

        $this->assertGuest();
    }

    /**
     * Test getting user data.
     *
     * @return void
     */
    public function testGetUserData()
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
                'roles',
                'token',
                'token_type',
                'expires_in',
            ]);
    }

    /**
     * Test updating user profile.
     *
     * @return void
     */
    public function testUpdateProfile()
    {
        $newName = $this->faker->name;
        $newEmail = $this->faker->unique()->safeEmail;

        $response = $this->actingAs($this->user)->putJson('/api/v1/profile', [
            'name' => $newName,
            'email' => $newEmail,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Profile updated successfully',
                'user' => [
                    'name' => $newName,
                    'email' => $newEmail,
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => $newName,
            'email' => $newEmail,
        ]);
    }

    /**
     * Test sending password reset email.
     *
     * @return void
     */
    public function testSendPasswordResetEmail()
    {
        $response = $this->postJson('/api/v1/forgot-password', [
            'email' => $this->user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Password reset email sent',
            ]);

        // Assert that a password reset notification was sent
        $this->assertDatabaseHas('password_resets', [
            'email' => $this->user->email,
        ]);
    }

    /**
     * Test resetting password.
     *
     * @return void
     */
    public function testResetPassword()
    {
        // Generate a password reset token
        $token = Password::createToken($this->user);

        $newPassword = 'newpassword';
        $response = $this->postJson('/api/v1/reset-password', [
            'token' => $token,
            'email' => $this->user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Password reset successful',
            ]);

        // Assert that the user can log in with the new password
        $this->assertTrue(Hash::check($newPassword, $this->user->fresh()->password));
    }

    /**
     * Test enabling two-factor authentication.
     *
     * @return void
     */
    public function testEnableTwoFactorAuth()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/enable-2fa');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
            ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'name' => '2fa',
        ]);
    }

    /**
     * Test disabling two-factor authentication.
     *
     * @return void
     */
    public function testDisableTwoFactorAuth()
    {
        $this->user->createToken('2fa');

        $response = $this->actingAs($this->user)->postJson('/api/v1/disable-2fa');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Two-factor authentication disabled',
            ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'name' => '2fa',
        ]);
    }

    /**
     * Test verifying two-factor authentication.
     *
     * @return void
     */
    public function testVerifyTwoFactorAuth()
    {
        $token = $this->user->createToken('2fa')->plainTextToken;

        $response = $this->actingAs($this->user)->postJson('/api/v1/verify-2fa', [
            'token' => $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Two-factor authentication verified',
            ]);
    }

    /**
     * Test refreshing token.
     *
     * @return void
     */
    public function testRefreshToken()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
            ]);
    }

    /**
     * Test verifying email.
     *
     * @return void
     */
    public function testVerifyEmail()
    {
        // Mark the user's email as unverified
        $this->user->email_verified_at = null;
        $this->user->save();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $this->user->id, 'hash' => sha1($this->user->getEmailForVerification())]
        );

        $response = $this->get($verificationUrl);

        $response->assertRedirect('/home');

        $this->assertTrue($this->user->fresh()->hasVerifiedEmail());
    }

    /**
     * Test assigning a role to a user.
     *
     * @return void
     */
    public function testAssignRole()
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/users/{$this->user->id}/assign-role", [
                'role' => RoleType::Teacher->value,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Role assigned successfully',
            ]);

        $this->assertTrue($this->user->fresh()->hasRole(RoleType::Teacher));
    }

    /**
     * Test removing a role from a user.
     *
     * @return void
     */
    public function testRemoveRole()
    {
        $this->user->assignRole(RoleType::Teacher);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/users/{$this->user->id}/remove-role", [
                'role' => RoleType::Teacher->value,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Role removed successfully',
            ]);

        $this->assertFalse($this->user->fresh()->hasRole(RoleType::Teacher));
    }
}
