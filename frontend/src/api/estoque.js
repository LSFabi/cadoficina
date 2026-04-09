import api from './axios'

export const getVariacoesEstoque = (params) => api.get('/estoque/variacoes', { params })
export const getMovimentacoes = (params) => api.get('/estoque/movimentacoes', { params })
export const ajustarEstoque = (data) => api.post('/estoque/ajuste', data)
