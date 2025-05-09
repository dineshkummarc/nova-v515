<?php

namespace Laravel\Nova\Http\Controllers\Fortify;

use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController as Controller;

class PasswordResetLinkController extends Controller
{
    /**
     * Get the broker to be used during password reset.
     */
    #[\Override]
    protected function broker(): PasswordBroker
    {
        return Password::broker(config('nova.passwords'));
    }
}
