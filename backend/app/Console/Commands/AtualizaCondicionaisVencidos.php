<?php

namespace App\Console\Commands;

use App\Models\Condicional;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AtualizaCondicionaisVencidos extends Command
{
    protected $signature = 'condicionais:atualizar-vencidos';

    protected $description = 'Marca condicionais com data_prevista_dev vencida: retirado→vencido, parcial→parcial_vencido';

    public function handle(): int
    {
        $hoje = Carbon::today()->toDateString();

        $vencidos = Condicional::where('status', 'retirado')
            ->where('data_prevista_dev', '<', $hoje)
            ->update(['status' => 'vencido']);

        $parcialVencidos = Condicional::where('status', 'parcial')
            ->where('data_prevista_dev', '<', $hoje)
            ->update(['status' => 'parcial_vencido']);

        $this->info("condicionais:atualizar-vencidos — retirado→vencido: {$vencidos} | parcial→parcial_vencido: {$parcialVencidos}");

        return Command::SUCCESS;
    }
}
