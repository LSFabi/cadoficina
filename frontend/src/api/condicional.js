import api from './axios'

export const getCondicionais      = (params)           => api.get('/condicionais', { params })
export const getCondicional       = (id)               => api.get(`/condicionais/${id}`)
export const createCondicional    = (data)             => api.post('/condicionais', data)
export const addItemCondicional   = (id, data)         => api.post(`/condicionais/${id}/itens`, data)
export const devolverItem         = (id, idItem, data) => api.patch(`/condicionais/${id}/itens/${idItem}/devolver`, data)
export const fecharCondicional    = (id)               => api.patch(`/condicionais/${id}/fechar`)
export const cancelarCondicional  = (id, data)         => api.patch(`/condicionais/${id}/cancelar`, data)
