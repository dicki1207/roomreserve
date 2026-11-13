<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Middleware RBAC: Memisahkan akses setiap Role ke halaman & aksi yang berwenang saja.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $user = Auth::user();

        if ($user->status !== 'active') {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Akun Anda sedang dinonaktifkan.');
        }

        if (!in_array($user->role, $roles, true)) {
            AuditLog::create([
                'user_id'    => $user->id,
                'action'     => 'RBAC_ACCESS_DENIED (' . $request->path() . ')',
                'entity'     => 'SECURITY',
                'entity_id'  => $user->id,
                'created_at' => now(),
            ]);

            return redirect()->route('role.' . $user->role)
                ->with('error', 'Akses Ditolak (RBAC): Halaman tersebut hanya untuk role ' . strtoupper(implode('/', $roles)) . '.');
        }

        return $next($request);
    }
}
