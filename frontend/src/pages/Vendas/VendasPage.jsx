import { useEffect, useState, useCallback } from 'react'
import { getVendas, getVenda, createVenda } from '../../api/venda'
import { getClientes } from '../../api/cliente'
import VendaDetalhe from './VendaDetalhe'

const STATUS_LABEL = {
  rascunho:  { label: 'Rascunho',  cls: 'bg-yellow-100 text-yellow-700' },
  concluida: { label: 'Concluída', cls: 'bg-green-100  text-green-700'  },
  cancelada: { label: 'Cancelada', cls: 'bg-red-100    text-red-700'    },
}

// ─── Modal nova venda ─────────────────────────────────────────────────────────

function ModalNovaVenda({ clientes, onCriar, onFechar }) {
  const [idCliente, setIdCliente] = useState('')
  const [desconto, setDesconto]   = useState('0')
  const [loading, setLoading]     = useState(false)
  const [erro, setErro]           = useState('')

  async function submit(e) {
    e.preventDefault()
    setErro('')
    setLoading(true)
    try {
      const { data } = await createVenda({
        id_cliente: Number(idCliente),
        desconto:   Number(desconto),
      })
      onCriar(data)
    } catch (err) {
      setErro(
        err.response?.data?.message ??
        err.response?.data?.errors?.id_cliente?.[0] ??
        'Erro ao criar venda.'
      )
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="fixed inset-0 z-40 flex items-center justify-center bg-black/40">
      <div className="bg-white rounded-xl shadow-xl w-full max-w-sm p-6">
        <div className="flex items-center justify-between mb-5">
          <h3 className="font-semibold text-gray-800">Nova venda</h3>
          <button onClick={onFechar} className="text-gray-400 hover:text-gray-700 text-lg leading-none">
            ✕
          </button>
        </div>

        <form onSubmit={submit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Cliente <span className="text-red-500">*</span>
            </label>
            <select
              value={idCliente} onChange={(e) => setIdCliente(e.target.value)}
              required
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Selecione um cliente...</option>
              {clientes.map((c) => (
                <option key={c.id_cliente} value={c.id_cliente}>
                  {c.nome}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Desconto (R$)
            </label>
            <input
              type="number" min="0" step="0.01"
              value={desconto} onChange={(e) => setDesconto(e.target.value)}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          {erro && (
            <p className="text-sm text-red-600 bg-red-50 border border-red-200 rounded px-3 py-2">
              {erro}
            </p>
          )}

          <div className="flex gap-3 pt-1">
            <button
              type="button" onClick={onFechar}
              className="flex-1 border border-gray-300 text-gray-600 text-sm rounded-lg py-2 hover:bg-gray-50"
            >
              Cancelar
            </button>
            <button
              type="submit" disabled={loading}
              className="flex-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg py-2 transition-colors"
            >
              {loading ? 'Criando...' : 'Criar venda'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

// ─── VendasPage ───────────────────────────────────────────────────────────────

export default function VendasPage() {
  const [vendas, setVendas]               = useState([])
  const [carregando, setCarregando]       = useState(true)
  const [erro, setErro]                   = useState('')

  const [clientes, setClientes]           = useState([])
  const [modalAberto, setModalAberto]     = useState(false)
  const [carregandoClientes, setCarregandoClientes] = useState(false)

  const [vendaAberta, setVendaAberta]     = useState(null)   // venda com detalhe completo
  const [carregandoDetalhe, setCarregandoDetalhe] = useState(false)

  // ── listagem ────────────────────────────────────────────────────────────────

  const carregarVendas = useCallback(async () => {
    try {
      const { data } = await getVendas()
      setVendas(data)
    } catch {
      setErro('Erro ao carregar vendas.')
    }
  }, [])

  useEffect(() => {
    carregarVendas().finally(() => setCarregando(false))
  }, [carregarVendas])

  // ── recarregar venda aberta (chamado após cada operação no painel) ───────────

  const recarregarVendaAberta = useCallback(async () => {
    if (!vendaAberta) return
    const { data } = await getVenda(vendaAberta.id_venda)
    setVendaAberta(data)
    await carregarVendas()
  }, [vendaAberta, carregarVendas])

  // ── abrir detalhe de uma venda da listagem ──────────────────────────────────

  async function abrirDetalhe(id) {
    setCarregandoDetalhe(true)
    try {
      const { data } = await getVenda(id)
      setVendaAberta(data)
    } catch {
      setErro('Erro ao carregar detalhe da venda.')
    } finally {
      setCarregandoDetalhe(false)
    }
  }

  // ── nova venda ──────────────────────────────────────────────────────────────

  async function abrirModal() {
    setCarregandoClientes(true)
    try {
      const { data } = await getClientes()
      setClientes(data)
      setModalAberto(true)
    } catch {
      setErro('Erro ao carregar clientes.')
    } finally {
      setCarregandoClientes(false)
    }
  }

  async function onVendaCriada(vendaBasica) {
    setModalAberto(false)
    // buscar detalhe completo (com itens e pagamentos) da venda recém-criada
    const { data } = await getVenda(vendaBasica.id_venda)
    setVendaAberta(data)
    await carregarVendas()
  }

  // ── render ─────────────────────────────────────────────────────────────────

  if (carregando) return <p className="text-gray-400 text-sm">Carregando...</p>

  return (
    <div className="flex gap-5 h-full">

      {/* ── coluna esquerda: listagem ──────────────────────────────────────── */}
      <div className="flex-1 min-w-0 space-y-4">
        <div className="flex items-center justify-between">
          <h2 className="text-lg font-semibold text-gray-800">Vendas</h2>
          <button
            onClick={abrirModal}
            disabled={carregandoClientes}
            className="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors"
          >
            {carregandoClientes ? 'Carregando...' : '+ Nova venda'}
          </button>
        </div>

        {erro && (
          <p className="text-sm text-red-600 bg-red-50 border border-red-200 rounded px-3 py-2">
            {erro}
          </p>
        )}

        <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-gray-500 uppercase text-xs">
              <tr>
                <th className="px-4 py-3 text-left">#</th>
                <th className="px-4 py-3 text-left">Cliente</th>
                <th className="px-4 py-3 text-left">Status</th>
                <th className="px-4 py-3 text-right">Total</th>
                <th className="px-4 py-3 text-left">Data</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {vendas.length === 0 && (
                <tr>
                  <td colSpan={5} className="px-4 py-6 text-center text-gray-400">
                    Nenhuma venda encontrada.
                  </td>
                </tr>
              )}
              {vendas.map((v) => {
                const s = STATUS_LABEL[v.status] ?? { label: v.status, cls: 'bg-gray-100 text-gray-600' }
                const ativa = vendaAberta?.id_venda === v.id_venda
                return (
                  <tr
                    key={v.id_venda}
                    onClick={() => abrirDetalhe(v.id_venda)}
                    className={`cursor-pointer transition-colors ${
                      ativa ? 'bg-blue-50 border-l-2 border-blue-400' : 'hover:bg-gray-50'
                    }`}
                  >
                    <td className="px-4 py-3 text-gray-500">{v.id_venda}</td>
                    <td className="px-4 py-3 font-medium text-gray-700">
                      {v.cliente?.nome ?? '—'}
                    </td>
                    <td className="px-4 py-3">
                      <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${s.cls}`}>
                        {s.label}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-right tabular-nums">
                      R$ {Number(v.valor_total).toFixed(2)}
                    </td>
                    <td className="px-4 py-3 text-gray-400">
                      {new Date(v.data_venda).toLocaleDateString('pt-BR')}
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>
      </div>

      {/* ── coluna direita: painel de detalhe ─────────────────────────────── */}
      {(vendaAberta || carregandoDetalhe) && (
        <div className="w-96 flex-shrink-0 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          {carregandoDetalhe ? (
            <p className="text-sm text-gray-400">Carregando detalhe...</p>
          ) : (
            <VendaDetalhe
              venda={vendaAberta}
              onAtualizar={recarregarVendaAberta}
              onFechar={() => setVendaAberta(null)}
            />
          )}
        </div>
      )}

      {/* ── modal nova venda ───────────────────────────────────────────────── */}
      {modalAberto && (
        <ModalNovaVenda
          clientes={clientes}
          onCriar={onVendaCriada}
          onFechar={() => setModalAberto(false)}
        />
      )}
    </div>
  )
}
