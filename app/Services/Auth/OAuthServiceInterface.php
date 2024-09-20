<?php

namespace App\Services\Auth;

use App\Models\User;

interface OAuthServiceInterface
{
    public function handleGoogleCallback($googleUser): User;
    public function handleMicrosoftCallback($microsoftUser): User;
}
