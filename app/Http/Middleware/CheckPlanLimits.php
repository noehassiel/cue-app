<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanLimits
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->subscribed()) {
            return $next($request);
        }

        if ($request->isMethod('POST') && str_contains($request->path(), '/transactions')) {
            $workspace = $request->route('workspace');

            if ($workspace) {
                $currentMonthCount = $workspace->transactions()
                    ->where('projected_date', 'like', now()->format('Y-m') . '%')
                    ->count();

                if ($currentMonthCount >= 50) {
                    return response()->json([
                        'message' => 'Free plan allows 50 transactions per month.',
                        'limit' => 50,
                        'current' => $currentMonthCount,
                    ], 402);
                }
            }
        }

        if ($request->isMethod('POST') && $request->path() === 'api/v1/workspaces') {
            $workspaceCount = $user->workspaces()->count();

            if ($workspaceCount >= 1) {
                return response()->json([
                    'message' => 'Free plan allows 1 workspace.',
                    'limit' => 1,
                    'current' => $workspaceCount,
                ], 402);
            }
        }

        return $next($request);
    }
}
