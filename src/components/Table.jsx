import { clsx } from 'clsx';

export function Table({ children, className }) {
  return (
    <div className="overflow-x-auto">
      <table className={clsx('w-full table-fixed', className)}>
        {children}
      </table>
    </div>
  );
}

export function TableHeader({ children, className }) {
  return (
    <thead className={clsx('bg-gray-50', className)}>
      {children}
    </thead>
  );
}

export function TableBody({ children, className }) {
  return (
    <tbody className={clsx('', className)}>
      {children}
    </tbody>
  );
}

export function TableRow({ children, className }) {
  return (
    <tr className={clsx('border-b border-gray-100 hover:bg-gray-50', className)}>
      {children}
    </tr>
  );
}

export function TableHead({ children, className }) {
  return (
    <th className={clsx('px-4 py-3 text-left text-sm font-semibold text-gray-600', className)}>
      {children}
    </th>
  );
}

export function TableCell({ children, className }) {
  return (
    <td className={clsx('px-4 py-3 text-sm text-gray-700', className)}>
      {children}
    </td>
  );
}
