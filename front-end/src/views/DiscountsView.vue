<template>
  <section class="erp-page" :aria-busy="loading">
    <div class="erp-page__intro">
      <h2 class="erp-page__title">Descontos por quantidade</h2>
      <p class="erp-page__lead">
        Regras persistidas em <code class="erp-code">discount_rules</code>. Visualização e edição em modal; ativar/desativar e excluir na grade.
      </p>
    </div>

    <div class="erp-toolbar">
      <span />
      <div class="erp-toolbar__actions">
        <button type="button" class="erp-btn erp-btn--secondary" :disabled="loading" @click="loadRules">Atualizar</button>
      </div>
    </div>

    <div class="erp-card">
      <div class="erp-card__header erp-card__header--plain">
        <h3 class="erp-card__title">Nova regra</h3>
      </div>
      <div class="erp-card__body">
        <form class="erp-form" @submit.prevent="createRule">
          <div class="erp-form__grid cols-discount">
            <div class="erp-field">
              <label class="erp-label" for="rule-name">Nome</label>
              <input id="rule-name" v-model="form.name" class="erp-input" required autocomplete="off" />
            </div>
            <div class="erp-field">
              <label class="erp-label" for="min_quantity">Qtd. mínima</label>
              <input id="min_quantity" v-model.number="form.min_quantity" class="erp-input" type="number" min="1" required />
            </div>
            <div class="erp-field">
              <label class="erp-label" for="value_type">Tipo de valor</label>
              <select id="value_type" v-model="form.value_type" class="erp-select">
                <option value="percent">Percentual (%)</option>
                <option value="fixed">Valor fixo (R$)</option>
              </select>
            </div>
            <div class="erp-field">
              <label class="erp-label" for="value-pct">{{ form.value_type === 'percent' ? 'Percentual' : 'Valor fixo' }}</label>
              <input
                v-if="form.value_type === 'percent'"
                id="value-pct"
                v-model.number="form.value"
                class="erp-input"
                type="number"
                step="0.01"
                min="0"
                required
              />
              <div v-else class="erp-money">
                <span class="erp-money__prefix">R$</span>
                <input
                  id="value-fix"
                  :value="form.value_display"
                  class="erp-input"
                  type="text"
                  inputmode="decimal"
                  autocomplete="off"
                  required
                  @input="onCreateMoneyInput($event.target.value)"
                />
              </div>
            </div>
          </div>
          <div class="erp-form__actions">
            <button type="submit" class="erp-btn erp-btn--primary" :disabled="saving">{{ saving ? 'Salvando…' : 'Adicionar regra' }}</button>
          </div>
        </form>
      </div>
    </div>

    <div class="erp-card erp-card__body--flush">
      <div class="erp-card__header">
        <h3 class="erp-card__title">Regras configuradas</h3>
      </div>

      <p v-if="loading" class="erp-state" role="status">Carregando…</p>

      <template v-else>
        <div v-if="!rules.length" class="erp-state erp-state--empty" role="status">Nenhuma regra nesta página.</div>
        <div v-else class="erp-table-scroll">
          <div class="erp-data-table">
            <div class="erp-data-table__head grid-rules">
              <span>Nome</span><span>Qtd mín.</span><span>Valor</span><span>Status</span><span class="cell-actions">Ações</span>
            </div>
            <div v-for="rule in rules" :key="rule.id" class="erp-data-table__row grid-rules">
              <span>{{ rule.name }}</span>
              <span class="erp-data-table__cell--muted">{{ rule.min_quantity }}</span>
              <span>{{ formatRuleValue(rule) }}</span>
              <span>
                <span :class="rule.is_active ? 'erp-chip erp-chip--ok' : 'erp-chip erp-chip--off'">
                  {{ rule.is_active ? 'Ativo' : 'Inativo' }}
                </span>
              </span>
              <span class="cell-actions erp-stack">
                <button type="button" class="erp-btn erp-btn--ghost erp-btn--sm" :disabled="saving" @click="openView(rule)">Ver</button>
                <button type="button" class="erp-btn erp-btn--secondary erp-btn--sm" :disabled="saving" @click="openEdit(rule)">Editar</button>
                <button type="button" class="erp-btn erp-btn--warn erp-btn--sm" :disabled="saving" @click="toggleActive(rule)">
                  {{ rule.is_active ? 'Desativar' : 'Ativar' }}
                </button>
                <button type="button" class="erp-btn erp-btn--danger erp-btn--sm" :disabled="saving" @click="removeRule(rule.id)">Excluir</button>
              </span>
            </div>
          </div>
        </div>
        <div class="erp-card__body erp-card__body--divider">
          <PaginationBar
            :page="page"
            :per-page="perPage"
            :last-page="lastPage"
            :total="total"
            :items-length="rules.length"
            :per-page-options="perPageOptions"
            :disabled="loading"
            @update:page="onPageChange"
            @update:per-page="onPerPageChange"
          />
        </div>
      </template>
    </div>

    <p v-if="flashSuccess" class="erp-alert erp-alert--success flash-margin" role="status">{{ flashSuccess }}</p>
    <p v-if="error" class="erp-alert flash-margin" role="alert">{{ error }}</p>

    <ErpModal v-model="viewOpen" title="Regra de desconto" mode="view" :fields="viewFields" :initial-values="viewInitial" />

    <ErpModal v-model="editOpen" title="Editar regra" mode="edit" :fields="[]" :loading="editSaving" :error-text="editError">
      <div class="erp-form__grid cols-discount">
        <div class="erp-field">
          <label class="erp-label" for="em-name">Nome</label>
          <input id="em-name" v-model="editForm.name" class="erp-input" autocomplete="off" />
        </div>
        <div class="erp-field">
          <label class="erp-label" for="em-min">Qtd. mínima</label>
          <input id="em-min" v-model.number="editForm.min_quantity" class="erp-input" type="number" min="1" />
        </div>
        <div class="erp-field">
          <label class="erp-label" for="em-vt">Tipo de valor</label>
          <select id="em-vt" v-model="editForm.value_type" class="erp-select" @change="onEditValueTypeChange">
            <option value="percent">Percentual (%)</option>
            <option value="fixed">Valor fixo (R$)</option>
          </select>
        </div>
        <div class="erp-field">
          <label class="erp-label" for="em-val">{{ editForm.value_type === 'percent' ? 'Percentual' : 'Valor fixo' }}</label>
          <input
            v-if="editForm.value_type === 'percent'"
            id="em-val"
            v-model.number="editForm.value"
            class="erp-input"
            type="number"
            step="0.01"
            min="0"
          />
          <div v-else class="erp-money">
            <span class="erp-money__prefix">R$</span>
            <input
              class="erp-input"
              type="text"
              inputmode="decimal"
              autocomplete="off"
              :value="editForm.value_display"
              @input="onEditMoneyInput($event.target.value)"
            />
          </div>
        </div>
        <div class="erp-field">
          <label class="erp-label" for="em-act">Situação</label>
          <select id="em-act" v-model="editForm.is_active" class="erp-select">
            <option :value="true">Ativo</option>
            <option :value="false">Inativo</option>
          </select>
        </div>
      </div>
      <template #footer-actions>
        <button type="button" class="erp-btn erp-btn--ghost" :disabled="editSaving" @click="editOpen = false">Cancelar</button>
        <button type="button" class="erp-btn erp-btn--primary" :disabled="editSaving" @click="saveEdit">{{ editSaving ? 'Salvando…' : 'Salvar' }}</button>
      </template>
    </ErpModal>
  </section>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import api from '../services/api'
import PaginationBar from '../components/PaginationBar.vue'
import ErpModal from '../components/ErpModal.vue'
import { formatCurrencyInput, parseCurrencyInput, formatMoneyBR } from '../utils/currency'
import { apiErrorMessage } from '../utils/apiMessage'

const viewFields = [
  { key: 'name', label: 'Nome', type: 'text' },
  { key: 'min_quantity', label: 'Quantidade mínima', type: 'number' },
  { key: 'tipo', label: 'Tipo', type: 'text' },
  { key: 'valor_fmt', label: 'Valor', type: 'text' },
  { key: 'status_fmt', label: 'Situação', type: 'text' },
]

const rules = ref([])
const error = ref('')
const flashSuccess = ref('')
const loading = ref(true)
const saving = ref(false)
const page = ref(1)
const perPage = ref(10)
const lastPage = ref(1)
const total = ref(0)
const perPageOptions = [10, 20, 50]

const viewOpen = ref(false)
const viewInitial = ref({})

const editOpen = ref(false)
const editSaving = ref(false)
const editError = ref('')
const editingId = ref(null)
const editForm = ref({
  name: '',
  min_quantity: 1,
  value_type: 'percent',
  value: 0,
  value_display: '0,00',
  is_active: true,
})

const form = ref({
  name: '',
  min_quantity: 1,
  value_type: 'percent',
  value: 0,
  value_display: '0,00',
})

function formatRuleValue(rule) {
  if (rule.value_type === 'percent') {
    return `${Number(rule.value).toFixed(2)}%`
  }
  return formatMoneyBR(rule.value)
}

const loadRules = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/discount-rules', { params: { page: page.value, per_page: perPage.value } })
    rules.value = data.data ?? []
    lastPage.value = data.meta?.last_page ?? 1
    total.value = data.meta?.total ?? 0
  } catch {
    error.value = 'Não foi possível carregar as regras.'
    rules.value = []
  } finally {
    loading.value = false
  }
}

function onPageChange(p) {
  page.value = p
  loadRules()
}

function onPerPageChange(pp) {
  perPage.value = pp
  page.value = 1
  loadRules()
}

watch(
  () => form.value.value_type,
  () => {
    form.value.value = 0
    form.value.value_display = '0,00'
  }
)

const onCreateMoneyInput = (value) => {
  form.value.value_display = formatCurrencyInput(value)
  form.value.value = parseCurrencyInput(`R$ ${form.value.value_display}`)
}

function openView(rule) {
  viewInitial.value = {
    name: rule.name,
    min_quantity: rule.min_quantity,
    tipo: 'Quantidade (quantity)',
    valor_fmt: formatRuleValue(rule),
    status_fmt: rule.is_active ? 'Ativa' : 'Inativa',
  }
  viewOpen.value = true
}

function openEdit(rule) {
  editingId.value = rule.id
  editError.value = ''
  const isFixed = rule.value_type === 'fixed'
  const cents = Math.round(Number(rule.value) * 100)
  editForm.value = {
    name: rule.name,
    min_quantity: rule.min_quantity,
    value_type: rule.value_type,
    value: Number(rule.value),
    value_display: isFixed ? formatCurrencyInput(String(cents)) : '0,00',
    is_active: !!rule.is_active,
  }
  editOpen.value = true
}

const onEditMoneyInput = (value) => {
  editForm.value.value_display = formatCurrencyInput(value)
  editForm.value.value = parseCurrencyInput(`R$ ${editForm.value.value_display}`)
}

function onEditValueTypeChange() {
  if (editForm.value.value_type === 'percent') {
    editForm.value.value = 0
  } else {
    editForm.value.value = 0
    editForm.value.value_display = '0,00'
  }
}

const saveEdit = async () => {
  editSaving.value = true
  editError.value = ''
  try {
    await api.put(`/discount-rules/${editingId.value}`, {
      name: editForm.value.name,
      type: 'quantity',
      min_quantity: editForm.value.min_quantity,
      value_type: editForm.value.value_type,
      value: editForm.value.value,
      is_active: editForm.value.is_active,
    })
    editOpen.value = false
    flashSuccess.value = 'Regra atualizada.'
    window.setTimeout(() => {
      flashSuccess.value = ''
    }, 3000)
    await loadRules()
  } catch (err) {
    editError.value = apiErrorMessage(err, 'Erro ao salvar.')
  } finally {
    editSaving.value = false
  }
}

const createRule = async () => {
  saving.value = true
  error.value = ''
  try {
    await api.post('/discount-rules', {
      name: form.value.name,
      type: 'quantity',
      min_quantity: form.value.min_quantity,
      value_type: form.value.value_type,
      value: form.value.value,
      is_active: true,
    })
    form.value = { name: '', min_quantity: 1, value_type: 'percent', value: 0, value_display: '0,00' }
    flashSuccess.value = 'Regra criada.'
    window.setTimeout(() => {
      flashSuccess.value = ''
    }, 3000)
    page.value = 1
    await loadRules()
  } catch (err) {
    error.value = apiErrorMessage(err, 'Erro ao criar regra.')
  } finally {
    saving.value = false
  }
}

const toggleActive = async (rule) => {
  saving.value = true
  error.value = ''
  try {
    await api.put(`/discount-rules/${rule.id}`, { is_active: !rule.is_active })
    flashSuccess.value = rule.is_active ? 'Regra desativada.' : 'Regra ativada.'
    window.setTimeout(() => {
      flashSuccess.value = ''
    }, 3000)
    await loadRules()
  } catch (err) {
    error.value = apiErrorMessage(err, 'Erro ao atualizar.')
  } finally {
    saving.value = false
  }
}

const removeRule = async (id) => {
  saving.value = true
  error.value = ''
  try {
    await api.delete(`/discount-rules/${id}`)
    flashSuccess.value = 'Regra removida.'
    window.setTimeout(() => {
      flashSuccess.value = ''
    }, 3000)
    await loadRules()
  } catch (err) {
    error.value = apiErrorMessage(err, 'Erro ao remover.')
  } finally {
    saving.value = false
  }
}

onMounted(loadRules)
</script>

<style scoped>
.cols-discount {
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
}
.grid-rules {
  grid-template-columns: minmax(100px, 1.6fr) 90px minmax(88px, 1fr) 110px minmax(220px, 1.8fr);
  align-items: start;
}
.cell-actions {
  justify-content: flex-end;
}
.flash-margin {
  margin-top: 1rem;
}
@media (max-width: 1024px) {
  .grid-rules {
    grid-template-columns: 1fr 1fr;
  }
  .cell-actions {
    grid-column: 1 / -1;
    justify-content: flex-start;
  }
}
</style>
