import api from './axios'

export const getVendas = () => api.get('/vendas')
export const getVenda = (id) => api.get(`/vendas/${id}`)
export const createVenda = (data) => api.post('/vendas', data)
export const addItemVenda = (id, data) => api.post(`/vendas/${id}/itens`, data)
export const removeItemVenda = (idVenda, idItem) => api.delete(`/vendas/${idVenda}/itens/${idItem}`)
export const confirmarVenda = (id) => api.patch(`/vendas/${id}/confirmar`)
export const cancelarVenda = (id, data) => api.patch(`/vendas/${id}/cancelar`, data)
export const reabrirVenda = (id) => api.patch(`/vendas/${id}/reabrir`)
