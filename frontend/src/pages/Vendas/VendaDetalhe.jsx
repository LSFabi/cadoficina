import { useState, useEffect } from 'react'
import {
  addItemVenda,
  removeItemVenda,
  confirmarVenda,
  cancelarVenda,
  reabrirVenda,
} from '../../api/venda'
import { addPagamento } from '../../api/pagamento'
import { getProdutos, getVariacoes } from '../../api/produto'

const STATUS_LABEL = {
  rascunho:  { label: 'Rascunho',  cls: 'bg-yellow-100 text-yellow-700' },
  concluida: { label: 'Concluída', cls: 'bg-green-100  text-green-700'  },
  cancelada: { label: 'Cancelada', cls: 'bg-red-100    text-red-700'    },
}

const FORMAS_PAGAMENTO = [
  'dinheiro',
  'pix',
  'cartao_debito',
  'cartao_credito',
  'promissoria',
]

const FORMA_LABEL = {
  dinheiro:      'Dinheiro',
  pix:           'Pix',
  cartao_debito: 'Cartão Débito',
  cartao_credito:'Cartão Crédito',
  promissoria:   'Promissória',
}

// ─── sub-componentes ─────────────────────────────────────────────────────────

function Secao({ titulo, children }) {
  return (
    <div className="mt-5">
      <p className="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">
        {titulo}
      </p>
      {children}
    </div>
  )
}

function MsgErro({ msg }) {
  if (!msg) return null
  return (
    <p className="text-xs text-red-600 bg-red-50 border border-red-100 rounded px-3 py-2 mt-2">
      {msg}
    </p>
  )
}

function MsgOk({ msg }) {
  if (!msg) return null
  return (
    <p className="text-xs text-green-700 bg-green-50 border border-green-100 rounded px-3 py-2 mt-2">
      {msg}
    </p>
  )
}

// ─── FormItem ────────────────────────────────────────────────────────────────

function FormItem({ idVenda, onAtualizar }) {
  // produtos
  const [produtos, setProdutos]           = useState([])
  const [loadingProdutos, setLoadingProdutos] = useState(true)
  const [erroProdutos, setErroProdutos]   = useState('')

  // etapa 1 — produto selecionado
  const [produto, setProduto]             = useState(null)

  // etapa 2 — variacoes do produto
  const [variacoes, setVariacoes]         = useState([])
  const [loadingVar, setLoadingVar]       = useState(false)

  // etapa 2 — variacao selecionada
  const [variacao, setVariacao]           = useState(null)

  // campos editáveis
  const [quantidade, setQuantidade]       = useState('1')
  const [precoUnitario, setPrecoUnitario] = useState('')

  // submissão
  const [loading, setLoading]             = useState(false)
  const [erro, setErro]                   = useState('')

  // carregar produtos ao montar
  useEffect(() => {
    getProdutos()
      .then(({ data }) => setProdutos(data.filter((p) => p.ativo)))
      .catch(() => setErroProdutos('Erro ao carregar produtos.'))
      .finally(() => setLoadingProdutos(false))
  }, [])

  // ao selecionar produto: buscar variacoes
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
      setVariacoes(data)
    } catch {
      setVariacoes([])
    } finally {
      setLoadingVar(false)
    }
  }

  // ao selecionar variacao
  function handleVariacao(e) {
    const id = Number(e.target.value)
    if (!id) { setVariacao(null); return }
    const v = variacoes.find((x) => x.id_variacao === id) ?? null
    setVariacao(v)
  }

  // limpar seleção completa
  function limpar() {
    setProduto(null)
    setVariacoes([])
    setVariacao(null)
    setQuantidade('1')
    setPrecoUnitario('')
    setErro('')
  }

  const qtdNum   = Number(quantidade)
  const precoNum = Number(precoUnitario)
  const submitBloqueado =
    loading ||
    !produto ||
    !variacao ||
    !quantidade || isNaN(qtdNum) || qtdNum < 1 || qtdNum > variacao?.qtd_estoque ||
    !precoUnitario || isNaN(precoNum) || precoNum <= 0

  const estoqueAbaixoMinimo =
    variacao &&
    produto &&
    variacao.qtd_estoque <= produto.estoque_minimo

  async function submit(e) {
    e.preventDefault()
    if (submitBloqueado) return
    setErro('')
    setLoading(true)
    try {
      await addItemVenda(idVenda, {
        id_variacao:    variacao.id_variacao,
        quantidade:     Number(quantidade),
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

  if (loadingProdutos) {
    return <p className="text-xs text-gray-400 mt-2">Carregando produtos...</p>
  }
  if (erroProdutos) {
    return <p className="text-xs text-red-500 mt-2">{erroProdutos}</p>
  }

  return (
    <form onSubmit={submit} className="space-y-2 mt-2">

      {/* etapa 1: produto */}
      <div>
        <label className="block text-xs text-gray-500 mb-1">Produto</label>
        <select
          value={produto?.id_produto ?? ''}
          onChange={handleProduto}
          required
          className="w-full border border-gray-200 rounded px-2 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-blue-400"
        >
          <option value="">Selecione o produto...</option>
          {produtos.map((p) => (
            <option key={p.id_produto} value={p.id_produto}>
              {p.nome}
            </option>
          ))}
        </select>
      </div>

      {/* etapa 2: variacao */}
      {produto && (
        <div>
          <label className="block text-xs text-gray-500 mb-1">Variação</label>
          {loadingVar ? (
            <p className="text-xs text-gray-400">Carregando variações...</p>
          ) : variacoes.length === 0 ? (
            <p className="text-xs text-gray-400">Nenhuma variação ativa.</p>
          ) : (
            <select
              value={variacao?.id_variacao ?? ''}
              onChange={handleVariacao}
              required
              className="w-full border border-gray-200 rounded px-2 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-blue-400"
            >
              <option value="">Selecione a variação...</option>
              {variacoes.map((v) => {
                const semEstoque = v.qtd_estoque <= 0
                return (
                  <option key={v.id_variacao} value={v.id_variacao} disabled={semEstoque}>
                    {v.cor} / {v.tamanho} — estoque: {v.qtd_estoque}
                    {semEstoque ? ' (indisponível)' : ''}
                  </option>
                )
              })}
            </select>
          )}
        </div>
      )}

      {/* resumo da variacao selecionada */}
      {variacao && (
        <div className={`border rounded px-3 py-2 text-xs flex items-center justify-between ${
          estoqueAbaixoMinimo
            ? 'bg-yellow-50 border-yellow-200 text-yellow-800'
            : 'bg-blue-50 border-blue-100 text-blue-700'
        }`}>
          <span>
            <span className="font-medium">{produto.nome}</span>
            {' '}— {variacao.cor} / {variacao.tamanho}
          </span>
          <span className={estoqueAbaixoMinimo ? 'text-yellow-600 font-semibold' : 'text-blue-500'}>
            estoque: {variacao.qtd_estoque}
            {estoqueAbaixoMinimo && ' ⚠ baixo'}
          </span>
        </div>
      )}

      {/* quantidade + preco — só visíveis após selecionar variacao */}
      {variacao && (
        <div className="grid grid-cols-2 gap-2">
          <div>
            <label className="block text-xs text-gray-500 mb-1">Qtd</label>
            <input
              type="number" min="1" max={variacao.qtd_estoque} required
              value={quantidade}
              onChange={(e) => setQuantidade(e.target.value)}
              className="w-full border border-gray-200 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400"
            />
          </div>
          <div>
            <label className="block text-xs text-gray-500 mb-1">Preço unit. (R$)</label>
            <input
              type="number" min="0" step="0.01" required
              value={precoUnitario}
              onChange={(e) => setPrecoUnitario(e.target.value)}
              className="w-full border border-gray-200 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400"
            />
          </div>
        </div>
      )}

      <MsgErro msg={erro} />

      <button
        type="submit"
        disabled={submitBloqueado}
        className="w-full bg-gray-800 hover:bg-gray-900 disabled:opacity-40 text-white text-xs font-medium rounded py-1.5 transition-colors"
      >
        {loading ? 'Adicionando...' : '+ Adicionar item'}
      </button>
    </form>
  )
}

// ─── FormPagamento ────────────────────────────────────────────────────────────

function FormPagamento({ idVenda, venda, onAtualizar }) {
  const vazio = { forma_pagamento: 'pix', valor: '', valor_recebido: '', parcelas: '1' }
  const [form, setForm]       = useState(vazio)
  const [loading, setLoading] = useState(false)
  const [erro, setErro]       = useState('')

  const somaAtiva = venda.pagamentos
    ?.filter((p) => p.status === 'ativo')
    .reduce((acc, p) => acc + Number(p.valor), 0) ?? 0
  const saldoRestante = Math.max(0, Number(venda.valor_total) - somaAtiva)

  function change(e) {
    setForm((p) => ({ ...p, [e.target.name]: e.target.value }))
  }

  async function submit(e) {
    e.preventDefault()
    setErro('')

    const valorRecNum = Number(form.valor_recebido)
    if (form.forma_pagamento === 'dinheiro' && valorRecNum > 0 && valorRecNum < Number(form.valor)) {
      setErro('Valor recebido é menor que o valor do pagamento.')
      return
    }

    setLoading(true)
    try {
      const payload = {
        forma_pagamento: form.forma_pagamento,
        valor:           Number(form.valor),
      }
      if (form.forma_pagamento === 'dinheiro' && valorRecNum > 0) {
        payload.valor_recebido = valorRecNum
      }
      if (form.forma_pagamento === 'cartao_credito' && form.parcelas) {
        payload.parcelas = Number(form.parcelas)
      }
      await addPagamento(idVenda, payload)
      setForm(vazio)
      await onAtualizar()
    } catch (err) {
      setErro(err.response?.data?.message ?? err.response?.data?.errors?.message?.[0] ?? 'Erro ao registrar pagamento.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <form onSubmit={submit} className="space-y-2 mt-2">
      <div className="grid grid-cols-2 gap-2">
        <div>
          <label className="block text-xs text-gray-500 mb-1">Forma</label>
          <select
            name="forma_pagamento" value={form.forma_pagamento} onChange={change}
            className="w-full border border-gray-200 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400 bg-white"
          >
            {FORMAS_PAGAMENTO.map((f) => (
              <option key={f} value={f}>{FORMA_LABEL[f] ?? f}</option>
            ))}
          </select>
        </div>
        <div>
          <label className="block text-xs text-gray-500 mb-1">
            Valor (R$)
            {saldoRestante > 0 && (
              <span className="ml-1 text-blue-500 font-normal">
                — saldo: R$ {saldoRestante.toFixed(2)}
              </span>
            )}
            {saldoRestante === 0 && (
              <span className="ml-1 text-green-600 font-normal">— quitado</span>
            )}
          </label>
          <input
            type="number" name="valor" min="0.01" max={saldoRestante || undefined} step="0.01" required
            value={form.valor} onChange={change}
            className="w-full border border-gray-200 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400"
          />
        </div>
      </div>

      {form.forma_pagamento === 'dinheiro' && (
        <div>
          <label className="block text-xs text-gray-500 mb-1">Valor recebido (R$)</label>
          <input
            type="number" name="valor_recebido" min={form.valor || 0} step="0.01"
            value={form.valor_recebido} onChange={change}
            className="w-full border border-gray-200 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400"
          />
        </div>
      )}

      {form.forma_pagamento === 'cartao_credito' && (
        <div>
          <label className="block text-xs text-gray-500 mb-1">Parcelas</label>
          <input
            type="number" name="parcelas" min="1" max="24"
            value={form.parcelas} onChange={change}
            className="w-full border border-gray-200 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400"
          />
        </div>
      )}

      <MsgErro msg={erro} />
      <button
        type="submit" disabled={loading}
        className="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-medium rounded py-1.5 transition-colors"
      >
        {loading ? 'Registrando...' : '+ Registrar pagamento'}
      </button>
    </form>
  )
}

// ─── FormCancelar ─────────────────────────────────────────────────────────────

function FormCancelar({ idVenda, onAtualizar }) {
  const [motivo, setMotivo]   = useState('')
  const [aberto, setAberto]   = useState(false)
  const [loading, setLoading] = useState(false)
  const [erro, setErro]       = useState('')

  async function submit(e) {
    e.preventDefault()
    if (!motivo.trim()) {
      setErro('Informe o motivo do cancelamento.')
      return
    }
    setErro('')
    setLoading(true)
    try {
      await cancelarVenda(idVenda, { motivo_cancelamento: motivo.trim() })
      await onAtualizar()
      setAberto(false)
      setMotivo('')
    } catch (err) {
      const msg =
        err.response?.data?.errors?.message?.[0] ??
        err.response?.data?.errors?.motivo_cancelamento?.[0] ??
        err.response?.data?.message ??
        'Erro ao cancelar.'
      setErro(msg)
    } finally {
      setLoading(false)
    }
  }

  if (!aberto) {
    return (
      <button
        onClick={() => setAberto(true)}
        className="flex-1 border border-red-300 text-red-600 hover:bg-red-50 text-xs font-medium rounded py-1.5 transition-colors"
      >
        Cancelar venda
      </button>
    )
  }

  return (
    <form onSubmit={submit} className="space-y-2 w-full">
      <p className="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded px-3 py-2">
        Ação irreversível. Estoque será restaurado e pagamentos serão estornados.
      </p>
      <textarea
        value={motivo} onChange={(e) => setMotivo(e.target.value)}
        placeholder="Motivo do cancelamento..." rows={2}
        className="w-full border border-gray-200 rounded px-2 py-1.5 text-sm resize-none focus:outline-none focus:ring-1 focus:ring-red-400"
      />
      <MsgErro msg={erro} />
      <div className="flex gap-2">
        <button
          type="button" onClick={() => setAberto(false)}
          className="flex-1 border border-gray-200 text-gray-500 hover:bg-gray-50 text-xs rounded py-1.5"
        >
          Voltar
        </button>
        <button
          type="submit" disabled={loading}
          className="flex-1 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white text-xs font-medium rounded py-1.5"
        >
          {loading ? 'Cancelando...' : 'Confirmar cancelamento'}
        </button>
      </div>
    </form>
  )
}

// ─── VendaDetalhe (painel principal) ─────────────────────────────────────────

export default function VendaDetalhe({ venda, onAtualizar, onFechar }) {
  const [loadingAcao, setLoadingAcao] = useState('')
  const [erroAcao, setErroAcao]       = useState('')
  const [okAcao, setOkAcao]           = useState('')

  const s = STATUS_LABEL[venda.status] ?? { label: venda.status, cls: 'bg-gray-100 text-gray-600' }
  const isRascunho  = venda.status === 'rascunho'
  const isConcluida = venda.status === 'concluida'
  const isCancelada = venda.status === 'cancelada'

  async function acao(fn, nomeAcao, msgOk) {
    setErroAcao('')
    setOkAcao('')
    setLoadingAcao(nomeAcao)
    try {
      await fn()
      setOkAcao(msgOk)
      await onAtualizar()
    } catch (err) {
      setErroAcao(err.response?.data?.message ?? err.response?.data?.errors?.message?.[0] ?? `Erro ao ${nomeAcao}.`)
    } finally {
      setLoadingAcao('')
    }
  }

  async function handleRemoverItem(idItem) {
    await acao(
      () => removeItemVenda(venda.id_venda, idItem),
      'remover', 'Item removido.'
    )
  }

  async function handleConfirmar() {
    await acao(
      () => confirmarVenda(venda.id_venda),
      'confirmar', 'Venda confirmada!'
    )
  }

  async function handleReabrir() {
    await acao(
      () => reabrirVenda(venda.id_venda),
      'reabrir', 'Venda reaberta.'
    )
  }

  return (
    <div className="flex flex-col h-full">
      {/* cabeçalho do painel */}
      <div className="flex items-center justify-between pb-4 border-b border-gray-100">
        <div>
          <p className="text-xs text-gray-400 mb-0.5">Venda #{venda.id_venda}</p>
          <p className="font-semibold text-gray-800 text-sm">
            {venda.cliente?.nome ?? 'Cliente não informado'}
          </p>
        </div>
        <div className="flex items-center gap-3">
          <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${s.cls}`}>
            {s.label}
          </span>
          <button onClick={onFechar} className="text-gray-400 hover:text-gray-700 text-lg leading-none">
            ✕
          </button>
        </div>
      </div>

      {/* corpo com scroll */}
      <div className="flex-1 overflow-y-auto py-1 pr-1">

        {/* total */}
        <div className="mt-4 bg-gray-50 rounded-lg px-4 py-3">
          <p className="text-xs text-gray-500">Valor total</p>
          <p className="text-xl font-semibold text-gray-800 tabular-nums">
            R$ {Number(venda.valor_total).toFixed(2)}
          </p>
          {Number(venda.desconto) > 0 && (
            <p className="text-xs text-gray-400">
              Desconto: R$ {Number(venda.desconto).toFixed(2)}
            </p>
          )}
        </div>

        {/* feedback ações */}
        <MsgErro msg={erroAcao} />
        <MsgOk msg={okAcao} />

        {/* itens */}
        <Secao titulo="Itens">
          {(!venda.itens || venda.itens.length === 0) && (
            <p className="text-xs text-gray-400">Nenhum item adicionado.</p>
          )}
          <div className="space-y-1.5">
            {venda.itens?.map((item) => (
              <div
                key={item.id_item}
                className="flex items-center justify-between bg-gray-50 rounded px-3 py-2 text-xs"
              >
                <span className="text-gray-600">
                  <span className="font-medium text-gray-800">
                    {item.variacao?.produto?.nome ?? `Var #${item.id_variacao}`}
                  </span>
                  {' '}× {item.quantidade}
                </span>
                <div className="flex items-center gap-3">
                  <span className="tabular-nums text-gray-700">
                    R$ {Number(item.subtotal ?? item.quantidade * item.preco_unitario).toFixed(2)}
                  </span>
                  {isRascunho && (
                    <button
                      onClick={() => handleRemoverItem(item.id_item)}
                      disabled={loadingAcao === 'remover'}
                      className="text-red-400 hover:text-red-600 disabled:opacity-40 font-bold"
                      title="Remover item"
                    >
                      ✕
                    </button>
                  )}
                </div>
              </div>
            ))}
          </div>

          {isRascunho && (
            <FormItem idVenda={venda.id_venda} onAtualizar={onAtualizar} />
          )}
        </Secao>

        {/* pagamentos */}
        <Secao titulo="Pagamentos">
          {(!venda.pagamentos || venda.pagamentos.length === 0) && (
            <p className="text-xs text-gray-400">Nenhum pagamento registrado.</p>
          )}
          <div className="space-y-1.5">
            {venda.pagamentos?.map((pag) => (
              <div
                key={pag.id_pagamento}
                className="flex items-center justify-between bg-gray-50 rounded px-3 py-2 text-xs"
              >
                <span className="text-gray-600">
                  {FORMA_LABEL[pag.forma_pagamento] ?? pag.forma_pagamento}
                  {pag.parcelas > 1 && ` (${pag.parcelas}×)`}
                </span>
                <div className="flex items-center gap-3">
                  <span className="tabular-nums text-gray-700">
                    R$ {Number(pag.valor).toFixed(2)}
                  </span>
                  <span className={`text-xs px-1.5 py-0.5 rounded-full font-medium ${
                    pag.status === 'ativo'
                      ? 'bg-green-100 text-green-700'
                      : 'bg-gray-100 text-gray-400'
                  }`}>
                    {pag.status}
                  </span>
                </div>
              </div>
            ))}
          </div>

          {isRascunho && (
            <FormPagamento idVenda={venda.id_venda} venda={venda} onAtualizar={onAtualizar} />
          )}
        </Secao>

        {/* motivo cancelamento */}
        {isCancelada && venda.motivo_cancelamento && (
          <Secao titulo="Motivo do cancelamento">
            <p className="text-xs text-gray-600 bg-red-50 rounded px-3 py-2">
              {venda.motivo_cancelamento}
            </p>
          </Secao>
        )}
      </div>

      {/* ações */}
      <div className="pt-4 border-t border-gray-100 space-y-2 flex-shrink-0">
        {isRascunho && (
          <div className="flex gap-2">
            <button
              onClick={handleConfirmar}
              disabled={!!loadingAcao}
              className="flex-1 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white text-xs font-medium rounded py-1.5 transition-colors"
            >
              {loadingAcao === 'confirmar' ? 'Confirmando...' : 'Confirmar venda'}
            </button>
            <FormCancelar idVenda={venda.id_venda} onAtualizar={onAtualizar} />
          </div>
        )}

        {isConcluida && (
          <div className="flex gap-2">
            <button
              onClick={handleReabrir}
              disabled={!!loadingAcao}
              className="flex-1 border border-blue-300 text-blue-600 hover:bg-blue-50 text-xs font-medium rounded py-1.5 transition-colors"
            >
              {loadingAcao === 'reabrir' ? 'Reabrindo...' : 'Reabrir venda'}
            </button>
            <FormCancelar idVenda={venda.id_venda} onAtualizar={onAtualizar} />
          </div>
        )}

        {isCancelada && (
          <button
            onClick={handleReabrir}
            disabled={!!loadingAcao}
            className="w-full border border-blue-300 text-blue-600 hover:bg-blue-50 text-xs font-medium rounded py-1.5 transition-colors"
          >
            {loadingAcao === 'reabrir' ? 'Reabrindo...' : 'Reabrir venda'}
          </button>
        )}
      </div>
    </div>
  )
}
