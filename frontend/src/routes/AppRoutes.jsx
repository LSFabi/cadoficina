import { Routes, Route, Navigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import LoginPage from '../pages/Login/LoginPage'
import DashboardPage from '../pages/Dashboard/DashboardPage'
import VendasPage from '../pages/Vendas/VendasPage'
import CondicionaisPage from '../pages/Condicionais/CondicionaisPage'
import ProdutosPage from '../pages/Produtos/ProdutosPage'
import ClientesPage from '../pages/Clientes/ClientesPage'
import AppLayout from '../components/layout/AppLayout'

function ProtectedRoute({ children }) {
  const { token } = useAuth()
  return token ? children : <Navigate to="/login" replace />
}

export default function AppRoutes() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route
        path="/"
        element={
          <ProtectedRoute>
            <AppLayout />
          </ProtectedRoute>
        }
      >
        <Route index element={<Navigate to="/dashboard" replace />} />
        <Route path="dashboard" element={<DashboardPage />} />
        <Route path="vendas"       element={<VendasPage />} />
        <Route path="condicionais" element={<CondicionaisPage />} />
        <Route path="produtos"     element={<ProdutosPage />} />
        <Route path="clientes"     element={<ClientesPage />} />
      </Route>
      <Route path="*" element={<Navigate to="/dashboard" replace />} />
    </Routes>
  )
}
