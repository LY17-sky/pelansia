import { useState } from 'react';
import { X, AlertTriangle } from 'lucide-react';

export function ConfirmDialog({ isOpen, title, message, detail, confirmText = 'Hapus', cancelText = 'Batal', onConfirm, onCancel, danger = true }) {
  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-[9999] flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" onClick={onCancel} />
      <div className="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6 animate-slide-in">
        <button onClick={onCancel} className="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
          <X className="w-5 h-5" />
        </button>
        
        <div className="flex items-start gap-4">
          <div className={`w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0 ${danger ? 'bg-red-100' : 'bg-blue-100'}`}>
            <AlertTriangle className={`w-6 h-6 ${danger ? 'text-red-500' : 'text-blue-500'}`} />
          </div>
          <div className="flex-1">
            <h3 className="text-lg font-semibold text-gray-800 mb-1">{title}</h3>
            <p className="text-gray-600 text-sm mb-1">{message}</p>
            {detail && <p className="text-gray-400 text-xs">{detail}</p>}
          </div>
        </div>
        
        <div className="flex gap-3 mt-6">
          <button onClick={onCancel} className="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50 font-medium text-sm transition-colors">
            {cancelText}
          </button>
          <button onClick={onConfirm} className={`flex-1 px-4 py-2.5 rounded-xl text-white font-medium text-sm transition-colors ${danger ? 'bg-red-500 hover:bg-red-600' : 'bg-[#4A90D9] hover:bg-[#3570B5]'}`}>
            {confirmText}
          </button>
        </div>
      </div>
    </div>
  );
}
