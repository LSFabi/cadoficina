<?php

namespace App\Services;

use App\Models\Promissoria;
use App\Models\SequenciaDocumento;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PromissoriaService
{
    // RF_F05 — Emitir promissória com numeração sequencial (RN18)
    public function emitir(array $dados): Promissoria
    {
        // RN12 — exatamente UMA origem
        if (!empty($dados['id_venda']) && !empty($dados['id_condicional'])) {
            throw ValidationException::withMessages(['message' => ['Promissória deve ter apenas uma origem: venda OU condicional.']]);
        }

        if (empty($dados['id_venda']) && empty($dados['id_condicional'])) {
            throw ValidationException::withMessages(['message' => ['Informe a origem da promissória: id_venda ou id_condicional.']]);
        }

        return DB::transaction(function () use ($dados) {
            $numeroDoc = $this->gerarNumero(now()->year);

            $promissoria = Promissoria::create([
                'id_venda'        => $dados['id_venda'] ?? null,
                'id_condicional'  => $dados['id_condicional'] ?? null,
                'numero_documento'=> $numeroDoc,
                'valor_total'     => $dados['valor_total'],
                'data_vencimento' => $dados['data_vencimento'],
                'status'          => 'pendente',
                'status_documento'=> 'gerado',
            ]);

            return $promissoria->fresh(['venda', 'condicional']);
        });
    }

    // RF_F05b — Acordo mãe-filha (RN18/RN19)
    public function acordo(Promissoria $mae, array $dados): array
    {
        if (!in_array($mae->status, ['pendente', 'carencia', 'juridico'])) {
            throw ValidationException::withMessages(['message' => ['Apenas promissórias pendentes, em carência ou no jurídico podem gerar acordo.']]);
        }

        // Verificar limite de sufixos (RN18 — máx. 26: A-Z)
        $sufixosUsados = Promissoria::where('id_promissoria_origem', $mae->id_promissoria)
            ->pluck('sufixo_acordo')
            ->toArray();

        $proximoSufixo = $this->proximoSufixo($sufixosUsados);

        return DB::transaction(function () use ($mae, $dados, $proximoSufixo) {
            // Filha herda o ano da mãe (RN18)
            $anoMae    = substr($mae->numero_documento, -4);
            $baseNumero = explode('/', $mae->numero_documento)[0];
            $numeroFilha = $baseNumero . '-' . $proximoSufixo . '/' . $anoMae;

            // Preservar status_anterior da mãe (RN19)
            $mae->update([
                'status'          => 'substituida',
                'status_anterior' => $mae->status,
            ]);

            $filha = Promissoria::create([
                'id_venda'             => $mae->id_venda,
                'id_condicional'       => $mae->id_condicional,
                'id_promissoria_origem'=> $mae->id_promissoria,
                'numero_documento'     => $numeroFilha,
                'sufixo_acordo'        => $proximoSufixo,
                'valor_total'          => $dados['valor_total'],
                'data_vencimento'      => $dados['data_vencimento'],
                'status'               => 'pendente',
                'status_documento'     => 'gerado',
            ]);

            return [
                'mae'   => $mae->fresh(),
                'filha' => $filha->fresh(['venda', 'condicional']),
            ];
        });
    }

    // RF_F05c — Encaminhar ao jurídico
    public function encaminharJuridico(Promissoria $promissoria): Promissoria
    {
        if (!in_array($promissoria->status, ['pendente', 'carencia'])) {
            throw ValidationException::withMessages(['message' => ['Apenas promissórias pendentes ou em carência podem ser encaminhadas ao jurídico.']]);
        }

        $promissoria->update([
            'status'              => 'juridico',
            'data_envio_juridico' => now()->toDateString(),
        ]);

        return $promissoria->fresh();
    }

    // RF_F05d — Quitar promissória
    public function quitar(Promissoria $promissoria, string $dataPagamento): Promissoria
    {
        if (!in_array($promissoria->status, ['pendente', 'carencia', 'juridico'])) {
            throw ValidationException::withMessages(['message' => ['Promissória não pode ser quitada no status atual.']]);
        }

        $promissoria->update([
            'status'         => 'pago',
            'data_pagamento' => $dataPagamento,
        ]);

        return $promissoria->fresh();
    }

    // Cancelar promissória
    public function cancelar(Promissoria $promissoria): Promissoria
    {
        if ($promissoria->status === 'pago') {
            throw ValidationException::withMessages(['message' => ['Promissória já quitada não pode ser cancelada.']]);
        }

        if ($promissoria->status === 'cancelado') {
            throw ValidationException::withMessages(['message' => ['Promissória já está cancelada.']]);
        }

        $promissoria->update(['status' => 'cancelado']);

        return $promissoria->fresh();
    }

    // Gerar número sequencial via SELECT FOR UPDATE (RN18/proc_gerar_numero_promissoria)
    private function gerarNumero(int $ano): string
    {
        $seq = SequenciaDocumento::where('prefixo', 'PROM')
            ->where('ano', $ano)
            ->lockForUpdate()
            ->first();

        if (!$seq) {
            $seq = SequenciaDocumento::create([
                'prefixo'        => 'PROM',
                'ultimo_numero'  => 0,
                'ano'            => $ano,
            ]);
        }

        $seq->increment('ultimo_numero');
        $seq->refresh();

        return str_pad($seq->ultimo_numero, 3, '0', STR_PAD_LEFT) . '/' . $ano;
    }

    // Calcular próximo sufixo de acordo (A-Z, RN18)
    private function proximoSufixo(array $sufixosUsados): string
    {
        $letras = range('A', 'Z');

        foreach ($letras as $letra) {
            if (!in_array($letra, $sufixosUsados)) {
                return $letra;
            }
        }

        throw ValidationException::withMessages(['message' => ['Limite de 26 acordos por promissória atingido.']]);
    }
}
