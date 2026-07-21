<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Uppercase semua input teks user sebelum validasi (T2.4).
        $middleware->web(append: [
            \App\Http\Middleware\UppercaseInput::class,
            \App\Http\Middleware\ForceChangePassword::class,
            \App\Http\Middleware\RequireOrtuProfile::class,
        ]);
        // Alias middleware RBAC (rules.md §1.5) — dipakai sebagai `role:admin` dll.
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
        // Trust proxy dari Cloudflare Tunnel agar X-Forwarded-Proto: https dibaca.
        // Mencegah mixed-content blokir asset (CSS/JS) saat akses via tunnel HTTPS.
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
