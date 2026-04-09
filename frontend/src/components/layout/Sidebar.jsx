import { NavLink } from 'react-router-dom'

const navItems = [
  { to: '/dashboard',    label: 'Dashboard'    },
  { to: '/vendas',       label: 'Vendas'       },
  { to: '/condicionais', label: 'Condicionais' },
  { to: '/produtos',     label: 'Produtos'     },
  { to: '/clientes',     label: 'Clientes'     },
]

export default function Sidebar() {
  return (
    <aside className="w-56 bg-gray-900 text-white flex flex-col">
      <div className="h-14 flex items-center px-5 border-b border-gray-700">
        <span className="font-semibold text-sm tracking-wide">CADOficina</span>
      </div>
      <nav className="flex-1 py-4">
        {navItems.map(({ to, label }) => (
          <NavLink
            key={to}
            to={to}
            className={({ isActive }) =>
              `block px-5 py-2.5 text-sm transition-colors ${
                isActive
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-300 hover:bg-gray-800 hover:text-white'
              }`
            }
          >
            {label}
          </NavLink>
        ))}
      </nav>
    </aside>
  )
}
