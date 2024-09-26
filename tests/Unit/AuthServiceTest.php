<?php

namespace Tests\Unit;

use App\Repositories\Auth\AuthRepository;
use App\Services\Auth\AuthService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions; // Import the trait
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use DatabaseTransactions; // Use the trait for transaction handling

    public $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $repo = new AuthRepository();
        $this->authService = new AuthService($repo);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testLoginSuccess() // Added return type for better clarity
    {
        $request = new Request(['email' => 'admin@webschool.com', 'password' => 'password']);
        $result = $this->authService->login($request);

        $this->assertNotNull($result['access_token']);
        return $result;
    }

    public function testLoginFailure()
    {
        $this->expectException(\Exception::class);
        // Create a Request object instead of passing an array
        $request = new Request(['email' => 'khairiadam1@gmail.com', 'password' => 'wrongpassword']);
        $this->authService->login($request);
    }
}
