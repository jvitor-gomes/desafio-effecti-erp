export function apiErrorMessage(err, fallback = 'Operação falhou.') {
  const data = err?.response?.data
  if (!data) return err?.message || fallback

  const errors = data.errors
  if (errors && typeof errors === 'object') {
    const parts = Object.entries(errors).flatMap(([k, v]) =>
      Array.isArray(v) ? v.map((m) => `${k}: ${m}`) : [`${k}: ${v}`]
    )
    if (parts.length) return parts.join(' ')
  }

  if (typeof data.message === 'string' && data.message.trim()) return data.message
  return fallback
}
