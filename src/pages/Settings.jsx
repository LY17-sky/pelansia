import { useState, useEffect } from 'react';
import { Save, Settings } from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent } from '../components/Card';
import { Button } from '../components/Button';
import { api } from '../utils/api';

export function SettingsPage() {
  const [form, setForm] = useState({ app_name: '', target_lansia: '', backup_enabled: '0' });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState('');

  useEffect(() => {
    loadSettings();
  }, []);

  const loadSettings = async () => {
    try {
      const res = await api.getSettings();
      setForm({ app_name: res.data.app_name || 'PELANSIA', target_lansia: res.data.target_lansia || '100', backup_enabled: res.data.backup_enabled || '0' });
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    setMessage('');
    try {
      await api.updateSettings(form);
      setMessage('Pengaturan berhasil disimpan');
    } catch (err) {
      setMessage(err.message);
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#4A90D9]" />
      </div>
    );
  }

  return (
    <div className="space-y-6 max-w-2xl">
      <div>
        <h1 className="text-2xl font-bold text-gray-800">Pengaturan Sistem</h1>
        <p className="text-gray-500">Konfigurasi aplikasi Puskesmas</p>
      </div>

      {message && (
        <div className={`p-4 rounded-xl ${message.includes('berhasil') ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'}`}>
          {message}
        </div>
      )}

      <Card>
        <CardHeader>
          <CardTitle>
            <div className="flex items-center gap-2">
              <Settings className="w-5 h-5 text-[#4A90D9]" />
              Konfigurasi
            </div>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Nama Aplikasi</label>
                <input type="text" value={form.app_name} onChange={(e) => setForm({ ...form, app_name: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Target Lansia</label>
                <input type="number" value={form.target_lansia} onChange={(e) => setForm({ ...form, target_lansia: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Backup Otomatis</label>
                <select value={form.backup_enabled} onChange={(e) => setForm({ ...form, backup_enabled: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]">
                  <option value="1">Aktif</option>
                  <option value="0">Nonaktif</option>
                </select>
              </div>
            </div>
            <div className="pt-2">
              <Button type="submit" variant="primary" disabled={saving} className="flex items-center gap-2">
                <Save className="w-4 h-4" /> {saving ? 'Menyimpan...' : 'Simpan'}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
