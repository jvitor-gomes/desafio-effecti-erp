<template>
  <div class="erp-app">
    <a href="#main-content" class="skip-link">Ir para o conteúdo</a>

    <aside class="erp-sidebar" aria-label="Navegação principal">
      <div class="erp-sidebar__brand">
        <div class="erp-sidebar__logo">
          <span class="erp-sidebar__mark" aria-hidden="true">E</span>
          <div class="erp-sidebar__titles">
            <strong>ERP Contratos</strong>
            <span>Contratos & serviços</span>
          </div>
        </div>
      </div>
      <nav class="erp-nav">
        <RouterLink v-for="link in navLinks" :key="link.to" :to="link.to" class="erp-nav__link">
          <span class="erp-nav__icon" aria-hidden="true">{{ link.icon }}</span>
          {{ link.label }}
        </RouterLink>
      </nav>
    </aside>

    <div class="erp-main">
      <header class="erp-topbar">
        <div class="erp-topbar__titles">
          <h1 class="erp-topbar__title">{{ title }}</h1>
          <p class="erp-topbar__subtitle">{{ subtitle }}</p>
        </div>
      </header>
      <main id="main-content" class="erp-content" tabindex="-1">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const navLinks = [
  { to: '/clients', label: 'Clientes', icon: '◆' },
  { to: '/services', label: 'Serviços', icon: '◇' },
  { to: '/contracts', label: 'Contratos', icon: '▣' },
  { to: '/discounts', label: 'Descontos', icon: '%' },
]

const title = computed(() => (route.meta.title ? String(route.meta.title) : 'Painel'))
const subtitle = computed(() => (route.meta.subtitle ? String(route.meta.subtitle) : 'Gestão operacional'))
</script>
