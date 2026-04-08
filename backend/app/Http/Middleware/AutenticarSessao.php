<?php

namespace App\Http\Middleware;

use App\Models\Sessao;
use Closure;
use Illuminate\Http\Request;

class AutenticarSessao
{
    public function handle(Request $request, Closure $next, string $perfil = null)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token de autenticação não fornecido.'], 401);
        }

        $tokenHash = hash('sha256', $token);

        $sessao = Sessao::with('usuario')
            ->where('token_hash', $tokenHash)
            ->first();

        if (!$sessao) {
            return response()->json(['message' => 'Sessão inválida ou expirada.'], 401);
        }

        // Verificar expiração
        if (now()->greaterThan($sessao->expira_em)) {
            $sessao->delete();
            return response()->json(['message' => 'Sessão expirada. Faça login novamente.'], 401);
        }

        // Verificar IP (RNF04)
        if ($sessao->ip && $sessao->ip !== $request->ip()) {
            $sessao->delete();
            return response()->json(['message' => 'Sessão inválida: IP diferente do registrado.'], 401);
        }

        $usuario = $sessao->usuario;

        if (!$usuario || !$usuario->ativo) {
            return response()->json(['message' => 'Usuário inativo.'], 403);
        }

        // Verificar perfil exigido para a rota
        if ($perfil && $usuario->perfil !== $perfil) {
            return response()->json(['message' => 'Acesso negado: perfil insuficiente.'], 403);
        }

        // Injetar usuário e sessão no request
        $request->attributes->set('usuario', $usuario);
        $request->attributes->set('sessao', $sessao);

        return $next($request);
    }
}
