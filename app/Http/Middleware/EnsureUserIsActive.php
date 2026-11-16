<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user && ! $user->isActive()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = match ($user->status) {
                'pending' => __('Your account is waiting for admin activation.'),
                'rejected' => __('Your account has been rejected.'),
                'deactivated' => __('Your account has been deactivated.'),
                default => __('Your account is not active.'),
            };

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
