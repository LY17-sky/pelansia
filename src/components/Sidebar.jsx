import { clsx } from 'clsx';
import { 
  LayoutDashboard, 
  Users, 
  ClipboardList, 
  FileText, 
  LogOut,
  Menu,
  X,
  UserCog,
  Building2,
  Activity,
  Settings
} from 'lucide-react';
import { useState } from 'react';

const getMenuItems = (role) => {
  const items = [{ path: '/dashboard', icon: LayoutDashboard, label: 'Dashboard' }];
  if (role === 'super_admin') {
    items.push(
      { path: '/lansia', icon: Users, label: 'Data Lansia' },
      { path: '/laporan', icon: FileText, label: 'Laporan' },
    );
  } else {
    items.push(
      { path: '/lansia', icon: Users, label: 'Data Lansia' },
      { path: '/kunjungan', icon: ClipboardList, label: 'Input Kunjungan' },
    );
  }
  return items;
};

const adminMenuItems = [
  { path: '/users', icon: UserCog, label: 'Pengguna Sistem' },
  { path: '/puskesmas', icon: Building2, label: 'Data Wilayah' },
  { path: '/activities', icon: Activity, label: 'Log Aktivitas' },
  { path: '/settings', icon: Settings, label: 'Konfigurasi Sistem' },
];

export function Sidebar({ currentPath, onNavigate, onLogout, user }) {
  const [isOpen, setIsOpen] = useState(false);
  
  return (
    <>
      <button 
        className="lg:hidden fixed top-4 left-4 z-50 p-2 bg-white rounded-lg shadow-md"
        onClick={() => setIsOpen(!isOpen)}
      >
        {isOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
      </button>
      
      <aside className={clsx(
        'fixed left-0 top-0 h-full w-64 bg-[#4A90D9] shadow-lg z-40 transition-transform duration-300',
        'lg:translate-x-0',
        isOpen ? 'translate-x-0' : '-translate-x-full'
      )}>
        <div className="p-6">
          <h1 className="text-xl font-bold text-white">PELANSIA</h1>
          <p className="text-blue-200 text-sm">Sistem Pelaporan Lansia</p>
        </div>
        
        <nav className="p-4">
          <ul className="space-y-2">
            {getMenuItems(user?.role).map((item) => {
              const Icon = item.icon;
              const isActive = currentPath === item.path;
              return (
                <li key={item.path}>
                  <button
                    onClick={() => {
                      onNavigate(item.path);
                      setIsOpen(false);
                    }}
                    className={clsx(
                      'w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200',
                      isActive 
                            ? 'bg-[#4A90D9] text-white' 
                            : 'text-blue-100 hover:bg-[#3570B5]'
                    )}
                  >
                    <Icon className="w-5 h-5" />
                    <span className="font-medium">{item.label}</span>
                  </button>
                </li>
              );
            })}
          </ul>
          {user?.role === 'super_admin' && (
            <>
              <div className="mt-6 mb-2 px-4">
                <p className="text-xs font-semibold text-blue-200 uppercase tracking-wider">Manajemen</p>
              </div>
              <ul className="space-y-2">
                {adminMenuItems.map((item) => {
                  const Icon = item.icon;
                  const isActive = currentPath === item.path;
                  return (
                    <li key={item.path}>
                      <button
                        onClick={() => {
                          onNavigate(item.path);
                          setIsOpen(false);
                        }}
                        className={clsx(
                          'w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200',
                          isActive 
                            ? 'bg-blue-500 text-white' 
                            : 'text-gray-600 hover:bg-gray-100'
                        )}
                      >
                        <Icon className="w-5 h-5" />
                        <span className="font-medium">{item.label}</span>
                      </button>
                    </li>
                  );
                })}
              </ul>
            </>
          )}
        </nav>
        
        <div className="absolute bottom-0 left-0 right-0 p-4 space-y-1">
          <button
            onClick={() => { onNavigate('/profile'); setIsOpen(false); }}
            className="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-[#3570B5] transition-colors"
          >
            <UserCog className="w-5 h-5" />
            <span className="font-medium">Akun Saya</span>
          </button>
          <button
            onClick={onLogout}
            className="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-red-400 hover:bg-red-800/30 transition-colors"
          >
            <LogOut className="w-5 h-5" />
            <span className="font-medium">Logout</span>
          </button>
        </div>
      </aside>
      
      {isOpen && (
        <div 
          className="lg:hidden fixed inset-0 bg-black/50 z-30"
          onClick={() => setIsOpen(false)}
        />
      )}
    </>
  );
}
