import { forwardRef, type SelectHTMLAttributes } from 'react'
import './Select.css'

interface SelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
  error?: boolean
}

const Select = forwardRef<HTMLSelectElement, SelectProps>(
  ({ error, className = '', children, ...rest }, ref) => {
    return (
      <select
        ref={ref}
        className={`select ${error ? 'select-error' : ''} ${className}`}
        {...rest}
      >
        {children}
      </select>
    )
  },
)

Select.displayName = 'Select'

export default Select
