// Extrae el mensaje de error de una respuesta de API, con un fallback.
export function apiError(err: unknown, fallback: string): string {
  return (err as { response?: { data?: { message?: string } } })?.response?.data?.message ?? fallback
}
