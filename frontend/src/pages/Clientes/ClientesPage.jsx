import { useEffect, useState } from 'react'
import { getClientes } from '../../api/cliente'

export default function ClientesPage() {
  const [clientes, setClientes] = useState([])
  const [carregando, setCarregando] = useState(true)
  const [erro, setErro] = useState('')

  useEffect(() => {
    getClientes()
      .then(({ data }) => setClientes(data))
      .catch(() => setErro('Erro ao carregar clientes.'))
      .finally(() => setCarregando(false))
  }, [])

  if (carregando) return <p className="text-gray-400 text-sm">Carregando...</p>
  if (erro) return <p className="text-red-500 text-sm">{erro}</p>

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h2 className="text-lg font-semibold text-gray-800">Clientes</h2>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-gray-50 text-gray-500 uppercase text-xs">
            <tr>
              <th className="px-4 py-3 text-left">#</th>
              <th className="px-4 py-3 text-left">Nome</th>
              <th className="px-4 py-3 text-left">Telefone</th>
              <th className="px-4 py-3 text-left">Email</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {clientes.length === 0 && (
              <tr>
                <td colSpan={4} className="px-4 py-6 text-center text-gray-400">
                  Nenhum cliente encontrado.
                </td>
              </tr>
            )}
            {clientes.map((c) => (
              <tr key={c.id_cliente} className="hover:bg-gray-50">
                <td className="px-4 py-3 text-gray-500">{c.id_cliente}</td>
                <td className="px-4 py-3 font-medium text-gray-700">{c.nome}</td>
                <td className="px-4 py-3 text-gray-500">{c.telefone}</td>
                <td className="px-4 py-3 text-gray-400">{c.email ?? '—'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
