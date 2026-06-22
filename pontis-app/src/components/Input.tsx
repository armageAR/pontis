import { forwardRef, type InputHTMLAttributes } from 'react'
import './Input.css'

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  error?: boolean
}

const Input = forwardRef<HTMLInputElement, InputProps>(
  ({ error, className = '', ...rest }, ref) => {
    return (
      <input
        ref={ref}
        className={`input ${error ? 'input-error' : ''} ${className}`}
        {...rest}
      />
    )
  },
)

Input.displayName = 'Input'

export default Input
