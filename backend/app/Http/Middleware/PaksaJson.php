<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memaksa semua permintaan ke /api/... diperlakukan sebagai permintaan JSON.
 *
 * Tanpa ini, permintaan yang tidak menyebut "Accept: application/json" (misalnya
 * dari curl, atau yang headernya hilang di tengah jalan) dianggap permintaan browser:
 * saat belum login, Laravel mencoba mengalihkan ke halaman login yang tidak ada
 * dan berakhir dengan error 500, bukan pesan JSON 401.
 *
 * Middleware ini harus didaftarkan sebagai middleware GLOBAL (bootstrap/app.php).
 * Bila ditaruh di grup rute, ia berjalan sesudah middleware auth dan tidak berguna.
 */
class PaksaJson
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/*')) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}