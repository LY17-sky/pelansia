import { useState, useEffect, useRef } from 'react';
import { User, Bell, Info, AlertTriangle, CheckCircle } from 'lucide-react';
import { api } from '../utils/api';

const notifConfig = {
  lansia_baru: { icon: Info, bg: 'bg-blue-100', color: 'text-blue-600' },
  kunjungan_baru: { icon: Info, bg: 'bg-blue-100', color: 'text-blue-600' },
  lansia_risti: { icon: AlertTriangle, bg: 'bg-amber-100', color: 'text-amber-600' },
  kesehatan_memburuk: { icon: AlertTriangle, bg: 'bg-amber-100', color: 'text-amber-600' },
  laporan_terkirim: { icon: CheckCircle, bg: 'bg-green-100', color: 'text-green-600' },
};

function getTimeAgo(datetime) {
  if (!datetime) return '';
  const now = new Date();
  const then = new Date(datetime);
  const diff = Math.floor((now - then) / 1000);
  if (diff < 60) return 'Baru saja';
  if (diff < 3600) return Math.floor(diff / 60) + ' menit lalu';
  if (diff < 86400) return Math.floor(diff / 3600) + ' jam lalu';
  if (diff < 2592000) return Math.floor(diff / 86400) + ' hari lalu';
  return Math.floor(diff / 2592000) + ' bulan lalu';
}

export function Navbar({ user }) {
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [showDropdown, setShowDropdown] = useState(false);
  const dropdownRef = useRef(null);
  const bellRef = useRef(null);

  const fetchNotifications = async () => {
    try {
      const [notifData, countData] = await Promise.all([
        api.getNotifications(),
        api.getNotifCount(),
      ]);
      setNotifications(notifData.data || []);
      setUnreadCount(countData.count || 0);
    } catch (e) {}
  };

  useEffect(() => {
    fetchNotifications();
    const interval = setInterval(fetchNotifications, 30000);
    return () => clearInterval(interval);
  }, []);

  useEffect(() => {
    const handleClickOutside = (e) => {
      if (
        dropdownRef.current && !dropdownRef.current.contains(e.target) &&
        bellRef.current && !bellRef.current.contains(e.target)
      ) {
        setShowDropdown(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleMarkAllRead = async () => {
    try {
      await api.markAllRead();
      setUnreadCount(0);
      setNotifications(prev => prev.map(n => ({ ...n, is_read: 1 })));
    } catch (e) {}
  };

  return (
    <header className="bg-white shadow-sm relative">
      <div className="flex items-center justify-between px-6 py-4">
        <div className="lg:hidden w-10" />

        <div className="flex items-center gap-4 ml-auto">
          <div ref={bellRef} className="relative">
            <button
              className="p-2 rounded-lg hover:bg-gray-100 transition-colors relative"
              onClick={() => setShowDropdown(!showDropdown)}
            >
              <Bell className="w-5 h-5 text-gray-600" />
              {unreadCount > 0 && (
                <span className="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-white">
                  {unreadCount > 9 ? '9+' : unreadCount}
                </span>
              )}
            </button>

            {showDropdown && (
              <div
                ref={dropdownRef}
                className="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-gray-100 z-50"
                style={{ top: '100%' }}
              >
                <div className="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                  <span className="text-sm font-semibold text-gray-800 flex items-center gap-2">
                    <Bell className="w-4 h-4" /> Notifikasi
                  </span>
                  {unreadCount > 0 && (
                    <button
                      onClick={handleMarkAllRead}
                      className="text-xs text-blue-600 hover:text-blue-800 font-medium"
                    >
                      Tandai dibaca
                    </button>
                  )}
                </div>

                <div className="max-h-80 overflow-y-auto">
                  {notifications.length === 0 ? (
                    <div className="px-4 py-8 text-center text-gray-400 text-sm">
                      Tidak ada notifikasi
                    </div>
                  ) : (
                    notifications.map((notif) => {
                      const cfg = notifConfig[notif.type] || notifConfig.lansia_baru;
                      const Icon = cfg.icon;
                      return (
                        <div
                          key={notif.id}
                          className={`px-4 py-3 border-b border-gray-50 hover:bg-gray-50 transition-colors cursor-pointer ${!notif.is_read ? 'bg-blue-50/30' : ''}`}
                        >
                          <div className="flex gap-3">
                            <div className={`w-8 h-8 rounded-lg ${cfg.bg} flex items-center justify-center flex-shrink-0`}>
                              <Icon className={`w-4 h-4 ${cfg.color}`} />
                            </div>
                            <div className="flex-1 min-w-0">
                              <p className={`text-sm ${!notif.is_read ? 'font-semibold' : 'text-gray-700'}`}>
                                {notif.title}
                              </p>
                              <p className="text-xs text-gray-500 mt-0.5 line-clamp-2">
                                {notif.message}
                              </p>
                              <p className="text-xs text-gray-400 mt-1">
                                {getTimeAgo(notif.created_at)}
                              </p>
                            </div>
                          </div>
                        </div>
                      );
                    })
                  )}
                </div>
              </div>
            )}
          </div>

          <div className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer">
            <div className="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center">
              <User className="w-5 h-5 text-white" />
            </div>
            <div className="hidden sm:block">
              <p className="text-sm font-medium text-gray-800">{user?.nama_lengkap}</p>
              <p className="text-xs text-gray-500 capitalize">{user?.role}</p>
            </div>
          </div>
        </div>
      </div>
    </header>
  );
}
