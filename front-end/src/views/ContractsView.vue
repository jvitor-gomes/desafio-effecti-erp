<template>
  <section class="erp-page" :aria-busy="loading">
    <div class="erp-page__intro">
      <h2 class="erp-page__title">Contratos</h2>
      <p class="erp-page__lead">
        CRUD de contrato com <strong>múltiplos itens</strong> na criação; gestão de itens (incluir, editar, remover) e datas no modal.
        Total mensal com desconto por quantidade — cancelados não aplicam desconto.
      </p>
    </div>

    <div class="erp-toolbar">
      <span />
      <div class="erp-toolbar__actions">
        <button type="button" class="erp-btn erp-btn--secondary" :disabled="loading || lookupsLoading" @click="refreshAll">Atualizar</button>
      </div>
    </div>

    <div class="erp-card">
      <div class="erp-card__header erp-card__header--plain">
        <h3 class="erp-card__title">Novo contrato</h3>
        <p class="erp-card__desc">Cliente ativo obrigatório; pelo menos um item (serviço, quantidade, valor unitário).</p>
      </div>
      <div class="erp-card__body">
        <form class="erp-form" @submit.prevent="createContract">
          <div class="erp-form__grid cols-contract-head">
            <div class="erp-field">
              <label class="erp-label" for="contract-client">Cliente</label>
              <select id="contract-client" v-model.number="form.client_id" class="erp-select" required>
                <option disabled :value="0">Selecione…</option>
                <option v-for="c in activeClients" :key="c.id" :value="c.id">{{ c.name }} (#{{ c.id }})</option>
              </select>
            </div>
            <div class="erp-field">
              <label class="erp-label" for="contract-start">Data de início</label>
              <input id="contract-start" v-model="form.start_date" class="erp-input" type="date" required />
            </div>
            <div class="erp-field">
              <label class="erp-label" for="contract-end">Data de término</label>
              <input id="contract-end" v-model="form.end_date" class="erp-input" type="date" />
              <span class="erp-hint">Opcional.</span>
            </div>
          </div>

          <div class="erp-card erp-card--sub block-mt-sm">
            <div class="erp-card__header erp-card__header--plain erp-subcard-toolbar">
              <h4 class="erp-card__title erp-card__title--natural">Itens do contrato</h4>
              <button type="button" class="erp-btn erp-btn--secondary erp-btn--sm" :disabled="!services.length" @click="addItemRow">+ Linha</button>
            </div>
            <div class="erp-card__body">
              <div v-for="(row, idx) in itemRows" :key="idx" class="item-row erp-form__grid erp-form__grid--contract-line">
                <div class="erp-field">
                  <label class="erp-label" :for="'svc-' + idx">Serviço</label>
                  <select
                    :id="'svc-' + idx"
                    v-model.number="row.service_id"
                    class="erp-select"
                    required
                    @change="syncRowPrice(idx)"
                  >
                    <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
                  </select>
                </div>
                <div class="erp-field">
                  <label class="erp-label" :for="'qty-' + idx">Quantidade</label>
                  <input :id="'qty-' + idx" v-model.number="row.quantity" class="erp-input" type="number" min="1" required />
                </div>
                <div class="erp-field">
                  <label class="erp-label" :for="'uv-' + idx">Valor unitário</label>
                  <div class="erp-money">
                    <span class="erp-money__prefix">R$</span>
                    <input
                      :id="'uv-' + idx"
                      :value="row.unit_value_display"
                      class="erp-input"
                      type="text"
                      inputmode="decimal"
                      autocomplete="off"
                      required
                      @input="onRowMoney(idx, $event.target.value)"
                    />
                  </div>
                </div>
                <div class="erp-field erp-field--action">
                  <span class="erp-label erp-label--spacer" aria-hidden="true">&nbsp;</span>
                  <div class="erp-field__anchor">
                    <button
                      v-if="itemRows.length > 1"
                      type="button"
                      class="erp-btn erp-btn--ghost erp-btn--sm"
                      title="Remover linha"
                      @click="removeItemRow(idx)"
                    >
                      ✕
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="erp-form__actions">
            <button
              type="submit"
              class="erp-btn erp-btn--primary"
              :disabled="saving || lookupsLoading || !services.length || !activeClients.length"
            >
              {{ saving ? 'Criando…' : 'Criar contrato' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="erp-card erp-card__body--flush">
      <div class="erp-card__header">
        <h3 class="erp-card__title">Contratos</h3>
      </div>

      <p v-if="loading" class="erp-state" role="status">Carregando contratos…</p>

      <template v-else>
        <div v-if="!contracts.length" class="erp-state erp-state--empty" role="status">Nenhum contrato nesta página.</div>
        <div v-else class="erp-table-scroll">
          <div class="erp-data-table">
            <div class="erp-data-table__head grid-contracts">
              <span>ID</span><span>Cliente</span><span>Início</span><span>Status</span><span>Total</span><span class="cell-actions">Ações</span>
            </div>
            <div v-for="contract in contracts" :key="contract.id" class="erp-data-table__row grid-contracts">
              <span class="erp-data-table__cell--muted">{{ contract.id }}</span>
              <span>{{ contract.client?.name || '—' }}</span>
              <span class="erp-data-table__cell--muted">{{ formatDate(contract.start_date) }}</span>
              <span>
                <span :class="contract.status === 'A' ? 'erp-chip erp-chip--ok' : 'erp-chip erp-chip--off'">
                  {{ contract.status === 'A' ? 'Ativo' : 'Cancelado' }}
                </span>
              </span>
              <span class="erp-data-table__cell--strong">{{ formatMoneyBR(contract.calculation?.total || 0) }}</span>
              <span class="cell-actions erp-stack">
                <button type="button" class="erp-btn erp-btn--ghost erp-btn--sm" :disabled="saving" @click="openManage(contract.id)">Detalhes</button>
                <button type="button" class="erp-btn erp-btn--warn erp-btn--sm" :disabled="saving || contract.status === 'C'" @click="cancelContract(contract.id)">
                  Cancelar
                </button>
                <button type="button" class="erp-btn erp-btn--danger erp-btn--sm" :disabled="saving" @click="removeContract(contract.id)">Excluir</button>
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
            :items-length="contracts.length"
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

    <ContractManageModal v-model="manageOpen" :contract-id="manageContractId" :services="services" @updated="loadContracts" />
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '../services/api'
import PaginationBar from '../components/PaginationBar.vue'
import ContractManageModal from '../components/ContractManageModal.vue'
import { formatCurrencyInput, parseCurrencyInput, formatMoneyBR } from '../utils/currency'
import { apiErrorMessage } from '../utils/apiMessage'

const contracts = ref([])
const clients = ref([])
const services = ref([])
const lookupsLoading = ref(true)

const form = ref({ client_id: 0, start_date: '', end_date: '' })

const itemRows = ref([])

const error = ref('')
const flashSuccess = ref('')
const loading = ref(true)
const saving = ref(false)
const page = ref(1)
const perPage = ref(10)
const lastPage = ref(1)
const total = ref(0)
const perPageOptions = [10, 20, 50]

const manageOpen = ref(false)
const manageContractId = ref(null)

const activeClients = computed(() => clients.value.filter((c) => c.status === 'A'))

function formatDate(d) {
  if (!d) return '—'
  return String(d).slice(0, 10)
}

function emptyRow() {
  const s = services.value[0]
  const base = Number(s?.base_monthly_value ?? 0)
  return {
    service_id: s?.id ?? 0,
    quantity: 1,
    unit_value: base,
    unit_value_display: formatCurrencyInput(String(Math.round(base * 100))),
  }
}

function resetCreateForm() {
  form.value = {
    client_id: activeClients.value[0]?.id ?? 0,
    start_date: '',
    end_date: '',
  }
  itemRows.value = services.value.length ? [emptyRow()] : []
}

async function loadLookups() {
  lookupsLoading.value = true
  try {
    const [ac, asv] = await Promise.all([
      api.get('/clients', { params: { page: 1, per_page: 500 } }),
      api.get('/services', { params: { page: 1, per_page: 500 } }),
    ])
    clients.value = ac.data.data ?? []
    services.value = asv.data.data ?? []
    resetCreateForm()
  } catch {
    error.value = 'Não foi possível carregar clientes/serviços para os formulários.'
  } finally {
    lookupsLoading.value = false
  }
}

const loadContracts = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/contracts', { params: { page: page.value, per_page: perPage.value } })
    contracts.value = data.data ?? []
    lastPage.value = data.meta?.last_page ?? 1
    total.value = data.meta?.total ?? 0
  } catch {
    error.value = 'Não foi possível carregar os contratos.'
    contracts.value = []
  } finally {
    loading.value = false
  }
}

async function refreshAll() {
  await Promise.all([loadLookups(), loadContracts()])
}

function onPageChange(p) {
  page.value = p
  loadContracts()
}

function onPerPageChange(pp) {
  perPage.value = pp
  page.value = 1
  loadContracts()
}

function addItemRow() {
  itemRows.value.push(emptyRow())
}

function removeItemRow(idx) {
  if (itemRows.value.length <= 1) return
  itemRows.value.splice(idx, 1)
}

function syncRowPrice(idx) {
  const row = itemRows.value[idx]
  const s = services.value.find((x) => x.id === row.service_id)
  if (!s) return
  const base = Number(s.base_monthly_value)
  row.unit_value = base
  row.unit_value_display = formatCurrencyInput(String(Math.round(base * 100)))
}

function onRowMoney(idx, raw) {
  const row = itemRows.value[idx]
  row.unit_value_display = formatCurrencyInput(raw)
  row.unit_value = parseCurrencyInput(`R$ ${row.unit_value_display}`)
}

const createContract = async () => {
  saving.value = true
  error.value = ''
  try {
    const payload = {
      client_id: form.value.client_id,
      start_date: form.value.start_date,
      end_date: form.value.end_date || null,
      items: itemRows.value.map((r) => ({
        service_id: r.service_id,
        quantity: r.quantity,
        unit_value: r.unit_value,
      })),
    }
    await api.post('/contracts', payload)
    flashSuccess.value = 'Contrato criado.'
    window.setTimeout(() => {
      flashSuccess.value = ''
    }, 3000)
    page.value = 1
    resetCreateForm()
    await loadContracts()
  } catch (err) {
    error.value = apiErrorMessage(err, 'Falha ao criar contrato.')
  } finally {
    saving.value = false
  }
}

function openManage(id) {
  manageContractId.value = id
  manageOpen.value = true
}

const cancelContract = async (id) => {
  saving.value = true
  error.value = ''
  try {
    await api.patch(`/contracts/${id}/cancel`)
    flashSuccess.value = 'Contrato cancelado.'
    window.setTimeout(() => {
      flashSuccess.value = ''
    }, 3000)
    await loadContracts()
  } catch (err) {
    error.value = apiErrorMessage(err, 'Falha ao cancelar contrato.')
  } finally {
    saving.value = false
  }
}

const removeContract = async (id) => {
  saving.value = true
  error.value = ''
  try {
    await api.delete(`/contracts/${id}`)
    flashSuccess.value = 'Contrato excluído.'
    window.setTimeout(() => {
      flashSuccess.value = ''
    }, 3000)
    await loadContracts()
  } catch (err) {
    error.value = apiErrorMessage(err, 'Falha ao excluir contrato.')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await loadLookups()
  await loadContracts()
})
</script>

<style scoped>
.cols-contract-head {
  grid-template-columns: minmax(0, 2fr) repeat(2, minmax(10rem, 1fr));
}

@media (max-width: 720px) {
  .cols-contract-head {
    grid-template-columns: 1fr;
  }
}

.block-mt-sm {
  margin-top: 1rem;
}

.item-row {
  padding-bottom: 1rem;
  margin-bottom: 1rem;
  border-bottom: 1px dashed var(--erp-border);
}

.item-row:last-child {
  border-bottom: 0;
  margin-bottom: 0;
  padding-bottom: 0;
}

.grid-contracts {
  grid-template-columns: 44px minmax(100px, 1.2fr) 92px 88px minmax(72px, 0.9fr) minmax(200px, 1.4fr);
}

.cell-actions {
  justify-content: flex-end;
}

.flash-margin {
  margin-top: 1rem;
}

@media (max-width: 1100px) {
  .grid-contracts {
    grid-template-columns: 40px 1fr 1fr;
  }

  .grid-contracts .cell-actions {
    grid-column: 1 / -1;
    justify-content: flex-start;
  }
}
</style>
