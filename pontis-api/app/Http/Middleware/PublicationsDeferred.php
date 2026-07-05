<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Diferimiento de publicaciones a V2. El módulo de publicaciones (services,
 * needs, exploración y preview) queda dormido en V1: el código, modelos,
 * migraciones y datos se conservan, pero ningún rol puede acceder a estos
 * endpoints. Responde 403 para comunicar que el recurso existe pero está
 * deshabilitado en esta versión. Ver openspec: defer-publications-to-v2.
 */
class PublicationsDeferred
{
    public function handle(Request $request, Closure $next): Response
    {
        abort(403, 'Las publicaciones no están disponibles en esta versión.');
    }
}
