<?php

namespace App\Services;

use App\Models\Devolucao;
use App\Models\Venda;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DevolucaoService
{
    public function criar(array $dados): Devolucao
    {
        $venda = Venda::find($dados['id_venda']);

        if (!$venda) {
            throw ValidationException::withMessages(['message' => ['Venda não encontrada.']]);
        }

        if ($venda->status !== 'concluida') {
            throw ValidationException::withMessages(['message' => ['Somente vendas concluídas podem gerar devolução.']]);
        }

        if (Devolucao::where('id_venda', $dados['id_venda'])->exists()) {
            throw ValidationException::withMessages(['message' => ['Venda já possui devolução registrada.']]);
        }

        return DB::transaction(function () use ($dados) {
            // id_condicional permanece null nesta fase
            // data_devolucao gerado pelo banco (DEFAULT_GENERATED)
            $devolucao = Devolucao::create([
                'id_venda'   => $dados['id_venda'],
                'id_cliente' => $dados['id_cliente'] ?? null,
                'id_usuario' => $dados['id_usuario'] ?? null,
                'tipo'       => $dados['tipo'],
                'observacao' => $dados['observacao'] ?? null,
            ]);

            return $devolucao->fresh(['venda', 'cliente', 'usuario']);
        });
    }
}
