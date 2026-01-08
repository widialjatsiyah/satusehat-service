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
        $code = $request->header('X-Clinic-Code');
        $secret = $request->header('X-Clinic-Secret');
        if (!$code || !$secret) {
            return response()->json(['error' => 'Missing clinic code or secret'], 401);
        }

        $clinic = \App\Models\Clinic::where('code', $code)->first();
        if (!$clinic || $clinic->api_shared_secret !== $secret) {
            return response()->json(['error' => 'Unauthorized clinic'], 401);
        }

        // Optional: verify HMAC signature header X-Signature using api_shared_secret
        // if ($request->hasHeader('X-Signature')) {
        //     $sig = $request->header('X-Signature');
        //     $payload = (string) $request->getContent();
        //     $expected = hash_hmac('sha256', $payload, $clinic->api_shared_secret);
        //     if (!hash_equals($expected, $sig)) {
        //         return response()->json(['error' => 'Invalid signature'], 401);
        //     }
        // }

        // Attach clinic to request for downstream use
        $request->attributes->set('clinic', $clinic);
//  dd($request->attributes->get('clinic'));
        // dd($request);
        return $next($request);
    }
}
