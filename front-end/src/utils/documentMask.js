function onlyDigits(str) {
  return String(str ?? '').replace(/\D/g, '').slice(0, 14)
}

function formatCpfPartial(d) {
  const len = d.length
  if (len === 0) return ''
  if (len <= 3) return d
  if (len <= 6) return `${d.slice(0, 3)}.${d.slice(3)}`
  if (len <= 9) return `${d.slice(0, 3)}.${d.slice(3, 6)}.${d.slice(6)}`
  return `${d.slice(0, 3)}.${d.slice(3, 6)}.${d.slice(6, 9)}-${d.slice(9, 11)}`
}

function formatCnpjPartial(d) {
  const len = d.length
  if (len === 0) return ''
  if (len <= 2) return d
  if (len <= 5) return `${d.slice(0, 2)}.${d.slice(2)}`
  if (len <= 8) return `${d.slice(0, 2)}.${d.slice(2, 5)}.${d.slice(5)}`
  if (len <= 12) return `${d.slice(0, 2)}.${d.slice(2, 5)}.${d.slice(5, 8)}/${d.slice(8)}`
  return `${d.slice(0, 2)}.${d.slice(2, 5)}.${d.slice(5, 8)}/${d.slice(8, 12)}-${d.slice(12, 14)}`
}

export function maskCpfCnpj(raw) {
  const digits = onlyDigits(raw)
  if (digits.length <= 11) {
    return formatCpfPartial(digits)
  }
  return formatCnpjPartial(digits)
}

export function digitsOnlyDocument(raw) {
  return onlyDigits(raw)
}
