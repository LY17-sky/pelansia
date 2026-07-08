import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router';
import { Users, ClipboardList, HeartPulse, FileText, UserPlus, X, Clock, Activity, Inbox } from 'lucide-react';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, CartesianGrid, PieChart, Pie, Cell, Legend } from 'recharts';
import { Card, CardHeader, CardTitle, CardContent } from '../components/Card';
import { Modal } from '../components/Modal';
import { api } from '../utils/api';

const COLORS = {
  pra_lansia: '#3B82F6',
  lansia: '#8B5CF6',
  lansia_utama: '#F97316',
  risiko_rendah: '#059669',
  risiko_sedang: '#D97706',
  risiko_tinggi: '#DC2626',
  pemeriksaan_biasa: '#3B82F6',
  rawat_inap: '#DC2626',
  rujuk_rs: '#D97706',
  rawat_jalan: '#059669',
};

const labelKategori = { pra_lansia: 'Pra Lansia', lansia: 'Lansia', lansia_utama: 'Lansia Ristik' };
const labelRisiko = { risiko_rendah: 'Risiko Rendah', risiko_sedang: 'Risiko Sedang', risiko_tinggi: 'Risiko Tinggi' };
const labelRekomendasi = { pemeriksaan_biasa: 'Pemeriksaan Umum', rawat_inap: 'Rawat Inap', rujuk_rs: 'Rujuk RS', rawat_jalan: 'Rawat Jalan' };

export function DashboardPage() {
  const navigate = useNavigate();
  const [data, setData] = useState({
    totalLansia: 0,
    kunjunganHariIni: 0,
    lansiaSakit: 0,
    chartData: [],
    kategoriData: [],
    risikoData: [],
    rekomendasiData: [],
    rujukanPoli: [],
  });
  const [loading, setLoading] = useState(true);
  const [modalData, setModalData] = useState([]);
  const [modalTitle, setModalTitle] = useState('');
  const [modalOpen, setModalOpen] = useState(false);
  const [modalLoading, setModalLoading] = useState(false);
  const [modalType, setModalType] = useState('');
  
  useEffect(() => {
    loadDashboard();
  }, []);
  
  const loadDashboard = async () => {
    try {
      const response = await api.getDashboard();
      setData(response.data);
    } catch (error) {
      console.error('Failed to load dashboard:', error);
    } finally {
      setLoading(false);
    }
  };
  
  const openModal = async (type) => {
    setModalLoading(true);
    setModalOpen(true);
    setModalType(type);
    const today = new Date().toISOString().split('T')[0];
    try {
      let result;
      if (type === 'total') {
        result = await api.getLansia();
        setModalTitle('Daftar Semua Lansia Terdaftar');
        setModalData(result.data || []);
      } else if (type === 'kunjungan') {
        result = await api.getVisits(today, today);
        setModalTitle(`Kunjungan Hari Ini (${today})`);
        setModalData(result.data || []);
      } else if (type === 'sakit') {
        result = await api.getLansia();
        const sakit = (result.data || []).filter(l => l.status_kesehatan && l.status_kesehatan !== 'sehat');
        setModalTitle('Daftar Lansia Sakit');
        setModalData(sakit);
      }
    } catch (e) {
      setModalData([]);
    } finally {
      setModalLoading(false);
    }
  };

  const stats = [
    { 
      title: 'Jumlah Lansia', 
      value: data.totalLansia, 
      icon: Users, 
      color: 'bg-[#4A90D9]',
      text: 'text-[#4A90D9]',
    },
    { 
      title: 'Kunjungan Hari Ini', 
      value: data.kunjunganHariIni, 
      icon: ClipboardList, 
      color: 'bg-green-600',
      text: 'text-green-600',
    },
    { 
      title: 'Lansia Sakit', 
      value: data.lansiaSakit, 
      icon: HeartPulse, 
      color: 'bg-red-600',
      text: 'text-red-600',
    },
  ];
  
  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500" />
      </div>
    );
  }
  
  return (
      <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-800">Dashboard</h1>
        <p className="text-gray-500">Ringkasan data pelaporan lansia</p>
      </div>
      
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <button onClick={() => navigate('/kunjungan')} className="flex items-center gap-3 p-4 bg-white border border-gray-200 rounded-xl hover:bg-[#F0F6FC] transition-colors text-left">
          <ClipboardList className="w-5 h-5 text-[#4A90D9]" />
          <div>
            <p className="font-medium text-gray-800 text-sm">Input Kunjungan</p>
            <p className="text-xs text-gray-500">Catat kunjungan baru</p>
          </div>
        </button>
        <button onClick={() => navigate('/lansia')} className="flex items-center gap-3 p-4 bg-white border border-gray-200 rounded-xl hover:bg-[#F0F6FC] transition-colors text-left">
          <UserPlus className="w-5 h-5 text-green-600" />
          <div>
            <p className="font-medium text-gray-800 text-sm">Tambah Lansia</p>
            <p className="text-xs text-gray-500">Registrasi lansia baru</p>
          </div>
        </button>
        <button onClick={() => navigate('/laporan')} className="flex items-center gap-3 p-4 bg-white border border-gray-200 rounded-xl hover:bg-[#F0F6FC] transition-colors text-left">
          <FileText className="w-5 h-5 text-amber-600" />
          <div>
            <p className="font-medium text-gray-800 text-sm">Lihat Laporan</p>
            <p className="text-xs text-gray-500">Export data kunjungan</p>
          </div>
        </button>
      </div>
      
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        {stats.map((stat, index) => {
          const Icon = stat.icon;
          const types = ['total', 'kunjungan', 'sakit'];
          return (
            <Card key={index} className="hover:shadow-lg transition-shadow cursor-pointer" onClick={() => openModal(types[index])}>
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-500">{stat.title}</p>
                  <p className="text-3xl font-bold text-gray-800 mt-1">{stat.value}</p>
                </div>
                <div className={`w-14 h-14 ${stat.color} rounded-xl flex items-center justify-center`}>
                  <Icon className="w-7 h-7 text-white" />
                </div>
              </div>
            </Card>
          );
        })}
      </div>
      
      <Card>
        <CardHeader>
          <CardTitle>Grafik Kunjungan Mingguan</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="h-80">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={data.chartData}>
                <CartesianGrid strokeDasharray="3 3" stroke="#E5E7EB" />
                <XAxis dataKey="hari" tick={{ fontSize: 12 }} stroke="#6B7280" />
                <YAxis tick={{ fontSize: 12 }} stroke="#6B7280" />
                <Tooltip 
                  contentStyle={{ 
                    backgroundColor: '#fff', 
                    border: '1px solid #E5E7EB',
                    borderRadius: '8px',
                  }}
                />
                <Bar dataKey="jumlah" fill="#4A90D9" radius={[4, 4, 0, 0]} name="Kunjungan" />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </CardContent>
      </Card>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Distribusi Kategori Usia</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="h-64">
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={data.kategoriData.map(d => ({ ...d, name: labelKategori[d.kategori] || d.kategori }))}
                    dataKey="total"
                    nameKey="name"
                    cx="50%"
                    cy="50%"
                    outerRadius={70}
                    label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                  >
                    {data.kategoriData.map((entry, index) => (
                      <Cell key={index} fill={COLORS[entry.kategori] || '#6B7280'} />
                    ))}
                  </Pie>
                  <Tooltip />
                  <Legend />
                </PieChart>
              </ResponsiveContainer>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Distribusi Status Risiko</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="h-64">
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={data.risikoData.map(d => ({ ...d, name: labelRisiko[d.risiko] || d.risiko }))}
                    dataKey="total"
                    nameKey="name"
                    cx="50%"
                    cy="50%"
                    outerRadius={70}
                    label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                  >
                    {data.risikoData.map((entry, index) => (
                      <Cell key={index} fill={COLORS[entry.risiko] || '#6B7280'} />
                    ))}
                  </Pie>
                  <Tooltip />
                  <Legend />
                </PieChart>
              </ResponsiveContainer>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Distribusi Rekomendasi</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="h-64">
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={data.rekomendasiData.map(d => ({ ...d, name: labelRekomendasi[d.rekomendasi] || d.rekomendasi }))}
                    dataKey="total"
                    nameKey="name"
                    cx="50%"
                    cy="50%"
                    outerRadius={70}
                    label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                  >
                    {data.rekomendasiData.map((entry, index) => (
                      <Cell key={index} fill={COLORS[entry.rekomendasi] || '#6B7280'} />
                    ))}
                  </Pie>
                  <Tooltip />
                  <Legend />
                </PieChart>
              </ResponsiveContainer>
            </div>
          </CardContent>
        </Card>

        {data.rujukanPoli.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle>Rujukan per Poli</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={data.rujukanPoli} layout="vertical">
                    <CartesianGrid strokeDasharray="3 3" stroke="#E5E7EB" />
                    <XAxis type="number" tick={{ fontSize: 12 }} stroke="#6B7280" />
                    <YAxis type="category" dataKey="poli" tick={{ fontSize: 12 }} stroke="#6B7280" width={120} />
                    <Tooltip
                      contentStyle={{ backgroundColor: '#fff', border: '1px solid #E5E7EB', borderRadius: '8px' }}
                    />
                    <Bar dataKey="total" fill="#4A90D9" radius={[0, 4, 4, 0]} name="Jumlah" />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </CardContent>
          </Card>
        )}
      </div>

      <Modal isOpen={modalOpen} onClose={() => setModalOpen(false)} title={modalTitle} size="xl">
        {modalLoading ? (
          <div className="flex items-center justify-center h-32">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#4A90D9]" />
          </div>
        ) : modalData.length === 0 ? (
          <div className="text-center py-8 text-gray-400">
            <Inbox className="w-12 h-12 mx-auto mb-3 opacity-50" />
            <p>Tidak ada data</p>
          </div>
        ) : modalType === 'kunjungan' ? (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b bg-gray-50">
                  <th className="text-left px-4 py-3 font-semibold text-gray-600">Nama Lansia</th>
                  <th className="text-left px-4 py-3 font-semibold text-gray-600">Jam</th>
                  <th className="text-left px-4 py-3 font-semibold text-gray-600">Diagnosa</th>
                  <th className="text-left px-4 py-3 font-semibold text-gray-600">Status</th>
                  <th className="text-left px-4 py-3 font-semibold text-gray-600">Tindakan</th>
                </tr>
              </thead>
              <tbody>
                {modalData.map((row, i) => (
                  <tr key={i} className="border-b hover:bg-gray-50">
                    <td className="px-4 py-3 font-medium">{row.nama_lengkap}</td>
                    <td className="px-4 py-3">
                      <span className="flex items-center gap-1">
                        <Clock className="w-3.5 h-3.5 text-gray-400" />
                        {row.jam_kunjungan ? row.jam_kunjungan.substring(0, 5) : '-'}
                      </span>
                    </td>
                    <td className="px-4 py-3">{row.diagnosa || '-'}</td>
                    <td className="px-4 py-3">
                      {row.status_kesehatan === 'sehat' ? (
                        <span className="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Sehat</span>
                      ) : row.status_kesehatan === 'sakit_ringan' ? (
                        <span className="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Ringan</span>
                      ) : row.status_kesehatan === 'sakit_berat' ? (
                        <span className="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Berat</span>
                      ) : (
                        <span className="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">-</span>
                      )}
                    </td>
                    <td className="px-4 py-3">{row.tindakan || '-'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b bg-gray-50">
                  <th className="text-left px-4 py-3 font-semibold text-gray-600">Nama Lansia</th>
                  <th className="text-left px-4 py-3 font-semibold text-gray-600">NIK</th>
                  <th className="text-left px-4 py-3 font-semibold text-gray-600">Umur</th>
                  <th className="text-left px-4 py-3 font-semibold text-gray-600">L/P</th>
                  <th className="text-left px-4 py-3 font-semibold text-gray-600">Status</th>
                </tr>
              </thead>
              <tbody>
                {modalData.map((row, i) => (
                  <tr key={i} className="border-b hover:bg-gray-50">
                    <td className="px-4 py-3 font-medium">{row.nama_lengkap}</td>
                    <td className="px-4 py-3 text-gray-500">{row.nik}</td>
                    <td className="px-4 py-3">{row.usia || '-'} thn</td>
                    <td className="px-4 py-3">{row.jenis_kelamin}</td>
                    <td className="px-4 py-3">
                      {row.status_kesehatan === 'sehat' ? (
                        <span className="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Sehat</span>
                      ) : row.status_kesehatan === 'sakit_ringan' ? (
                        <span className="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Ringan</span>
                      ) : row.status_kesehatan === 'sakit_berat' ? (
                        <span className="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Berat</span>
                      ) : (
                        <span className="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">-</span>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Modal>
    </div>
  );
}
