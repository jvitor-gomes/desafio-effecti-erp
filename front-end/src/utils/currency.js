export function formatCurrencyInput(value) {
  const digits = String(value ?? '').replace(/\D/g, '')
  const numeric = Number(digits || 0) / 100
  return numeric.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

export function parseCurrencyInput(value) {
  let s = String(value ?? '')
    .replace(/R\$\s?/gi, '')
    .trim()
  s = s.replace(/\./g, '').replace(',', '.')
  const numeric = Number(s)
  return Number.isNaN(numeric) ? 0 : numeric
}

export function formatMoneyBR(amount) {
  const n = Number(amount ?? 0)
  return n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}
