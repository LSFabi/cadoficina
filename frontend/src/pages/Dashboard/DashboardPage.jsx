import { useEffect, useState } from 'react'
import api from '../../api/axios'
import { useAuth } from '../../context/AuthContext'

function Card({ titulo, valor, sub }) {
  return (
    <div className="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
      <p className="text-xs text-gray-500 uppercase tracking-wide mb-1">{titulo}</p>
      <p className="text-2xl font-semibold text-gray-800">{valor}</p>
      {sub && <p className="text-xs text-gray-400 mt-1">{sub}</p>}
    </div>
  )
}

export default function DashboardPage() {
  const { usuario } = useAuth()
  const isProp = usuario?.perfil === 'proprietaria'

  const [dados, setDados] = useState(null)
  const [erro, setErro] = useState('')

  useEffect(() => {
    api.get('/dashboard')
      .then(({ data }) => setDados(data))
      .catch(() => setErro('Erro ao carregar indicadores.'))
  }, [])

  if (erro) return <p className="text-red-500 text-sm">{erro}</p>
  if (!dados) return <p className="text-gray-400 text-sm">Carregando...</p>

  const vendasDia = dados.indicador_1_vendas_dia
  const estoque = dados.indicador_3_estoque_critico
  const condicionais = dados.indicador_4_condicionais_abertos

  return (
    <div className="space-y-6">
      <h2 className="text-lg font-semibold text-gray-800">Dashboard</h2>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <Card
          titulo="Vendas do dia"
          valor={vendasDia?.quantidade ?? 0}
          sub={`R$ ${Number(vendasDia?.valor_total ?? 0).toFixed(2)}`}
        />
        <Card
          titulo="Estoque zerado"
          valor={`${estoque?.zerados ?? 0} / ${estoque?.total_variacoes ?? 0}`}
          sub="variações ativas"
        />
        <Card
          titulo="Condicionais abertos"
          valor={condicionais ?? 0}
        />
        {isProp && (
          <>
            <Card
              titulo="Faturamento do mês"
              valor={`R$ ${Number(dados.indicador_2_faturamento_mensal ?? 0).toFixed(2)}`}
            />
            <Card
              titulo="Perdas do mês"
              valor={`R$ ${Number(dados.indicador_8_perdas_mes ?? 0).toFixed(2)}`}
            />
          </>
        )}
      </div>
    </div>
  )
}
