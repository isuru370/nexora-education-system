<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Staff app logins end after STAFF_TOKEN_IDLE_DAYS without using the app
        // (counted from the last use, so daily users never notice). A lost phone
        // stops working on its own. Parent app tokens have their own rule.
        Sanctum::authenticateAccessTokensUsing(function ($token, bool $isValid) {
            if (!$isValid || !$token->tokenable instanceof User) {
                return $isValid;
            }

            $days = (int) config('sanctum.staff_idle_days', 7);
            $lastUsed = $token->last_used_at ?? $token->created_at;

            if ($days > 0 && $lastUsed !== null && $lastUsed->lt(now()->subDays($days))) {
                $token->delete();

                return false;
            }

            return true;
        });
    }
}
