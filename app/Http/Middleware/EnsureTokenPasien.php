<?php

namespace App\Http\Middleware;

use App\Models\PasienProgram;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenPasien
{
    public function handle(Request $request, Closure $next): Response
    {
        $pasien = $request->user();

        if (! $pasien instanceof PasienProgram) {
            abort(403, 'Token ini tidak berlaku untuk aplikasi pasien.');
        }

        if ($pasien->status !== 'aktif') {
            abort(403, 'Program pasien tidak aktif. Hubungi petugas.');
        }

        return $next($request);
    }
}
