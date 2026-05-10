<template>
  <div class="pagination-bar erp-pagination erp-card">
    <div class="pagination-info">
      <span class="showing">{{ showingLabel }}</span>
      <label class="per-page">
        <span>Listar</span>
        <select class="erp-select pagination-select" :value="perPage" @change="onPerPageChange">
          <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
        </select>
        <span>itens</span>
      </label>
    </div>

    <nav class="pagination-nav" aria-label="Paginacao">
      <button type="button" class="nav-btn" :disabled="page <= 1 || disabled" title="Primeira pagina" @click="goPage(1)">
        «
      </button>
      <button type="button" class="nav-btn" :disabled="page <= 1 || disabled" title="Pagina anterior" @click="goPage(page - 1)">
        ‹
      </button>

      <template v-for="(p, idx) in pageButtons" :key="'p-' + idx">
        <button
          v-if="p !== 'ellipsis'"
          type="button"
          class="page-btn"
          :class="{ active: p === page }"
          :disabled="disabled"
          @click="goPage(p)"
        >
          {{ p }}
        </button>
        <span v-else class="ellipsis">…</span>
      </template>

      <button type="button" class="nav-btn" :disabled="page >= safeLastPage || disabled" title="Proxima pagina" @click="goPage(page + 1)">
        ›
      </button>
      <button type="button" class="nav-btn" :disabled="page >= safeLastPage || disabled" title="Ultima pagina" @click="goPage(safeLastPage)">
        »
      </button>
    </nav>

    <div class="pagination-meta muted">
      Pagina {{ page }} de {{ safeLastPage }}
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  page: { type: Number, required: true },
  lastPage: { type: Number, required: true },
  total: { type: Number, required: true },
  perPage: { type: Number, required: true },
  itemsLength: { type: Number, required: true },
  perPageOptions: { type: Array, default: () => [10, 20, 50] },
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:page', 'update:perPage'])

const safeLastPage = computed(() => Math.max(1, props.lastPage || 1))

const showingLabel = computed(() => {
  const total = props.total ?? 0
  if (total === 0) {
    return 'Nenhum registro'
  }
  if (props.itemsLength === 0) {
    return `Nenhum resultado nesta página — ${total} no total`
  }
  const from = (props.page - 1) * props.perPage + 1
  const to = from + props.itemsLength - 1
  return `Mostrando ${from}–${to} de ${total}`
})

const pageButtons = computed(() => {
  const current = props.page
  const last = safeLastPage.value
  const delta = 2
  const range = []
  const rangeWithDots = []
  let l

  for (let i = 1; i <= last; i += 1) {
    if (i === 1 || i === last || (i >= current - delta && i <= current + delta)) {
      range.push(i)
    }
  }

  range.forEach((i) => {
    if (l) {
      if (i - l === 2) {
        rangeWithDots.push(l + 1)
      } else if (i - l > 2) {
        rangeWithDots.push('ellipsis')
      }
    }
    rangeWithDots.push(i)
    l = i
  })

  return rangeWithDots
})

function goPage(p) {
  if (props.disabled || p < 1 || p > safeLastPage.value || p === props.page) return
  emit('update:page', p)
}

function onPerPageChange(e) {
  const next = Number(e.target.value)
  if (next === props.perPage) return
  emit('update:perPage', next)
}
</script>

<style scoped>
.pagination-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: 0;
  padding: 0.75rem 1rem;
}

.pagination-info {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1rem;
}

.showing {
  font-size: 0.8125rem;
  color: var(--erp-text-secondary, #475569);
}

.per-page {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8125rem;
  color: var(--erp-text-muted, #64748b);
}

.pagination-select {
  width: auto;
  min-height: 2.25rem;
  padding: 0.35rem 0.5rem;
}

.pagination-nav {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px;
}

.nav-btn,
.page-btn {
  min-width: 36px;
  height: 36px;
  padding: 0 10px;
  border: 1px solid var(--erp-border, #e2e8f0);
  border-radius: var(--erp-radius, 8px);
  background: #f8fafc;
  color: var(--erp-text, #0f172a);
  font-size: 0.875rem;
  cursor: pointer;
  font-family: inherit;
}

.nav-btn:hover:not(:disabled),
.page-btn:hover:not(:disabled) {
  background: var(--erp-accent-soft, #e8f0fe);
  border-color: #90caf9;
}

.nav-btn:disabled,
.page-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.page-btn.active {
  background: var(--erp-accent, #0d47a1);
  border-color: var(--erp-accent, #0d47a1);
  color: #fff;
  font-weight: 700;
}

.ellipsis {
  padding: 0 6px;
  color: #94a3b8;
  user-select: none;
}

.pagination-meta {
  font-size: 0.75rem;
}

.muted {
  color: var(--erp-text-muted, #64748b);
}

@media (max-width: 768px) {
  .pagination-bar {
    flex-direction: column;
    align-items: stretch;
  }
  .pagination-nav {
    justify-content: center;
  }
}
</style>
