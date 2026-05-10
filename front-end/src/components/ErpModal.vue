<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="erp-modal-backdrop"
      role="presentation"
      @click.self="close"
    >
      <div
        class="erp-modal"
        :class="{ 'erp-modal--lg': size === 'lg' }"
        role="dialog"
        :aria-modal="true"
        :aria-labelledby="titleId"
      >
        <header class="erp-modal__header">
          <h3 :id="titleId" class="erp-modal__title">{{ title }}</h3>
          <button type="button" class="erp-modal__close erp-btn erp-btn--ghost erp-btn--sm" aria-label="Fechar" @click="close">
            ×
          </button>
        </header>

        <div class="erp-modal__body">
          <slot name="before-fields" />
          <div v-if="fields.length" class="erp-form__grid erp-modal__fields">
            <template v-for="f in fields" :key="f.key">
              <div class="erp-field" :class="{ 'span-full': f.fullWidth }">
                <label class="erp-label" :for="fieldDomId(f)">{{ f.label }}</label>

                <template v-if="mode === 'view' || f.readonly">
                  <div class="erp-readonly-value">{{ displayReadonly(f) }}</div>
                </template>

                <template v-else-if="f.type === 'select'">
                  <select :id="fieldDomId(f)" v-model="local[f.key]" class="erp-select">
                    <option v-if="f.placeholder" value="">{{ f.placeholder }}</option>
                    <option v-for="opt in f.options || []" :key="String(opt.value)" :value="opt.value">{{ opt.label }}</option>
                  </select>
                </template>

                <template v-else-if="f.type === 'textarea'">
                  <textarea :id="fieldDomId(f)" v-model="local[f.key]" class="erp-input erp-input--textarea" rows="3" />
                </template>

                <template v-else-if="f.type === 'money'">
                  <div class="erp-money">
                    <span class="erp-money__prefix">R$</span>
                    <input
                      :id="fieldDomId(f)"
                      :value="moneyDisp[f.key]"
                      class="erp-input"
                      type="text"
                      inputmode="decimal"
                      autocomplete="off"
                      @input="onMoneyInput(f.key, $event.target.value)"
                    />
                  </div>
                </template>

                <template v-else-if="f.type === 'document'">
                  <input
                    :id="fieldDomId(f)"
                    :value="docDisp[f.key]"
                    class="erp-input"
                    type="text"
                    inputmode="numeric"
                    autocomplete="off"
                    maxlength="18"
                    @input="onDocInput(f.key, $event.target.value)"
                  />
                </template>

                <template v-else>
                  <input
                    :id="fieldDomId(f)"
                    v-model="local[f.key]"
                    class="erp-input"
                    :type="f.type === 'number' ? 'number' : f.type === 'email' ? 'email' : f.type === 'date' ? 'date' : 'text'"
                    :min="f.min"
                    :step="f.step"
                    :placeholder="f.placeholder"
                    autocomplete="off"
                  />
                </template>
              </div>
            </template>
          </div>

          <slot />

          <p v-if="errorText" class="erp-alert erp-modal__alert" role="alert">{{ errorText }}</p>
        </div>

        <footer v-if="mode !== 'view'" class="erp-modal__footer">
          <slot name="footer-actions">
            <button type="button" class="erp-btn erp-btn--ghost" :disabled="loading" @click="close">Cancelar</button>
            <button type="button" class="erp-btn erp-btn--primary" :disabled="loading" @click="confirmSubmit">
              {{ loading ? 'Salvando…' : submitLabel }}
            </button>
          </slot>
        </footer>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import { formatCurrencyInput, parseCurrencyInput, formatMoneyBR } from '../utils/currency'
import { maskCpfCnpj, digitsOnlyDocument } from '../utils/documentMask'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: '' },
  mode: { type: String, default: 'edit' },
  fields: { type: Array, default: () => [] },
  initialValues: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
  errorText: { type: String, default: '' },
  submitLabel: { type: String, default: 'Salvar' },
  size: { type: String, default: 'md' },
})

const emit = defineEmits(['update:modelValue', 'submit'])

const titleId = `erp-modal-title-${Math.random().toString(36).slice(2, 9)}`
const local = ref({})
const moneyDisp = ref({})
const docDisp = ref({})

function fieldDomId(f) {
  return `${titleId}-${f.key}`
}

function resetFromInitial() {
  local.value = { ...(props.initialValues || {}) }
  moneyDisp.value = {}
  docDisp.value = {}
  for (const f of props.fields) {
    if (f.type === 'money') {
      const n = Number(local.value[f.key] ?? 0)
      moneyDisp.value[f.key] = formatCurrencyInput(String(Math.round(n * 100)))
      local.value[f.key] = n
    }
    if (f.type === 'document') {
      docDisp.value[f.key] = maskCpfCnpj(String(local.value[f.key] ?? ''))
    }
    if (f.type === 'select' && f.coerce === 'number' && local.value[f.key] != null && local.value[f.key] !== '') {
      local.value[f.key] = Number(local.value[f.key])
    }
  }
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) resetFromInitial()
  }
)

watch(
  () => props.initialValues,
  () => {
    if (props.modelValue) resetFromInitial()
  },
  { deep: true }
)

function onMoneyInput(key, raw) {
  moneyDisp.value[key] = formatCurrencyInput(raw)
  local.value[key] = parseCurrencyInput(`R$ ${moneyDisp.value[key]}`)
}

function onDocInput(key, raw) {
  docDisp.value[key] = maskCpfCnpj(raw)
}

function displayReadonly(f) {
  const v = local.value[f.key]
  if (v === null || v === undefined || v === '') return '—'
  if (f.type === 'money') return formatMoneyBR(Number(v))
  if (f.type === 'document') return maskCpfCnpj(String(v))
  if (f.type === 'select') {
    const opt = (f.options || []).find((o) => String(o.value) === String(v))
    return opt ? opt.label : String(v)
  }
  return String(v)
}

function buildPayload() {
  const out = {}
  for (const f of props.fields) {
    if (f.readonly || props.mode === 'view') continue
    if (!(f.key in local.value)) continue
    let v = local.value[f.key]
    if (f.type === 'document') {
      v = digitsOnlyDocument(docDisp.value[f.key] ?? '')
    }
    if (f.type === 'money') {
      v = parseCurrencyInput(`R$ ${moneyDisp.value[f.key] ?? '0,00'}`)
    }
    if (f.coerce === 'number') {
      v = v === '' || v == null ? null : Number(v)
    }
    out[f.key] = v
  }
  return out
}

function confirmSubmit() {
  emit('submit', buildPayload())
}

function close() {
  emit('update:modelValue', false)
}
</script>

<style scoped>
.span-full {
  grid-column: 1 / -1;
}
.erp-input--textarea {
  min-height: 5rem;
  resize: vertical;
}
.erp-modal__alert {
  margin-top: 1rem;
}
</style>
