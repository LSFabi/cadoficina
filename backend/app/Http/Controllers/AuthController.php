<?php

namespace App\Http\Controllers;

use App\Models\Sessao;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // RF_SEC01 — Login
    public function login(Request $request)
    {
        $validated = $request->validate([
            'login'      => 'required|string',
            'senha'      => 'required|string',
            'dispositivo'=> 'nullable|string|max:200',
        ]);

        $senhaHash = hash('sha256', $validated['senha']);

        $usuario = Usuario::where('login', $validated['login'])
            ->where('senha_hash', $senhaHash)
            ->where('ativo', true)
            ->first();

        if (!$usuario) {
            return response()->json(['message' => 'Credenciais inválidas.'], 401);
        }

        $token  = Str::random(64);
        $tokenHash = hash('sha256', $token);

        // Delete + create atômicos: se create falhar, sessões anteriores não são perdidas
        $sessao = DB::transaction(function () use ($usuario, $request, $validated, $tokenHash) {
            Sessao::where('id_usuario', $usuario->id_usuario)->delete();

            return Sessao::create([
                'id_usuario'  => $usuario->id_usuario,
                'token_hash'  => $tokenHash,
                'ip'          => $request->ip(),
                'dispositivo' => $validated['dispositivo'] ?? $request->userAgent(),
                'expira_em'   => now()->addHours(8),
            ]);
        });

        return response()->json([
            'token'    => $token,
            'expira_em'=> $sessao->expira_em,
            'usuario'  => [
                'id_usuario' => $usuario->id_usuario,
                'nome'       => $usuario->nome,
                'perfil'     => $usuario->perfil,
            ],
        ]);
    }

    // RF_SEC02 — Logout
    public function logout(Request $request)
    {
        $token     = $request->bearerToken();
        $tokenHash = hash('sha256', $token ?? '');

        Sessao::where('token_hash', $tokenHash)->delete();

        return response()->json(['message' => 'Sessão encerrada com sucesso.']);
    }

    // Renovar sessão (opcional)
    public function me(Request $request)
    {
        return response()->json([
            'usuario' => $request->attributes->get('usuario'),
        ]);
    }
}
