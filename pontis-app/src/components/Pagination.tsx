import './Pagination.css'

interface PerPageOption {
  value: number
  label: string
}

interface PaginationProps {
  currentPage: number
  lastPage: number
  total: number
  onPageChange: (page: number) => void
  /** Cuando se pasan `perPage` y `onPerPageChange` se muestra el pie con selector
   *  de cantidad a la izquierda y números de página clickeables a la derecha. */
  perPage?: number
  perPageOptions?: PerPageOption[]
  onPerPageChange?: (perPage: number) => void
}

const DEFAULT_PER_PAGE_OPTIONS: PerPageOption[] = [
  { value: 10, label: '10 por página' },
  { value: 25, label: '25 por página' },
  { value: 50, label: '50 por página' },
  { value: 0, label: 'Todos' },
]

/** Lista de páginas visibles con elipsis, manteniendo primera, última y vecinas. */
function pageList(current: number, last: number): (number | 'gap')[] {
  const pages: (number | 'gap')[] = []
  for (let p = 1; p <= last; p++) {
    if (p === 1 || p === last || (p >= current - 1 && p <= current + 1)) {
      pages.push(p)
    } else if (pages[pages.length - 1] !== 'gap') {
      pages.push('gap')
    }
  }
  return pages
}

export default function Pagination({
  currentPage,
  lastPage,
  total,
  onPageChange,
  perPage,
  perPageOptions,
  onPerPageChange,
}: PaginationProps) {
  const withFooter = perPage !== undefined && onPerPageChange !== undefined

  // Modo simple (legacy): solo anterior/siguiente, oculto con una sola página.
  if (!withFooter) {
    if (lastPage <= 1) return null
    return (
      <div className="pagination">
        <span className="pagination-info">{total} resultado{total !== 1 ? 's' : ''}</span>
        <div className="pagination-pages">
          <button
            type="button"
            className="pagination-page-btn"
            disabled={currentPage <= 1}
            onClick={() => onPageChange(currentPage - 1)}
            aria-label="Página anterior"
          >
            ‹
          </button>
          <span className="pagination-current">{currentPage} / {lastPage}</span>
          <button
            type="button"
            className="pagination-page-btn"
            disabled={currentPage >= lastPage}
            onClick={() => onPageChange(currentPage + 1)}
            aria-label="Página siguiente"
          >
            ›
          </button>
        </div>
      </div>
    )
  }

  const options = perPageOptions ?? DEFAULT_PER_PAGE_OPTIONS

  return (
    <div className="pagination pagination-footer">
      <div className="pagination-perpage">
        <select
          className="pagination-perpage-select"
          value={perPage}
          onChange={e => onPerPageChange!(Number(e.target.value))}
          aria-label="Cantidad por página"
        >
          {options.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
        </select>
        <span className="pagination-info">{total} resultado{total !== 1 ? 's' : ''}</span>
      </div>
      {lastPage > 1 && (
        <div className="pagination-pages">
          <button
            type="button"
            className="pagination-page-btn"
            disabled={currentPage <= 1}
            onClick={() => onPageChange(currentPage - 1)}
            aria-label="Página anterior"
          >
            ‹
          </button>
          {pageList(currentPage, lastPage).map((p, i) =>
            p === 'gap' ? (
              <span key={`gap-${i}`} className="pagination-gap">…</span>
            ) : (
              <button
                key={p}
                type="button"
                className={`pagination-page-btn ${p === currentPage ? 'pagination-page-active' : ''}`}
                aria-current={p === currentPage ? 'page' : undefined}
                onClick={() => onPageChange(p)}
              >
                {p}
              </button>
            ),
          )}
          <button
            type="button"
            className="pagination-page-btn"
            disabled={currentPage >= lastPage}
            onClick={() => onPageChange(currentPage + 1)}
            aria-label="Página siguiente"
          >
            ›
          </button>
        </div>
      )}
    </div>
  )
}
