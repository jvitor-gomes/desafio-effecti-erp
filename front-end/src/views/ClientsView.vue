<template>
  <section class="erp-page" :aria-busy="loading">
    <div class="erp-page__intro">
      <h2 class="erp-page__title">Clientes</h2>
      <p class="erp-page__lead">
        CRUD completo: visualização, edição em modal e remoção (bloqueada se houver contratos). Documento com máscara CPF/CNPJ.
      </p>
    </div>

    <div class="erp-toolbar">
      <span />
      <div class="erp-toolbar__actions">
        <button type="button" class="erp-btn erp-btn--secondary" :disabled="loading" @click="loadClients">Atualizar lista</button>
      </div>
    </div>

    <div class="erp-card">
      <div class="erp-card__header erp-card__header--plain">
        <h3 class="erp-card__title">Incluir cliente</h3>
        <p class="erp-card__desc">Campos obrigatórios conforme política do sistema.</p>
      </div>
      <div class="erp-card__body">
        <form class="erp-form" @submit.prevent="createClient">
          <div class="erp-form__grid">
            <div class="erp-field">
              <label class="erp-label" for="client-name">Nome completo</label>
              <input id="client-name" v-model="form.name" class="erp-input" required autocomplete="organization" />
            </div>
            <div class="erp-field">
              <label class="erp-label" for="client-document">CPF / CNPJ</label>
              <input
                id="client-document"
                :value="form.document"
                class="erp-input"
                type="text"
                inputmode="numeric"
                autocomplete="off"
                maxlength="18"
                placeholder="000.000.000-00"
                required
                @input="onDocumentInput"
              />
            </div>
            <div class="erp-field">
              <label class="erp-label" for="client-email">E-mail</label>
              <input id="client-email" v-model="form.email" class="erp-input" type="email" required autocomplete="email" />
            </div>
            <div class="erp-field">
              <label class="erp-label" for="client-status">Status</label>
              <select id="client-status" v-model="form.status" class="erp-select">
                <option value="A">Ativo</option>
                <option value="I">Inativo</option>
              </select>
            </div>
          </div>
          <div class="erp-form__actions">
            <button type="submit" class="erp-btn erp-btn--primary" :disabled="saving">{{ saving ? 'Salvando…' : 'Salvar cliente' }}</button>
          </div>
        </form>
      </div>
    </div>

    <div class="erp-card erp-card__body--flush">
      <div class="erp-card__header">
        <h3 class="erp-card__title">Registros</h3>
        <p class="erp-card__desc">Listagem paginada — origem API.</p>
      </div>

      <p v-if="loading" class="erp-state" role="status">Carregando clientes…</p>

      <template v-else>
        <div v-if="!clients.length" class="erp-state erp-state--empty" role="status">Nenhum cliente nesta página.</div>
        <div v-else class="erp-table-scroll">
          <div class="erp-data-table">
            <div class="erp-data-table__head grid-clients">
              <span>ID</span><span>Nome</span><span>Documento</span><span>Status</span><span class="cell-actions">Ações</span>
            </div>
            <div v-for="client in clients" :key="client.id" class="erp-data-table__row grid-clients">
              <span class="erp-data-table__cell--muted">{{ client.id }}</span>
              <span>{{ client.name }}</span>
              <span class="erp-data-table__cell--muted">{{ client.document }}</span>
              <span>
                <span :class="client.status === 'A' ? 'erp-chip erp-chip--ok' : 'erp-chip erp-chip--off'">
                  {{ client.status === 'A' ? 'Ativo' : 'Inativo' }}
                </span>
              </span>
              <span class="cell-actions erp-stack">
                <button type="button" class="erp-btn erp-btn--ghost erp-btn--sm" :disabled="saving" @click="openView(client.id)">Ver</button>
                <button type="button" class="erp-btn erp-btn--secondary erp-btn--sm" :disabled="saving" @click="openEdit(client.id)">Editar</button>
                <button type="button" class="erp-btn erp-btn--danger erp-btn--sm" :disabled="saving" @click="removeClient(client.id)">Remover</button>
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
            :items-length="clients.length"
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

    <ErpModal
      v-model="viewOpen"
      title="Cliente"
      mode="view"
      :fields="clientFields"
      :initial-values="modalInitial"
    />

    <ErpModal
      v-model="editOpen"
      title="Editar cliente"
      mode="edit"
      :fields="clientFields"
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
import { maskCpfCnpj, digitsOnlyDocument } from '../utils/documentMask'
import { apiErrorMessage } from '../utils/apiMessage'

const clientFields = [
  { key: 'name', label: 'Nome completo', type: 'text' },
  { key: 'document', label: 'CPF / CNPJ', type: 'document' },
  { key: 'email', label: 'E-mail', type: 'email' },
  {
    key: 'status',
    label: 'Status',
    type: 'select',
    options: [
      { value: 'A', label: 'Ativo' },
      { value: 'I', label: 'Inativo' },
    ],
  },
]

const clients = ref([])
const form = ref({ name: '', document: '', email: '', status: 'A' })
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

const loadClients = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/clients', { params: { page: page.value, per_page: perPage.value } })
    clients.value = data.data ?? []
    lastPage.value = data.meta?.last_page ?? 1
    total.value = data.meta?.total ?? 0
  } catch {
    error.value = 'Não foi possível carregar os clientes. Verifique a API e tente de novo.'
    clients.value = []
  } finally {
    loading.value = false
  }
}

function onPageChange(p) {
  page.value = p
  loadClients()
}

function onPerPageChange(pp) {
  perPage.value = pp
  page.value = 1
  loadClients()
}

function onDocumentInput(e) {
  form.value.document = maskCpfCnpj(e.target.value)
}

async function fetchOne(id) {
  const { data } = await api.get(`/clients/${id}`)
  return data.data
}

async function openView(id) {
  modalError.value = ''
  try {
    modalInitial.value = await fetchOne(id)
    viewOpen.value = true
  } catch {
    error.value = 'Não foi possível carregar o cliente.'
  }
}

async function openEdit(id) {
  editingId.value = id
  modalError.value = ''
  modalSaving.value = false
  try {
    modalInitial.value = await fetchOne(id)
    editOpen.value = true
  } catch {
    error.value = 'Não foi possível carregar o cliente.'
  }
}

async function saveEdit(payload) {
  modalSaving.value = true
  modalError.value = ''
  try {
    await api.put(`/clients/${editingId.value}`, payload)
    editOpen.value = false
    flashSuccess.value = 'Cliente atualizado.'
    window.setTimeout(() => {
      flashSuccess.value = ''
    }, 3000)
    await loadClients()
  } catch (err) {
    modalError.value = apiErrorMessage(err, 'Falha ao atualizar.')
  } finally {
    modalSaving.value = false
  }
}

const createClient = async () => {
  saving.value = true
  error.value = ''
  try {
    await api.post('/clients', {
      name: form.value.name,
      document: digitsOnlyDocument(form.value.document),
      email: form.value.email,
      status: form.value.status,
    })
    form.value = { name: '', document: '', email: '', status: 'A' }
    flashSuccess.value = 'Cliente criado.'
    window.setTimeout(() => {
      flashSuccess.value = ''
    }, 3000)
    page.value = 1
    await loadClients()
  } catch (err) {
    error.value = apiErrorMessage(err, 'Falha ao salvar cliente.')
  } finally {
    saving.value = false
  }
}

const removeClient = async (id) => {
  saving.value = true
  error.value = ''
  try {
    await api.delete(`/clients/${id}`)
    flashSuccess.value = 'Cliente removido.'
    window.setTimeout(() => {
      flashSuccess.value = ''
    }, 3000)
    await loadClients()
  } catch (err) {
    error.value = apiErrorMessage(err, 'Falha ao remover cliente.')
  } finally {
    saving.value = false
  }
}

onMounted(loadClients)
</script>

<style scoped>
.grid-clients {
  grid-template-columns: 56px minmax(120px, 1.4fr) minmax(100px, 1fr) 100px minmax(200px, 1.4fr);
}
.cell-actions {
  justify-content: flex-end;
}
.flash-margin {
  margin-top: 1rem;
}
@media (max-width: 960px) {
  .grid-clients {
    grid-template-columns: 44px 1fr;
  }
  .grid-clients .cell-actions {
    grid-column: 1 / -1;
    justify-content: flex-start;
  }
}
</style>
