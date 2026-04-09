import api from './axios'

export const addPagamento = (idVenda, data) => api.post(`/vendas/${idVenda}/pagamentos`, data)
export const estornarPagamento = (idPagamento) => api.patch(`/pagamentos/${idPagamento}/estornar`)
