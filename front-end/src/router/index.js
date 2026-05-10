import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  { path: '/', redirect: '/clients' },
  {
    path: '/clients',
    meta: { title: 'Clientes', subtitle: 'Cadastro e relacionamento' },
    component: () => import('../views/ClientsView.vue'),
  },
  {
    path: '/services',
    meta: { title: 'Serviços', subtitle: 'Valor base mensal recorrente' },
    component: () => import('../views/ServicesView.vue'),
  },
  {
    path: '/contracts',
    meta: { title: 'Contratos', subtitle: 'Itens, totais e status' },
    component: () => import('../views/ContractsView.vue'),
  },
  {
    path: '/discounts',
    meta: { title: 'Regras de desconto', subtitle: 'Por quantidade contratada' },
    component: () => import('../views/DiscountsView.vue'),
  },
]

export default createRouter({
  history: createWebHistory(),
  routes,
})
