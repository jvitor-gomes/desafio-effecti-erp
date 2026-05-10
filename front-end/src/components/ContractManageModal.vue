<template>
  <ErpModal v-model="openProxy" :title="titleText" mode="view" :fields="[]" size="lg">
    <div v-if="loadingContract" class="erp-state" role="status">Carregando contrato…</div>

    <template v-else-if="contract">
      <div class="contract-summary erp-form__grid">
        <div class="erp-field">
          <span class="erp-label">Cliente</span>
          <div class="erp-readonly-value">{{ contract.client?.name || '—' }} <span class="muted">#{{ contract.client_id }}</span></div>
        </div>
        <div class="erp-field">
          <span class="erp-label">Status</span>
          <div>
            <span :class="contract.status === 'A' ? 'erp-chip erp-chip--ok' : 'erp-chip erp-chip--off'">
              {{ contract.status === 'A' ? 'Ativo' : 'Cancelado' }}
            </span>
          </div>
        </div>
        <div class="erp-field">
          <span class="erp-label">Subtotal</span>
          <div class="erp-readonly-value">{{ formatMoneyBR(contract.calculation?.subtotal ?? 0) }}</div>
        </div>
        <div class="erp-field">
          <span class="erp-label">Desconto</span>
          <div class="erp-readonly-value">{{ formatMoneyBR(contract.calculation?.discount ?? 0) }}</div>
        </div>
        <div class="erp-field">
          <span class="erp-label">Total mensal</span>
          <div class="erp-readonly-value strong">{{ formatMoneyBR(contract.calculation?.total ?? 0) }}</div>
        </div>
      </div>

      <div v-if="contract.status === 'A'" class="erp-card erp-card--sub block-mt">
        <div class="erp-card__header erp-card__header--plain">
          <h4 class="erp-card__title erp-card__title--natural">Datas do contrato</h4>
        </div>
        <div class="erp-card__body">
          <div class="erp-form__grid cols-dates">
            <div class="erp-field">
              <label class="erp-label" for="cm-start">Início</label>
              <input id="cm-start" v-model="header.start_date" class="erp-input" type="date" />
            </div>
            <div class="erp-field">
              <label class="erp-label" for="cm-end">Término (opcional)</label>
              <input id="cm-end" v-model="header.end_date" class="erp-input" type="date" />
            </div>
          </div>
          <div class="erp-form__actions erp-form__actions--flat">
            <button type="button" class="erp-btn erp-btn--primary erp-btn--sm" :disabled="savingHeader" @click="saveHeader">
              {{ savingHeader ? 'Salvando…' : 'Salvar datas' }}
            </button>
          </div>
        </div>
      </div>

      <div class="block-mt">
        <h4 class="block-title">Itens do contrato</h4>
        <div v-if="!contract.items?.length" class="erp-state erp-state--empty">Nenhum item.</div>
        <div v-else class="erp-table-scroll">
          <div class="erp-data-table">
            <div class="erp-data-table__head grid-items">
              <span>Serviço</span><span>Qtd</span><span>V. unit.</span><span>Linha</span><span class="cell-actions">Ações</span>
            </div>
            <div v-for="row in contract.items" :key="row.id" class="erp-data-table__row grid-items">
              <span>{{ row.service?.name || `Serviço #${row.service_id}` }}</span>
              <span class="erp-data-table__cell--muted">{{ row.quantity }}</span>
              <span class="erp-data-table__cell--muted">{{ formatMoneyBR(row.unit_value) }}</span>
              <span class="erp-data-table__cell--strong">{{ formatMoneyBR(lineTotal(row)) }}</span>
              <span v-if="contract.status === 'A'" class="cell-actions erp-stack">
                <button type="button" class="erp-btn erp-btn--secondary erp-btn--sm" :disabled="busyItem" @click="openEditItem(row)">Editar</button>
                <button type="button" class="erp-btn erp-btn--danger erp-btn--sm" :disabled="busyItem" @click="removeItem(row.id)">Remover</button>
              </span>
              <span v-else class="cell-actions muted">—</span>
            </div>
          </div>
        </div>
      </div>

      <div v-if="contract.status === 'A'" class="erp-card erp-card--sub block-mt">
        <div class="erp-card__header erp-card__header--plain">
          <h4 class="erp-card__title erp-card__title--natural">Adicionar item</h4>
        </div>
        <div class="erp-card__body">
          <div class="erp-form__grid cols-add">
            <div class="erp-field span-svc">
              <label class="erp-label" for="cm-svc">Serviço</label>
              <select id="cm-svc" v-model.number="newItem.service_id" class="erp-select">
                <option v-if="!services.length" disabled :value="0">Carregue serviços na página</option>
                <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }} — {{ formatMoneyBR(s.base_monthly_value) }}</option>
              </select>
            </div>
            <div class="erp-field">
              <label class="erp-label" for="cm-qty">Quantidade</label>
              <input id="cm-qty" v-model.number="newItem.quantity" class="erp-input" type="number" min="1" />
            </div>
            <div class="erp-field">
              <label class="erp-label" for="cm-unit">Valor unitário</label>
              <div class="erp-money">
                <span class="erp-money__prefix">R$</span>
                <input
                  id="cm-unit"
                  :value="newItem.unit_display"
                  class="erp-input"
                  type="text"
                  inputmode="decimal"
                  autocomplete="off"
                  @input="onNewItemMoney($event.target.value)"
                />
              </div>
            </div>
          </div>
          <button type="button" class="erp-btn erp-btn--primary erp-btn--sm block-mt-sm" :disabled="busyItem || !canAddItem" @click="addItem">
            {{ busyItem ? 'Aguarde…' : 'Incluir item' }}
          </button>
        </div>
      </div>

      <p v-if="innerError" class="erp-alert block-mt" role="alert">{{ innerError }}</p>
      <p v-if="innerOk" class="erp-alert erp-alert--success block-mt" role="status">{{ innerOk }}</p>
    </template>

    <p v-else-if="innerError" class="erp-alert" role="alert">{{ innerError }}</p>
  </ErpModal>

  <ErpModal
    v-model="itemModalOpen"
    title="Editar item"
    :fields="itemFields"
    :initial-values="itemModalInitial"
    :loading="savingItemModal"
    :error-text="itemModalError"
    submit-label="Salvar item"
    @submit="submitEditItem"
  />
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import api from '../services/api'
import ErpModal from './ErpModal.vue'
import { apiErrorMessage } from '../utils/apiMessage'
import { formatCurrencyInput, parseCurrencyInput, formatMoneyBR } from '../utils/currency'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  contractId: { type: Number, default: null },
  services: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue', 'updated'])

const openProxy = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

const contract = ref(null)
const loadingContract = ref(false)
const innerError = ref('')
const innerOk = ref('')
const savingHeader = ref(false)
const busyItem = ref(false)

const header = ref({ start_date: '', end_date: '' })

const newItem = ref({
  service_id: 0,
  quantity: 1,
  unit_value: 0,
  unit_display: '0,00',
})

const titleText = computed(() =>
  contract.value ? `Contrato #${contract.value.id}` : props.contractId ? `Contrato #${props.contractId}` : 'Contrato'
)

function lineTotal(row) {
  return Number(row.quantity ?? 0) * Number(row.unit_value ?? 0)
}

const canAddItem = computed(() => newItem.value.service_id > 0 && newItem.value.quantity >= 1 && props.services.length > 0)

async function fetchContract() {
  if (!props.contractId) return
  loadingContract.value = true
  innerError.value = ''
  innerOk.value = ''
  contract.value = null
  try {
    const { data } = await api.get(`/contracts/${props.contractId}`)
    contract.value = data.data
    header.value = {
      start_date: contract.value.start_date?.slice(0, 10) ?? '',
      end_date: contract.value.end_date?.slice(0, 10) ?? '',
    }
    syncNewItemDefault()
  } catch {
    innerError.value = 'Não foi possível carregar o contrato.'
  } finally {
    loadingContract.value = false
  }
}

function syncNewItemDefault() {
  const first = props.services[0]
  newItem.value = {
    service_id: first?.id ?? 0,
    quantity: 1,
    unit_value: Number(first?.base_monthly_value ?? 0),
    unit_display: formatCurrencyInput(String(Math.round(Number(first?.base_monthly_value ?? 0) * 100))),
  }
}

watch(
  () => props.modelValue,
  (v) => {
    if (v && props.contractId) fetchContract()
  }
)

watch(
  () => props.contractId,
  () => {
    if (props.modelValue && props.contractId) fetchContract()
  }
)

watch(
  () => newItem.value.service_id,
  (sid) => {
    const s = props.services.find((x) => x.id === sid)
    if (!s) return
    newItem.value.unit_value = Number(s.base_monthly_value)
    newItem.value.unit_display = formatCurrencyInput(String(Math.round(Number(s.base_monthly_value) * 100)))
  }
)

function onNewItemMoney(raw) {
  newItem.value.unit_display = formatCurrencyInput(raw)
  newItem.value.unit_value = parseCurrencyInput(`R$ ${newItem.value.unit_display}`)
}

async function saveHeader() {
  savingHeader.value = true
  innerError.value = ''
  innerOk.value = ''
  try {
    const payload = {
      start_date: header.value.start_date,
      end_date: header.value.end_date || null,
    }
    const { data } = await api.put(`/contracts/${props.contractId}`, payload)
    contract.value = data.data
    innerOk.value = 'Datas atualizadas.'
    emit('updated')
    window.setTimeout(() => {
      innerOk.value = ''
    }, 2500)
  } catch (err) {
    innerError.value = apiErrorMessage(err, 'Falha ao salvar datas.')
  } finally {
    savingHeader.value = false
  }
}

async function addItem() {
  busyItem.value = true
  innerError.value = ''
  innerOk.value = ''
  try {
    const { data } = await api.post(`/contracts/${props.contractId}/items`, {
      service_id: newItem.value.service_id,
      quantity: newItem.value.quantity,
      unit_value: newItem.value.unit_value,
    })
    contract.value = data.data
    syncNewItemDefault()
    innerOk.value = 'Item adicionado.'
    emit('updated')
    window.setTimeout(() => {
      innerOk.value = ''
    }, 2000)
  } catch (err) {
    innerError.value = apiErrorMessage(err, 'Falha ao adicionar item.')
  } finally {
    busyItem.value = false
  }
}

async function removeItem(itemId) {
  if (!confirm('Remover este item do contrato?')) return
  busyItem.value = true
  innerError.value = ''
  try {
    const { data } = await api.delete(`/contracts/${props.contractId}/items/${itemId}`)
    contract.value = data.data
    innerOk.value = 'Item removido.'
    emit('updated')
    window.setTimeout(() => {
      innerOk.value = ''
    }, 2000)
  } catch (err) {
    innerError.value = apiErrorMessage(err, 'Falha ao remover.')
  } finally {
    busyItem.value = false
  }
}

const itemModalOpen = ref(false)
const editingItemId = ref(null)
const savingItemModal = ref(false)
const itemModalError = ref('')
const itemModalInitial = ref({ quantity: 1, unit_value: 0 })

const itemFields = [
  { key: 'quantity', label: 'Quantidade', type: 'number', min: 1 },
  { key: 'unit_value', label: 'Valor unitário', type: 'money' },
]

function openEditItem(row) {
  editingItemId.value = row.id
  itemModalInitial.value = {
    quantity: row.quantity,
    unit_value: Number(row.unit_value),
  }
  itemModalError.value = ''
  itemModalOpen.value = true
}

async function submitEditItem(payload) {
  savingItemModal.value = true
  itemModalError.value = ''
  try {
    const body = {
      quantity: Number(payload.quantity),
      unit_value: payload.unit_value,
    }
    const { data } = await api.put(`/contracts/${props.contractId}/items/${editingItemId.value}`, body)
    contract.value = data.data
    itemModalOpen.value = false
    innerOk.value = 'Item atualizado.'
    emit('updated')
    window.setTimeout(() => {
      innerOk.value = ''
    }, 2000)
  } catch (err) {
    itemModalError.value = apiErrorMessage(err, 'Falha ao atualizar item.')
  } finally {
    savingItemModal.value = false
  }
}
</script>

<style scoped>
.contract-summary {
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
}
.muted {
  color: var(--erp-text-muted);
  font-weight: 400;
}
.strong {
  font-weight: 700;
}
.block-mt {
  margin-top: 1.25rem;
}
.block-mt-sm {
  margin-top: 0.75rem;
}
.block-title {
  margin: 0 0 0.65rem;
  font-size: 0.85rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--erp-text-secondary);
}
.cols-dates {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}
.cols-add {
  grid-template-columns: minmax(0, 1fr) minmax(6rem, 8rem) minmax(9rem, 12rem);
}
.erp-form__actions--flat {
  border: 0;
  margin-top: 0.5rem;
  padding-top: 0;
  padding-bottom: 0;
}
@media (max-width: 720px) {
  .cols-add {
    grid-template-columns: 1fr;
  }
  .span-svc {
    grid-column: 1 / -1;
  }
}
.grid-items {
  grid-template-columns: minmax(100px, 1.6fr) 52px 88px 88px minmax(140px, 1fr);
}
.cell-actions {
  justify-content: flex-end;
}
@media (max-width: 900px) {
  .grid-items {
    grid-template-columns: 1fr 1fr;
  }
  .grid-items .cell-actions {
    grid-column: 1 / -1;
    justify-content: flex-start;
  }
}
</style>
