import api from './axios'

export const getProdutos = () => api.get('/produtos')
export const getProduto = (id) => api.get(`/produtos/${id}`)
export const createProduto = (data) => api.post('/produtos', data)
export const updateProduto = (id, data) => api.put(`/produtos/${id}`, data)
export const deleteProduto = (id) => api.delete(`/produtos/${id}`)

export const getVariacoes = (idProduto) => api.get(`/produtos/${idProduto}/variacoes`)
export const createVariacao = (idProduto, data) => api.post(`/produtos/${idProduto}/variacoes`, data)
export const updateVariacao = (idProduto, idVariacao, data) => api.put(`/produtos/${idProduto}/variacoes/${idVariacao}`, data)
export const deleteVariacao = (idProduto, idVariacao) => api.delete(`/produtos/${idProduto}/variacoes/${idVariacao}`)
