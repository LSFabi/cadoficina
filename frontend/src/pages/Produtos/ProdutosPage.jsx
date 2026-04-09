import { useEffect, useState } from 'react'
import { getProdutos } from '../../api/produto'

export default function ProdutosPage() {
  const [produtos, setProdutos] = useState([])
  const [carregando, setCarregando] = useState(true)
  const [erro, setErro] = useState('')

  useEffect(() => {
    getProdutos()
      .then(({ data }) => setProdutos(data))
      .catch(() => setErro('Erro ao carregar produtos.'))
      .finally(() => setCarregando(false))
  }, [])

  if (carregando) return <p className="text-gray-400 text-sm">Carregando...</p>
  if (erro) return <p className="text-red-500 text-sm">{erro}</p>

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h2 className="text-lg font-semibold text-gray-800">Produtos</h2>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-gray-50 text-gray-500 uppercase text-xs">
            <tr>
              <th className="px-4 py-3 text-left">#</th>
              <th className="px-4 py-3 text-left">Nome</th>
              <th className="px-4 py-3 text-left">Categoria</th>
              <th className="px-4 py-3 text-right">Preço venda</th>
              <th className="px-4 py-3 text-center">Ativo</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {produtos.length === 0 && (
              <tr>
                <td colSpan={5} className="px-4 py-6 text-center text-gray-400">
                  Nenhum produto encontrado.
                </td>
              </tr>
            )}
            {produtos.map((p) => (
              <tr key={p.id_produto} className="hover:bg-gray-50">
                <td className="px-4 py-3 text-gray-500">{p.id_produto}</td>
                <td className="px-4 py-3 font-medium text-gray-700">{p.nome}</td>
                <td className="px-4 py-3 text-gray-500">{p.categoria?.nome ?? '—'}</td>
                <td className="px-4 py-3 text-right tabular-nums">
                  R$ {Number(p.preco_venda).toFixed(2)}
                </td>
                <td className="px-4 py-3 text-center">
                  <span
                    className={`inline-block w-2 h-2 rounded-full ${
                      p.ativo ? 'bg-green-500' : 'bg-gray-300'
                    }`}
                  />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
