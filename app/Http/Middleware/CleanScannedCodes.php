<?php

namespace App\Http\Middleware;

use App\Support\ScannedCode;
use Closure;
use Illuminate\Http\Request;

/**
 * Old student cards hold {"std_ID":"SA11045"} in their QR. Every request
 * (staff app and web) gets the plain code instead, in the fields a scanned
 * or typed Student ID / card code is sent in, so every scan screen finds
 * the student without its own handling.
 */
class CleanScannedCodes
{
    /** Request fields that carry a scanned / typed student code */
    public const FIELDS = ['code', 'qr_code', 'student_code', 'custom_id', 'search', 'keyword', 'student_custom_id'];

    public function handle(Request $request, Closure $next)
    {
        foreach ([$request->query, $request->request] as $bag) {
            foreach (self::FIELDS as $field) {
                $value = $bag->get($field);

                if (is_string($value) && str_starts_with(ltrim($value), '{')) {
                    $bag->set($field, ScannedCode::clean($value));
                }
            }
        }

        // JSON bodies (staff app)
        if ($request->isJson()) {
            $json = $request->json();

            foreach (self::FIELDS as $field) {
                $value = $json->get($field);

                if (is_string($value) && str_starts_with(ltrim($value), '{')) {
                    $json->set($field, ScannedCode::clean($value));
                }
            }

            // bulk payments: payments[*].code
            $payments = $json->get('payments');
            if (is_array($payments)) {
                foreach ($payments as $i => $payment) {
                    if (is_array($payment) && is_string($payment['code'] ?? null) && str_starts_with(ltrim($payment['code']), '{')) {
                        $payments[$i]['code'] = ScannedCode::clean($payment['code']);
                    }
                }
                $json->set('payments', $payments);
            }
        }

        return $next($request);
    }
}
