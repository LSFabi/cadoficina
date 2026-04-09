import { useAuth } from '../../context/AuthContext'
import { useNavigate } from 'react-router-dom'

export default function Topbar() {
  const { usuario, logout } = useAuth()
  const navigate = useNavigate()

  async function handleLogout() {
    await logout()
    navigate('/login')
  }

  return (
    <header className="h-14 bg-white border-b border-gray-200 flex items-center justify-between px-6">
      <span className="text-sm text-gray-500">
        {usuario?.perfil === 'proprietaria' ? 'Proprietária' : 'Operador'}
      </span>
      <div className="flex items-center gap-4">
        <span className="text-sm font-medium text-gray-700">{usuario?.nome}</span>
        <button
          onClick={handleLogout}
          className="text-sm text-red-500 hover:text-red-700 transition-colors"
        >
          Sair
        </button>
      </div>
    </header>
  )
}
