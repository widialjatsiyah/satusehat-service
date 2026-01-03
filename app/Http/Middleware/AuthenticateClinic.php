<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthenticateClinic
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next)
    {
        $key = $request->header('X-Clinic-Key');
        if (!$key) {
            return response()->json(['error' => 'Missing clinic key'], 401);
        }

        $clinic = \App\Models\Clinic::where('code', $key)->first();
        if (!$clinic || !$clinic->active) {
            return response()->json(['error' => 'Unauthorized clinic'], 401);
        }

        // Optional: verify HMAC signature header X-Signature using api_shared_secret
        if ($request->hasHeader('X-Signature')) {
            $sig = $request->header('X-Signature');
            $payload = (string) $request->getContent();
            $expected = hash_hmac('sha256', $payload, $clinic->api_shared_secret);
            if (!hash_equals($expected, $sig)) {
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $request->attributes->set('clinic', $clinic);

        return $next($request);
    }
}
