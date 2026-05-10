<template>
  <section class="erp-page" :aria-busy="loading">
    <div class="erp-page__intro">
      <h2 class="erp-page__title">Serviços</h2>
      <p class="erp-page__lead">Catálogo mensal recorrente — valor base usado como padrão nos itens de contrato. CRUD completo com modal de edição.</p>
    </div>

    <div class="erp-toolbar">
      <span />
      <div class="erp-toolbar__actions">
        <button type="button" class="erp-btn erp-btn--secondary" :disabled="loading" @click="loadServices">Atualizar lista</button>
      </div>
    </div>

    <div class="erp-card">
      <div class="erp-card__header erp-card__header--plain">
        <h3 class="erp-card__title">Novo serviço</h3>
        <p class="erp-card__desc">Valores em reais (duas casas no backend).</p>
      </div>
      <div class="erp-card__body">
        <form class="erp-form" @submit.prevent="createService">
          <div class="erp-form__grid cols-2">
            <div class="erp-field span-2-md">
              <label class="erp-label" for="service-name">Nome do serviço</label>
              <input id="service-name" v-model="form.name" class="erp-input" required autocomplete="off" />
            </div>
            <div class="erp-field">
              <label class="erp-label" for="service-value">Valor base mensal</label>
              <div class="erp-money">
                <span class="erp-money__prefix" aria-hidden="true">R$</span>
                <input
                  id="service-value"
                  :value="form.base_monthly_value_display"
                  class="erp-input"
                  type="text"
                  inputmode="decimal"
                  autocomplete="off"
                  required
                  aria-describedby="svc-hint"
                  @input="onMoneyInput($event.target.value)"
                />
              </div>
              <span id="svc-hint" class="erp-hint">Entrada em centavos mascarada (ex.: digite 125050 → 1.250,50).</span>
            </div>
          </div>
          <div class="erp-form__actions">
            <button type="submit" class="erp-btn erp-btn--primary" :disabled="saving">{{ saving ? 'Salvando…' : 'Salvar serviço' }}</button>
          </div>
        </form>
      </div>
    </div>

    <div class="erp-card erp-card__body--flush">
      <div class="erp-card__header">
        <h3 class="erp-card__title">Cadastrados</h3>
      </div>

      <p v-if="loading" class="erp-state" role="status">Carregando serviços…</p>

      <template v-else>
        <div v-if="!services.length" class="erp-state erp-state--empty" role="status">Nenhum serviço nesta página.</div>
        <div v-else class="erp-table-scroll">
          <div class="erp-data-table">
            <div class="erp-data-table__head grid-services">
              <span>ID</span><span>Serviço</span><span>Valor base</span><span class="cell-actions">Ações</span>
            </div>
            <div v-for="service in services" :key="service.id" class="erp-data-table__row grid-services">
              <span class="erp-data-table__cell--muted">{{ service.id }}</span>
              <span>{{ service.name }}</span>
              <span class="erp-data-table__cell--strong">{{ formatMoneyBR(service.base_monthly_value) }}</span>
              <span class="cell-actions erp-stack">
                <button type="button" class="erp-btn erp-btn--ghost erp-btn--sm" :disabled="saving" @click="openView(service.id)">Ver</button>
                <button type="button" class="erp-btn erp-btn--secondary erp-btn--sm" :disabled="saving" @click="openEdit(service.id)">Editar</button>
                <button type="button" class="erp-btn erp-btn--danger erp-btn--sm" :disabled="saving" @click="removeService(service.id)">Remover</button>
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
            :items-length="services.length"
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

    <ErpModal v-model="viewOpen" title="Serviço" mode="view" :fields="serviceFields" :initial-values="modalInitial" />

    <ErpModal
      v-model="editOpen"
      title="Editar serviço"
      mode="edit"
      :fields="serviceFields"
      :initial-values="modalInitial"
      :loading="modalSaving"
      :error-text="modalError"
      submit-label="Salvar alterações"
      @submit="saveEdit"
    />
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'
import PaginationBar from '../components/PaginationBar.vue'
import ErpModal from '../components/ErpModal.vue'
import { formatCurrencyInput, parseCurrencyInput, formatMoneyBR } from '../utils/currency'
import { apiErrorMessage } from '../utils/apiMessage'

const serviceFields = [
  { key: 'name', label: 'Nome do serviço', type: 'text', fullWidth: true },
  { key: 'base_monthly_value', label: 'Valor base mensal', type: 'money' },
]

const services = ref([])
const form = ref({ name: '', base_monthly_value: 0, base_monthly_value_display: '0,00' })
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
const editOpen = ref(false)
const modalInitial = ref({})
const modalSaving = ref(false)
const modalError = ref('')
const editingId = ref(null)

const loadServices = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/services', { params: { page: page.value, per_page: perPage.value } })
    services.value = data.data ?? []
    lastPage.value = data.meta?.last_page ?? 1
    total.value = data.meta?.total ?? 0
  } catch {
    error.value = 'Não foi possível carregar os serviços.'
    services.value = []
  } finally {
    loading.value = false
  }
}

function onPageChange(p) {
  page.value = p
  loadServices()
}

function onPerPageChange(pp) {
  perPage.value = pp
  page.value = 1
  loadServices()
}

const onMoneyInput = (value) => {
  form.value.base_monthly_value_display = formatCurrencyInput(value)
  form.value.base_monthly_value = parseCurrencyInput(`R$ ${form.value.base_monthly_value_display}`)
}

async function fetchOne(id) {
  const { data } = await api.get(`/services/${id}`)
  return data.data
}

async function openView(id) {
  try {
    modalInitial.value = await fetchOne(id)
    viewOpen.value = true
  } catch {
    error.value = 'Não foi possível carregar o serviço.'
  }
}

async function openEdit(id) {
  editingId.value = id
  modalError.value = ''
  try {
    modalInitial.value = await fetchOne(id)
    editOpen.value = true
  } catch {
    error.value = 'Não foi possível carregar o serviço.'
  }
}

async function saveEdit(payload) {
  modalSaving.value = true
  modalError.value = ''
  try {
    await api.put(`/services/${editingId.value}`, payload)
    editOpen.value = false
    flashSuccess.value = 'Serviço atualizado.'
    window.setTimeout(() => {
      flashSuccess.value = ''
    }, 3000)
    await loadServices()
  } catch (err) {
    modalError.value = apiErrorMessage(err, 'Falha ao atualizar.')
  } finally {
    modalSaving.value = false
  }
}

const createService = async () => {
  saving.value = true
  error.value = ''
  try {
    await api.post('/services', { name: form.value.name, base_monthly_value: form.value.base_monthly_value })
    form.value = { name: '', base_monthly_value: 0, base_monthly_value_display: '0,00' }
    flashSuccess.value = 'Serviço criado.'
    window.setTimeout(() => {
      flashSuccess.value = ''
    }, 3000)
    page.value = 1
    await loadServices()
  } catch (err) {
    error.value = apiErrorMessage(err, 'Falha ao salvar serviço.')
  } finally {
    saving.value = false
  }
}

const removeService = async (id) => {
  saving.value = true
  error.value = ''
  try {
    await api.delete(`/services/${id}`)
    flashSuccess.value = 'Serviço removido.'
    window.setTimeout(() => {
      flashSuccess.value = ''
    }, 3000)
    await loadServices()
  } catch (err) {
    error.value = apiErrorMessage(err, 'Falha ao remover serviço.')
  } finally {
    saving.value = false
  }
}

onMounted(loadServices)
</script>

<style scoped>
.cols-2 {
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
}
.span-2-md {
  grid-column: 1 / -1;
}
@media (min-width: 720px) {
  .span-2-md {
    grid-column: span 2;
  }
}
.grid-services {
  grid-template-columns: 56px 1fr 140px minmax(200px, 1.2fr);
}
.cell-actions {
  justify-content: flex-end;
}
.flash-margin {
  margin-top: 1rem;
}
@media (max-width: 720px) {
  .grid-services {
    grid-template-columns: 1fr 1fr;
  }
  .grid-services .cell-actions {
    grid-column: 1 / -1;
    justify-content: flex-start;
  }
}
</style>
