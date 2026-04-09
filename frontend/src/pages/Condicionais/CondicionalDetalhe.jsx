import { useState, useEffect } from 'react'
import {
  addItemCondicional,
  devolverItem,
  fecharCondicional,
  cancelarCondicional,
} from '../../api/condicional'
import { getProdutos, getVariacoes } from '../../api/produto'

const STATUS_LABEL = {
  retirado:        { label: 'Retirado',         cls: 'bg-blue-100 text-blue-700'    },
  parcial:         { label: 'Parcial',           cls: 'bg-yellow-100 text-yellow-700' },
  parcial_vencido: { label: 'Parcial vencido',   cls: 'bg-orange-100 text-orange-700' },
  vencido:         { label: 'Vencido',           cls: 'bg-red-100 text-red-700'      },
  devolvido:       { label: 'Devolvido',         cls: 'bg-gray-100 text-gray-600'    },
  fechado:         { label: 'Fechado',           cls: 'bg-green-100 text-green-700'  },
  cancelado:       { label: 'Cancelado',         cls: 'bg-red-50 text-red-400'       },
}

const STATUS_ITEM_LABEL = {
  ativo:      { label: 'Ativo',      cls: 'bg-blue-100 text-blue-700'  },
  devolvido:  { label: 'Devolvido',  cls: 'bg-gray-100 text-gray-500'  },
  convertido: { label: 'Convertido', cls: 'bg-green-100 text-green-700' },
  perdido:    { label: 'Perdido',    cls: 'bg-red-100 text-red-700'    },
}

const TIPOS_CANCELAMENTO = [
  { value: 'virou_promissoria',       label: 'Virou promissória'        },
  { value: 'perda',                   label: 'Perda'                    },
  { value: 'devolvido_informalmente', label: 'Devolvido informalmente'  },
]

// ─── helpers ─────────────────────────────────────────────────────────────────

function Secao({ titulo, children }) {
  return (
    <div className="mt-5">
      <p className="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">{titulo}</p>
      {children}
    </div>
  )
}

function MsgErro({ msg }) {
  if (!msg) return null
  return <p className="text-xs text-red-600 bg-red-50 border border-red-100 rounded px-3 py-2 mt-2">{msg}</p>
}

function MsgOk({ msg }) {
  if (!msg) return null
  return <p className="text-xs text-green-700 bg-green-50 border border-green-100 rounded px-3 py-2 mt-2">{msg}</p>
}

// ─── FormDevolver (inline por item) ──────────────────────────────────────────

function FormDevolver({ idCondicional, item, onAtualizar }) {
  const saldo = item.qtd_retirada - item.qtd_devolvida - item.qtd_comprada
  const [aberto, setAberto] = useState(false)
  const [qtd, setQtd]       = useState('1')
  const [loading, setLoading] = useState(false)
  const [erro, setErro]     = useState('')

  async function submit(e) {
    e.preventDefault()
    const n = Number(qtd)
    if (!n || n < 1 || n > saldo) return
    setErro('')
    setLoading(true)
    try {
      await devolverItem(idCondicional, item.id_item_cond, { quantidade: n })
      setAberto(false)
      setQtd('1')
      await onAtualizar()
    } catch (err) {
      setErro(err.response?.data?.message ?? err.response?.data?.errors?.message?.[0] ?? 'Erro ao devolver.')
    } finally {
      setLoading(false)
    }
  }

  if (!aberto) {
    return (
      <button
        onClick={() => setAberto(true)}
        className="text-xs border border-blue-200 text-blue-600 hover:bg-blue-50 rounded px-2 py-0.5 transition-colors"
      >
        Devolver
      </button>
    )
  }

  return (
    <form onSubmit={submit} className="flex items-center gap-1.5 flex-wrap">
      <input
        type="number" min="1" max={saldo} required
        value={qtd} onChange={(e) => setQtd(e.target.value)}
        className="w-16 border border-gray-200 rounded px-2 py-0.5 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400"
      />
      <button type="submit" disabled={loading}
        className="text-xs bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded px-2 py-0.5">
        {loading ? '...' : 'OK'}
      </button>
      <button type="button" onClick={() => { setAberto(false); setErro('') }}
        className="text-xs text-gray-400 hover:text-gray-600">✕</button>
      {erro && <span className="text-xs text-red-500 w-full">{erro}</span>}
    </form>
  )
}

// ─── FormItemCondicional ──────────────────────────────────────────────────────

function FormItemCondicional({ idCondicional, onAtualizar }) {
  const [produtos, setProdutos]             = useState([])
  const [loadingProdutos, setLoadingProdutos] = useState(true)
  const [erroProdutos, setErroProdutos]     = useState('')
  const [produto, setProduto]               = useState(null)
  const [variacoes, setVariacoes]           = useState([])
  const [loadingVar, setLoadingVar]         = useState(false)
  const [variacao, setVariacao]             = useState(null)
  const [qtdRetirada, setQtdRetirada]       = useState('1')
  const [precoUnitario, setPrecoUnitario]   = useState('')
  const [loading, setLoading]               = useState(false)
  const [erro, setErro]                     = useState('')

  useEffect(() => {
    getProdutos()
      .then(({ data }) => setProdutos(data.filter((p) => p.ativo)))
      .catch(() => setErroProdutos('Erro ao carregar produtos.'))
      .finally(() => setLoadingProdutos(false))
  }, [])

  async function handleProduto(e) {
    const id = Number(e.target.value)
    if (!id) { setProduto(null); setVariacoes([]); setVariacao(null); setPrecoUnitario(''); return }
    const prod = produtos.find((p) => p.id_produto === id) ?? null
    setProduto(prod)
    setVariacao(null)
    setPrecoUnitario(prod ? String(prod.preco_venda) : '')
    setLoadingVar(true)
    try {
      const { data } = await getVariacoes(id)
      setVariacoes(data.filter((v) => v.qtd_estoque > 0))
    } catch {
      setVariacoes([])
    } finally {
      setLoadingVar(false)
    }
  }

  function handleVariacao(e) {
    const id = Number(e.target.value)
    setVariacao(variacoes.find((v) => v.id_variacao === id) ?? null)
  }

  function limpar() {
    setProduto(null); setVariacoes([]); setVariacao(null)
    setQtdRetirada('1'); setPrecoUnitario(''); setErro('')
  }

  const qtdNum   = Number(qtdRetirada)
  const precoNum = Number(precoUnitario)
  const submitBloqueado =
    loading || !produto || !variacao ||
    !qtdRetirada || isNaN(qtdNum) || qtdNum < 1 ||
    !precoUnitario || isNaN(precoNum) || precoNum < 0

  async function submit(e) {
    e.preventDefault()
    if (submitBloqueado) return
    setErro('')
    setLoading(true)
    try {
      await addItemCondicional(idCondicional, {
        id_variacao:    variacao.id_variacao,
        qtd_retirada:   Number(qtdRetirada),
        preco_unitario: Number(precoUnitario),
      })
      limpar()
      await onAtualizar()
    } catch (err) {
      setErro(err.response?.data?.message ?? err.response?.data?.errors?.message?.[0] ?? 'Erro ao adicionar item.')
    } finally {
      setLoading(false)
    }
  }

  if (loadingProdutos) return <p className="text-xs text-gray-400 mt-2">Carregando produtos...</p>
  if (erroProdutos)    return <p className="text-xs text-red-500 mt-2">{erroProdutos}</p>

  return (
    <form onSubmit={submit} className="space-y-2 mt-2">
      <div>
        <label className="block text-xs text-gray-500 mb-1">Produto</label>
        <select value={produto?.id_produto ?? ''} onChange={handleProduto} required
          className="w-full border border-gray-200 rounded px-2 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-blue-400">
          <option value="">Selecione o produto...</option>
          {produtos.map((p) => <option key={p.id_produto} value={p.id_produto}>{p.nome}</option>)}
        </select>
      </div>

      {produto && (
        <div>
          <label className="block text-xs text-gray-500 mb-1">Variação</label>
          {loadingVar ? (
            <p className="text-xs text-gray-400">Carregando variações...</p>
          ) : variacoes.length === 0 ? (
            <p className="text-xs text-gray-400">Nenhuma variação com estoque disponível.</p>
          ) : (
            <select value={variacao?.id_variacao ?? ''} onChange={handleVariacao} required
              className="w-full border border-gray-200 rounded px-2 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-blue-400">
              <option value="">Selecione a variação...</option>
              {variacoes.map((v) => (
                <option key={v.id_variacao} value={v.id_variacao}>
                  {v.cor} / {v.tamanho} — estoque: {v.qtd_estoque}
                </option>
              ))}
            </select>
          )}
        </div>
      )}

      {variacao && (
        <div className="grid grid-cols-2 gap-2">
          <div>
            <label className="block text-xs text-gray-500 mb-1">Qtd retirada</label>
            <input type="number" min="1" max={variacao.qtd_estoque} required
              value={qtdRetirada} onChange={(e) => setQtdRetirada(e.target.value)}
              className="w-full border border-gray-200 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400" />
          </div>
          <div>
            <label className="block text-xs text-gray-500 mb-1">Preço unit. (R$)</label>
            <input type="number" min="0" step="0.01" required
              value={precoUnitario} onChange={(e) => setPrecoUnitario(e.target.value)}
              className="w-full border border-gray-200 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400" />
          </div>
        </div>
      )}

      <MsgErro msg={erro} />
      <button type="submit" disabled={submitBloqueado}
        className="w-full bg-gray-800 hover:bg-gray-900 disabled:opacity-40 text-white text-xs font-medium rounded py-1.5 transition-colors">
        {loading ? 'Adicionando...' : '+ Adicionar item'}
      </button>
    </form>
  )
}

// ─── FormCancelarCondicional ──────────────────────────────────────────────────

function FormCancelarCondicional({ idCondicional, onAtualizar }) {
  const [aberto, setAberto] = useState(false)
  const [tipo, setTipo]     = useState('perda')
  const [loading, setLoading] = useState(false)
  const [erro, setErro]     = useState('')

  async function submit(e) {
    e.preventDefault()
    setErro('')
    setLoading(true)
    try {
      await cancelarCondicional(idCondicional, { tipo_cancelamento: tipo })
      await onAtualizar()
      setAberto(false)
    } catch (err) {
      setErro(err.response?.data?.message ?? err.response?.data?.errors?.message?.[0] ?? 'Erro ao cancelar.')
    } finally {
      setLoading(false)
    }
  }

  if (!aberto) {
    return (
      <button onClick={() => setAberto(true)}
        className="flex-1 border border-red-300 text-red-600 hover:bg-red-50 text-xs font-medium rounded py-1.5 transition-colors">
        Cancelar condicional
      </button>
    )
  }

  return (
    <form onSubmit={submit} className="space-y-2 w-full">
      <p className="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded px-3 py-2">
        Ação irreversível. Itens pendentes serão devolvidos ao estoque.
      </p>
      <div>
        <label className="block text-xs text-gray-500 mb-1">Tipo de cancelamento</label>
        <select value={tipo} onChange={(e) => setTipo(e.target.value)}
          className="w-full border border-gray-200 rounded px-2 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-red-400">
          {TIPOS_CANCELAMENTO.map((t) => (
            <option key={t.value} value={t.value}>{t.label}</option>
          ))}
        </select>
      </div>
      <MsgErro msg={erro} />
      <div className="flex gap-2">
        <button type="button" onClick={() => { setAberto(false); setErro('') }}
          className="flex-1 border border-gray-200 text-gray-500 hover:bg-gray-50 text-xs rounded py-1.5">
          Voltar
        </button>
        <button type="submit" disabled={loading}
          className="flex-1 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white text-xs font-medium rounded py-1.5">
          {loading ? 'Cancelando...' : 'Confirmar cancelamento'}
        </button>
      </div>
    </form>
  )
}

// ─── CondicionalDetalhe (painel principal) ────────────────────────────────────

export default function CondicionalDetalhe({ condicional, onAtualizar, onFechar }) {
  const [loadingFechar, setLoadingFechar] = useState(false)
  const [erroAcao, setErroAcao]           = useState('')
  const [okAcao, setOkAcao]               = useState('')

  const s = STATUS_LABEL[condicional.status] ?? { label: condicional.status, cls: 'bg-gray-100 text-gray-600' }
  const podeAdicionar      = ['retirado', 'parcial'].includes(condicional.status)
  const podeFecharCancelar = ['retirado', 'parcial', 'parcial_vencido', 'vencido'].includes(condicional.status)

  async function handleFechar() {
    setErroAcao(''); setOkAcao(''); setLoadingFechar(true)
    try {
      await fecharCondicional(condicional.id_condicional)
      setOkAcao('Condicional fechado.')
      await onAtualizar()
    } catch (err) {
      setErroAcao(err.response?.data?.message ?? err.response?.data?.errors?.message?.[0] ?? 'Erro ao fechar.')
    } finally {
      setLoadingFechar(false)
    }
  }

  return (
    <div className="flex flex-col h-full">

      {/* cabeçalho */}
      <div className="flex items-center justify-between pb-4 border-b border-gray-100">
        <div>
          <p className="text-xs text-gray-400 mb-0.5">Condicional #{condicional.id_condicional}</p>
          <p className="font-semibold text-gray-800 text-sm">{condicional.cliente?.nome ?? '—'}</p>
        </div>
        <div className="flex items-center gap-3">
          <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${s.cls}`}>{s.label}</span>
          <button onClick={onFechar} className="text-gray-400 hover:text-gray-700 text-lg leading-none">✕</button>
        </div>
      </div>

      {/* corpo com scroll */}
      <div className="flex-1 overflow-y-auto py-1 pr-1">

        {/* data prevista */}
        <div className="mt-4 bg-gray-50 rounded-lg px-4 py-3">
          <p className="text-xs text-gray-500">Devolução prevista</p>
          <p className="text-sm font-semibold text-gray-800">
            {new Date(condicional.data_prevista_dev + 'T00:00:00').toLocaleDateString('pt-BR')}
          </p>
        </div>

        <MsgErro msg={erroAcao} />
        <MsgOk msg={okAcao} />

        {/* itens */}
        <Secao titulo="Itens">
          {(!condicional.itens || condicional.itens.length === 0) && (
            <p className="text-xs text-gray-400">Nenhum item adicionado.</p>
          )}
          <div className="space-y-2">
            {condicional.itens?.map((item) => {
              const si    = STATUS_ITEM_LABEL[item.status_item] ?? { label: item.status_item, cls: 'bg-gray-100 text-gray-500' }
              const saldo = item.qtd_retirada - item.qtd_devolvida - item.qtd_comprada
              return (
                <div key={item.id_item_cond} className="bg-gray-50 rounded px-3 py-2 text-xs space-y-1.5">
                  <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                      <span className="font-medium text-gray-800">
                        {item.variacao?.produto?.nome ?? `Variação #${item.id_variacao}`}
                      </span>
                      {item.variacao && (
                        <span className="text-gray-500"> — {item.variacao.cor} / {item.variacao.tamanho}</span>
                      )}
                    </div>
                    <span className={`shrink-0 px-1.5 py-0.5 rounded-full font-medium ${si.cls}`}>{si.label}</span>
                  </div>

                  <div className="text-gray-500 flex gap-3 flex-wrap">
                    <span>Retirado: <b className="text-gray-700">{item.qtd_retirada}</b></span>
                    <span>Devolvido: <b className="text-gray-700">{item.qtd_devolvida}</b></span>
                    <span>Comprado: <b className="text-gray-700">{item.qtd_comprada}</b></span>
                    {saldo > 0 && (
                      <span className="text-blue-600 font-medium">Pendente: {saldo}</span>
                    )}
                  </div>

                  <div className="flex items-center justify-between">
                    <span className="tabular-nums text-gray-600">
                      R$ {Number(item.preco_unitario).toFixed(2)} / un.
                    </span>
                    {item.status_item === 'ativo' && saldo > 0 && (
                      <FormDevolver
                        idCondicional={condicional.id_condicional}
                        item={item}
                        onAtualizar={onAtualizar}
                      />
                    )}
                  </div>
                </div>
              )
            })}
          </div>

          {podeAdicionar && (
            <FormItemCondicional
              idCondicional={condicional.id_condicional}
              onAtualizar={onAtualizar}
            />
          )}
        </Secao>
      </div>

      {/* ações */}
      {podeFecharCancelar && (
        <div className="pt-4 border-t border-gray-100 space-y-2 flex-shrink-0">
          <div className="flex gap-2">
            <button
              onClick={handleFechar}
              disabled={loadingFechar}
              className="flex-1 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white text-xs font-medium rounded py-1.5 transition-colors"
            >
              {loadingFechar ? 'Fechando...' : 'Fechar condicional'}
            </button>
            <FormCancelarCondicional
              idCondicional={condicional.id_condicional}
              onAtualizar={onAtualizar}
            />
          </div>
        </div>
      )}
    </div>
  )
}
