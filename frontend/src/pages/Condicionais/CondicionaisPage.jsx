import { useEffect, useState, useCallback } from 'react'
import { getCondicionais, getCondicional, createCondicional } from '../../api/condicional'
import { getClientes } from '../../api/cliente'
import CondicionalDetalhe from './CondicionalDetalhe'

const STATUS_LABEL = {
  retirado:        { label: 'Retirado',         cls: 'bg-blue-100 text-blue-700'    },
  parcial:         { label: 'Parcial',           cls: 'bg-yellow-100 text-yellow-700' },
  parcial_vencido: { label: 'Parcial vencido',   cls: 'bg-orange-100 text-orange-700' },
  vencido:         { label: 'Vencido',           cls: 'bg-red-100 text-red-700'      },
  devolvido:       { label: 'Devolvido',         cls: 'bg-gray-100 text-gray-600'    },
  fechado:         { label: 'Fechado',           cls: 'bg-green-100 text-green-700'  },
  cancelado:       { label: 'Cancelado',         cls: 'bg-red-50 text-red-400'       },
}

// ─── Modal novo condicional ───────────────────────────────────────────────────

function ModalNovoCondicional({ clientes, onCriar, onFechar }) {
  const amanha = new Date(Date.now() + 86400000).toISOString().split('T')[0]
  const [idCliente, setIdCliente]       = useState('')
  const [dataPrevista, setDataPrevista] = useState('')
  const [loading, setLoading]           = useState(false)
  const [erro, setErro]                 = useState('')

  async function submit(e) {
    e.preventDefault()
    setErro('')
    setLoading(true)
    try {
      const { data } = await createCondicional({
        id_cliente:        Number(idCliente),
        data_prevista_dev: dataPrevista,
      })
      onCriar(data)
    } catch (err) {
      setErro(
        err.response?.data?.errors?.data_prevista_dev?.[0] ??
        err.response?.data?.message ??
        'Erro ao criar condicional.'
      )
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="fixed inset-0 z-40 flex items-center justify-center bg-black/40">
      <div className="bg-white rounded-xl shadow-xl w-full max-w-sm p-6">
        <div className="flex items-center justify-between mb-5">
          <h3 className="font-semibold text-gray-800">Novo condicional</h3>
          <button onClick={onFechar} className="text-gray-400 hover:text-gray-700 text-lg leading-none">✕</button>
        </div>

        <form onSubmit={submit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Cliente <span className="text-red-500">*</span>
            </label>
            <select
              value={idCliente} onChange={(e) => setIdCliente(e.target.value)} required
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Selecione um cliente...</option>
              {clientes.map((c) => (
                <option key={c.id_cliente} value={c.id_cliente}>{c.nome}</option>
              ))}
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Data prevista de devolução <span className="text-red-500">*</span>
            </label>
            <input
              type="date" required min={amanha}
              value={dataPrevista} onChange={(e) => setDataPrevista(e.target.value)}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          {erro && (
            <p className="text-sm text-red-600 bg-red-50 border border-red-200 rounded px-3 py-2">{erro}</p>
          )}

          <div className="flex gap-3 pt-1">
            <button type="button" onClick={onFechar}
              className="flex-1 border border-gray-300 text-gray-600 text-sm rounded-lg py-2 hover:bg-gray-50">
              Cancelar
            </button>
            <button type="submit" disabled={loading}
              className="flex-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg py-2 transition-colors">
              {loading ? 'Criando...' : 'Criar condicional'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

// ─── CondicionaisPage ─────────────────────────────────────────────────────────

export default function CondicionaisPage() {
  const [condicionais, setCondicionais]               = useState([])
  const [carregando, setCarregando]                   = useState(true)
  const [erro, setErro]                               = useState('')
  const [filtroStatus, setFiltroStatus]               = useState('')

  const [clientes, setClientes]                       = useState([])
  const [modalAberto, setModalAberto]                 = useState(false)
  const [carregandoClientes, setCarregandoClientes]   = useState(false)

  const [condicionalAberto, setCondicionalAberto]     = useState(null)
  const [carregandoDetalhe, setCarregandoDetalhe]     = useState(false)

  // ── listagem ────────────────────────────────────────────────────────────────

  const carregarCondicionais = useCallback(async () => {
    try {
      const { data } = await getCondicionais()
      setCondicionais(data)
    } catch {
      setErro('Erro ao carregar condicionais.')
    }
  }, [])

  useEffect(() => {
    carregarCondicionais().finally(() => setCarregando(false))
  }, [carregarCondicionais])

  // ── recarregar painel aberto ────────────────────────────────────────────────

  const recarregarCondicionalAberto = useCallback(async () => {
    if (!condicionalAberto) return
    const { data } = await getCondicional(condicionalAberto.id_condicional)
    setCondicionalAberto(data)
    await carregarCondicionais()
  }, [condicionalAberto, carregarCondicionais])

  // ── abrir detalhe ───────────────────────────────────────────────────────────

  async function abrirDetalhe(id) {
    setCarregandoDetalhe(true)
    try {
      const { data } = await getCondicional(id)
      setCondicionalAberto(data)
    } catch {
      setErro('Erro ao carregar detalhe do condicional.')
    } finally {
      setCarregandoDetalhe(false)
    }
  }

  // ── novo condicional ────────────────────────────────────────────────────────

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

  async function onCondicionalCriado(basico) {
    setModalAberto(false)
    const { data } = await getCondicional(basico.id_condicional)
    setCondicionalAberto(data)
    await carregarCondicionais()
  }

  // ── render ─────────────────────────────────────────────────────────────────

  // contagens sempre do array completo — independente do filtro ativo
  const contagens = condicionais.reduce((acc, c) => {
    acc[c.status] = (acc[c.status] ?? 0) + 1
    return acc
  }, {})

  const condicionaisFiltrados = filtroStatus
    ? condicionais.filter((c) => c.status === filtroStatus)
    : condicionais

  if (carregando) return <p className="text-gray-400 text-sm">Carregando...</p>

  return (
    <div className="flex gap-5 h-full">

      {/* ── coluna esquerda: listagem ──────────────────────────────────────── */}
      <div className="flex-1 min-w-0 space-y-4">
        <div className="flex items-center justify-between">
          <h2 className="text-lg font-semibold text-gray-800">Condicionais</h2>
          <div className="flex items-center gap-3">
            <select
              value={filtroStatus}
              onChange={(e) => setFiltroStatus(e.target.value)}
              className="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-blue-400"
            >
              <option value="">Todos os status ({condicionais.length})</option>
              {Object.entries(STATUS_LABEL).map(([value, { label }]) => (
                <option key={value} value={value}>
                  {label}{contagens[value] ? ` (${contagens[value]})` : ''}
                </option>
              ))}
            </select>
            <button
              onClick={abrirModal}
              disabled={carregandoClientes}
              className="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors"
            >
              {carregandoClientes ? 'Carregando...' : '+ Novo condicional'}
            </button>
          </div>
        </div>

        {erro && (
          <p className="text-sm text-red-600 bg-red-50 border border-red-200 rounded px-3 py-2">{erro}</p>
        )}

        <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-gray-500 uppercase text-xs">
              <tr>
                <th className="px-4 py-3 text-left">#</th>
                <th className="px-4 py-3 text-left">Cliente</th>
                <th className="px-4 py-3 text-left">Status</th>
                <th className="px-4 py-3 text-left">Devolução prevista</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {condicionaisFiltrados.length === 0 && (
                <tr>
                  <td colSpan={4} className="px-4 py-6 text-center text-gray-400">
                    Nenhum condicional encontrado.
                  </td>
                </tr>
              )}
              {condicionaisFiltrados.map((c) => {
                const s    = STATUS_LABEL[c.status] ?? { label: c.status, cls: 'bg-gray-100 text-gray-600' }
                const ativo = condicionalAberto?.id_condicional === c.id_condicional
                return (
                  <tr
                    key={c.id_condicional}
                    onClick={() => abrirDetalhe(c.id_condicional)}
                    className={`cursor-pointer transition-colors ${
                      ativo ? 'bg-blue-50 border-l-2 border-blue-400' : 'hover:bg-gray-50'
                    }`}
                  >
                    <td className="px-4 py-3 text-gray-500">{c.id_condicional}</td>
                    <td className="px-4 py-3 font-medium text-gray-700">{c.cliente?.nome ?? '—'}</td>
                    <td className="px-4 py-3">
                      <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${s.cls}`}>
                        {s.label}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-gray-400">
                      {new Date(c.data_prevista_dev + 'T00:00:00').toLocaleDateString('pt-BR')}
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>
      </div>

      {/* ── coluna direita: painel de detalhe ─────────────────────────────── */}
      {(condicionalAberto || carregandoDetalhe) && (
        <div className="w-[26rem] flex-shrink-0 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          {carregandoDetalhe ? (
            <p className="text-sm text-gray-400">Carregando detalhe...</p>
          ) : (
            <CondicionalDetalhe
              condicional={condicionalAberto}
              onAtualizar={recarregarCondicionalAberto}
              onFechar={() => setCondicionalAberto(null)}
            />
          )}
        </div>
      )}

      {/* ── modal novo condicional ─────────────────────────────────────────── */}
      {modalAberto && (
        <ModalNovoCondicional
          clientes={clientes}
          onCriar={onCondicionalCriado}
          onFechar={() => setModalAberto(false)}
        />
      )}
    </div>
  )
}
