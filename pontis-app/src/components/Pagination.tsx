import Button from './Button'
import './Pagination.css'

interface PaginationProps {
  currentPage: number
  lastPage: number
  total: number
  onPageChange: (page: number) => void
}

export default function Pagination({ currentPage, lastPage, total, onPageChange }: PaginationProps) {
  if (lastPage <= 1) return null

  return (
    <div className="pagination">
      <span className="pagination-info">{total} resultado{total !== 1 ? 's' : ''}</span>
      <div className="pagination-buttons">
        <Button
          variant="outline"
          disabled={currentPage <= 1}
          onClick={() => onPageChange(currentPage - 1)}
        >
          Anterior
        </Button>
        <span className="pagination-current">
          {currentPage} / {lastPage}
        </span>
        <Button
          variant="outline"
          disabled={currentPage >= lastPage}
          onClick={() => onPageChange(currentPage + 1)}
        >
          Siguiente
        </Button>
      </div>
    </div>
  )
}
