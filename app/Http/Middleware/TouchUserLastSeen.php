<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TouchUserLastSeen
{
    /**
     * Only touch the timestamp if the last write is older than this, so that
     * a request storm cannot turn into a write storm.
     */
    private const THROTTLE_SECONDS = 30;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $this->shouldTouch($user)) {
            User::query()
                ->whereKey($user->id)
                ->update(['last_seen_at' => now()]);
        }

        return $next($request);
    }

    private function shouldTouch(User $user): bool
    {
        return $user->last_seen_at === null
            || $user->last_seen_at->lessThan(now()->subSeconds(self::THROTTLE_SECONDS));
    }
}
