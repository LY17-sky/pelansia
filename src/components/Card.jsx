import { clsx } from 'clsx';

export function Card({ children, className, ...props }) {
  return (
    <div 
      className={clsx(
        'bg-white rounded-2xl shadow-md p-6',
        className
      )}
      {...props}
    >
      {children}
    </div>
  );
}

export function CardHeader({ children, className }) {
  return (
    <div className={clsx('mb-4', className)}>
      {children}
    </div>
  );
}

export function CardTitle({ children, className }) {
  return (
    <h3 className={clsx('text-lg font-semibold text-gray-800', className)}>
      {children}
    </h3>
  );
}

export function CardContent({ children, className }) {
  return (
    <div className={clsx('', className)}>
      {children}
    </div>
  );
}
