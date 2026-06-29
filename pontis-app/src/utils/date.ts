export function formatDate(value?: string | null): string {
  if (!value) return '-'

  const isoDate = /^(\d{4})-(\d{2})-(\d{2})/.exec(value)
  if (isoDate) {
    return `${isoDate[3]}-${isoDate[2]}-${isoDate[1]}`
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value

  return [
    String(date.getDate()).padStart(2, '0'),
    String(date.getMonth() + 1).padStart(2, '0'),
    String(date.getFullYear()),
  ].join('-')
}

export function toDateInputValue(value?: string | null): string {
  if (!value) return ''

  const isoDate = /^(\d{4}-\d{2}-\d{2})/.exec(value)
  if (isoDate) return isoDate[1]

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''

  return [
    String(date.getFullYear()),
    String(date.getMonth() + 1).padStart(2, '0'),
    String(date.getDate()).padStart(2, '0'),
  ].join('-')
}
