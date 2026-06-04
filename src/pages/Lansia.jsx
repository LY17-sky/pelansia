import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router';
import { Plus, Search, Edit, Trash2, History, AlertCircle } from 'lucide-react';
import { Card } from '../components/Card';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../components/Table';
import { Button } from '../components/Button';
import { Modal } from '../components/Modal';
import { api } from '../utils/api';
import { useToast } from '../components/Toast';
import { HealthIndicator } from '../components/HealthIndicator';
import { ConfirmDialog } from '../components/ConfirmDialog';

const labelRisiko = { risiko_rendah: 'Risiko Rendah', risiko_sedang: 'Risiko Sedang', risiko_tinggi: 'Risiko Tinggi (Risti)' };
const colorRisiko = { risiko_rendah: 'bg-green-100 text-green-700', risiko_sedang: 'bg-amber-100 text-amber-700', risiko_tinggi: 'bg-red-100 text-red-700' };
const labelKategori = { pra_lansia: 'Pra Lansia', lansia: 'Lansia', lansia_utama: 'Lansia Tua' };
const colorKategori = { pra_lansia: 'bg-blue-100 text-blue-700', lansia: 'bg-purple-100 text-purple-700', lansia_utama: 'bg-orange-100 text-orange-700' };

export function LansiaPage() {
  const toast = useToast();
  const navigate = useNavigate();
  const user = JSON.parse(localStorage.getItem('user') || '{}');
  const isSuperAdmin = user?.role === 'super_admin';
  const [lansia, setLansia] = useState([]);
  const [villages, setVillages] = useState([]);
  const [search, setSearch] = useState('');
  const [categoryFilter, setCategoryFilter] = useState('all');
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [editData, setEditData] = useState(null);
  const [deleteConfirm, setDeleteConfirm] = useState(null);
  const [formData, setFormData] = useState({
    nik: '',
    nama_lengkap: '',
    tempat_lahir: '',
    tanggal_lahir: '',
    jenis_kelamin: 'L',
    alamat: '',
    id_desa: '',
    no_telepon: '',
    bpjs: '',
    nama_keluarga: '',
    hubungan_keluarga: '',
    no_telepon_keluarga: '',
    status_risiko: 'risiko_rendah',
    status_kesehatan: 'sehat',
  });
  
  useEffect(() => {
    loadLansia();
    loadVillages();
  }, []);
  
  useEffect(() => {
    const timer = setTimeout(() => {
      loadLansia(search);
    }, 300);
    return () => clearTimeout(timer);
  }, [search]);
  
  const loadLansia = async (searchQuery = '') => {
    try {
      const response = await api.getLansia(searchQuery);
      setLansia(response.data);
    } catch (error) {
      console.error('Failed to load lansia:', error);
    } finally {
      setLoading(false);
    }
  };
  
  const loadVillages = async () => {
    try {
      const response = await api.getVillages();
      setVillages(response.data);
    } catch (error) {
      console.error('Failed to load villages:', error);
    }
  };
  
  // Kelompokkan data berdasarkan kategori
  const groupedData = lansia.reduce((acc, item) => {
    const category = item.kategori_lansia || 'unknown';
    if (!acc[category]) {
      acc[category] = [];
    }
    acc[category].push(item);
    return acc;
  }, {});

  // Filter berdasarkan kategori yang dipilih
  const filteredLansia = categoryFilter === 'all' 
    ? lansia 
    : lansia.filter(item => item.kategori_lansia === categoryFilter);

  // Hitung statistik
  const stats = {
    pra_lansia: lansia.filter(l => l.kategori_lansia === 'pra_lansia').length,
    lansia: lansia.filter(l => l.kategori_lansia === 'lansia').length,
    lansia_utama: lansia.filter(l => l.kategori_lansia === 'lansia_utama').length,
    risti: lansia.filter(l => l.status_risiko === 'risiko_tinggi').length,
  };
  
  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      if (editData) {
        await api.updateLansia(editData.id, formData);
      } else {
        await api.createLansia(formData);
      }
      loadLansia();
      toast(editData ? 'Data berhasil diperbarui' : 'Data berhasil disimpan', 'success');
    } catch (error) {
      toast(error.message, 'error');
    }
  };

  const handleEdit = (item) => {
    setEditData(item);
    setFormData({
      nik: item.nik,
      nama_lengkap: item.nama_lengkap,
      tempat_lahir: item.tempat_lahir || '',
      tanggal_lahir: item.tanggal_lahir,
      jenis_kelamin: item.jenis_kelamin,
      alamat: item.alamat || '',
      id_desa: item.id_desa || '',
      no_telepon: item.no_telepon || '',
      bpjs: item.bpjs || '',
      nama_keluarga: item.nama_keluarga || '',
      hubungan_keluarga: item.hubungan_keluarga || '',
      no_telepon_keluarga: item.no_telepon_keluarga || '',
      status_risiko: item.status_risiko || 'risiko_rendah',
      status_kesehatan: item.status_kesehatan || 'sehat',
    });
    setModalOpen(true);
  };

  const handleDelete = async (id) => {
    const item = lansia.find(l => l.id === id);
    setDeleteConfirm({ id, name: item?.nama_lengkap || '' });
  };
  
  const executeDelete = async () => {
    try {
      await api.deleteLansia(deleteConfirm.id);
      loadLansia();
      toast('Data berhasil dihapus', 'success');
    } catch (error) {
      toast(error.message, 'error');
    }
    setDeleteConfirm(null);
  };
  
  return (
    <div className="space-y-4">
      {/* Header + Search inline */}
      <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
        <div className="flex items-center gap-4 flex-wrap">
          <div>
            <h1 className="text-2xl font-bold text-gray-800">Data Lansia</h1>
            <p className="text-sm text-gray-500">Kelola data lansia puskesmas</p>
          </div>
          {isSuperAdmin && (
            <div className="flex items-center gap-2 px-3 py-1.5 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-700 whitespace-nowrap">
              <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              <span className="font-medium">Read-Only</span>
            </div>
          )}
        </div>
        <div className="flex items-center gap-2">
          <div className="relative flex-1 min-w-[200px]">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full pl-9 pr-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
              placeholder="Cari nama atau NIK..."
            />
          </div>
          {!isSuperAdmin && (
            <Button 
              variant="primary" 
              onClick={() => {
                setEditData(null);
                setFormData({
                  nik: '',
                  nama_lengkap: '',
                  tempat_lahir: '',
                  tanggal_lahir: '',
                  jenis_kelamin: 'L',
                  alamat: '',
                  id_desa: '',
                  no_telepon: '',
                  bpjs: '',
                  nama_keluarga: '',
                  hubungan_keluarga: '',
                  no_telepon_keluarga: '',
                  status_risiko: 'risiko_rendah',
                });
                setModalOpen(true);
              }}
              className="flex items-center gap-2 text-sm shrink-0"
            >
              <Plus className="w-4 h-4" /> Tambah
            </Button>
          )}
        </div>
      </div>

      {/* Statistik Kategori */}
      <div className="grid grid-cols-2 md:grid-cols-5 gap-2">
        <Card className="bg-blue-50 border border-blue-200 !p-3">
          <div className="flex items-center gap-2">
            <span className="text-xl font-bold text-blue-700">{stats.pra_lansia}</span>
            <div className="flex flex-col">
              <span className="text-xs font-semibold text-blue-600">Pra Lansia</span>
              <span className="text-[10px] text-blue-500">45-59 th</span>
            </div>
          </div>
        </Card>
        <Card className="bg-purple-50 border border-purple-200 !p-3">
          <div className="flex items-center gap-2">
            <span className="text-xl font-bold text-purple-700">{stats.lansia}</span>
            <div className="flex flex-col">
              <span className="text-xs font-semibold text-purple-600">Lansia</span>
              <span className="text-[10px] text-purple-500">60-69 th</span>
            </div>
          </div>
        </Card>
        <Card className="bg-orange-50 border border-orange-200 !p-3">
          <div className="flex items-center gap-2">
            <span className="text-xl font-bold text-orange-700">{stats.lansia_utama}</span>
            <div className="flex flex-col">
              <span className="text-xs font-semibold text-orange-600">Lansia Tua</span>
              <span className="text-[10px] text-orange-500">≥70 th</span>
            </div>
          </div>
        </Card>
        <Card className="bg-red-50 border border-red-200 !p-3">
          <div className="flex items-center gap-2">
            <span className="text-xl font-bold text-red-700">{stats.risti}</span>
            <div className="flex flex-col">
              <span className="text-xs font-semibold text-red-600">Risti</span>
              <span className="text-[10px] text-red-500">Risiko Tinggi</span>
            </div>
          </div>
        </Card>
        <Card className="bg-gray-50 border border-gray-200 !p-3">
          <div className="flex items-center gap-2">
            <span className="text-xl font-bold text-gray-700">{lansia.length}</span>
            <div className="flex flex-col">
              <span className="text-xs font-semibold text-gray-600">Total</span>
              <span className="text-[10px] text-gray-500">Semua</span>
            </div>
          </div>
        </Card>
      </div>

      {/* Filter + Tabel */}
      <div className="bg-white rounded-2xl shadow-md overflow-hidden">
        <div className="px-4 pt-3 pb-0 flex flex-wrap items-center gap-2 border-b border-gray-100">
          <span className="text-xs font-semibold text-gray-500 mr-1">Filter:</span>
          <button
            onClick={() => setCategoryFilter('all')}
            className={`px-3 py-1 rounded-full text-xs font-medium transition-colors ${
              categoryFilter === 'all'
                ? 'bg-gray-800 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            Semua ({lansia.length})
          </button>
          <button
            onClick={() => setCategoryFilter('pra_lansia')}
            className={`px-3 py-1 rounded-full text-xs font-medium transition-colors ${
              categoryFilter === 'pra_lansia'
                ? 'bg-blue-600 text-white'
                : 'bg-blue-100 text-blue-700 hover:bg-blue-200'
            }`}
          >
            Pra Lansia ({stats.pra_lansia})
          </button>
          <button
            onClick={() => setCategoryFilter('lansia')}
            className={`px-3 py-1 rounded-full text-xs font-medium transition-colors ${
              categoryFilter === 'lansia'
                ? 'bg-purple-600 text-white'
                : 'bg-purple-100 text-purple-700 hover:bg-purple-200'
            }`}
          >
            Lansia ({stats.lansia})
          </button>
          <button
            onClick={() => setCategoryFilter('lansia_utama')}
            className={`px-3 py-1 rounded-full text-xs font-medium transition-colors ${
              categoryFilter === 'lansia_utama'
                ? 'bg-orange-600 text-white'
                : 'bg-orange-100 text-orange-700 hover:bg-orange-200'
            }`}
          >
            Lansia Tua ({stats.lansia_utama})
          </button>
        </div>
        
        {filteredLansia.some(l => l.status_risiko === 'risiko_tinggi') && (
          <div className="mx-4 mt-3 mb-0 p-2 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700 flex items-center gap-1.5">
            <AlertCircle className="w-3.5 h-3.5 shrink-0" />
            <span><strong>{filteredLansia.filter(l => l.status_risiko === 'risiko_tinggi').length} lansia Risti</strong> — segera lakukan penanganan</span>
          </div>
        )}
        
        {loading ? (
          <div className="flex items-center justify-center h-64">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500" />
          </div>
        ) : (
          <div className={filteredLansia.some(l => l.status_risiko === 'risiko_tinggi') ? 'mt-3' : ''}>
            <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="w-[22%] whitespace-nowrap">Nama</TableHead>
                    <TableHead className="w-[14%] whitespace-nowrap">NIK</TableHead>
                    <TableHead className="w-[8%] whitespace-nowrap">Usia</TableHead>
                    <TableHead className="w-[13%] whitespace-nowrap">Kategori</TableHead>
                    <TableHead className="w-[16%] whitespace-nowrap">Status</TableHead>
                    <TableHead className="w-auto">Alamat</TableHead>
                    <TableHead className="w-[12%] whitespace-nowrap">Aksi</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredLansia.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={isSuperAdmin ? 7 : 7} className="text-center py-8 text-gray-500">
                        Tidak ada data
                      </TableCell>
                    </TableRow>
                  ) : (
                    filteredLansia.map((item) => (
                      <TableRow key={item.id} className={item.is_risti ? 'bg-red-50' : ''}>
                         <TableCell className="font-medium cursor-pointer hover:text-[#4A90D9] truncate" onClick={() => navigate(`/lansia/riwayat/${item.id}`)}>
                            <div className="flex items-center gap-2 truncate">
                              <span className="truncate">{item.nama_lengkap}</span>
                              {item.is_risti && (
                                <span className="inline-flex items-center gap-1 px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold shrink-0">
                                  <AlertCircle className="w-3 h-3" /> Risti
                                </span>
                              )}
                            </div>
                          </TableCell>
                         <TableCell className="whitespace-nowrap">{item.nik}</TableCell>
                         <TableCell className="font-medium whitespace-nowrap">{item.usia} tahun</TableCell>
                         <TableCell className="whitespace-nowrap">
                           <span className={`px-2 py-1 rounded-full text-xs font-medium ${colorKategori[item.kategori_lansia] || 'bg-gray-100 text-gray-600'}`}>
                             {labelKategori[item.kategori_lansia] || item.kategori_lansia}
                           </span>
                         </TableCell>
                         <TableCell className="whitespace-nowrap">
                           <span className={`px-2 py-1 rounded-full text-xs font-medium ${colorRisiko[item.status_risiko] || 'bg-gray-100 text-gray-600'}`}>
                             {labelRisiko[item.status_risiko] || item.status_risiko}
                           </span>
                         </TableCell>
                         <TableCell className="truncate max-w-0">{item.alamat || '-'}</TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <button
                              onClick={() => navigate(`/lansia/riwayat/${item.id}`)}
                              className="p-1.5 rounded-lg bg-purple-50 text-purple-500 hover:bg-purple-100"
                              title="Riwayat Kunjungan"
                            >
                              <History className="w-4 h-4" />
                            </button>
                            {!isSuperAdmin && (
                              <>
                                <button
                                  onClick={() => handleEdit(item)}
                                  className="p-1.5 rounded-lg bg-blue-50 text-blue-500 hover:bg-blue-100"
                                >
                                  <Edit className="w-4 h-4" />
                                </button>
                                <button
                                  onClick={() => handleDelete(item.id)}
                                  className="p-1.5 rounded-lg bg-red-50 text-red-500 hover:bg-red-100"
                                >
                                  <Trash2 className="w-4 h-4" />
                                </button>
                              </>
                            )}
                          </div>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
            </Table>
          </div>
        )}
      </div>
      
      <Modal
        isOpen={modalOpen}
        onClose={() => setModalOpen(false)}
        title={editData ? 'Edit Data Lansia' : 'Tambah Data Lansia'}
        size="lg"
      >
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">NIK</label>
              <input
                type="text"
                value={formData.nik}
                onChange={(e) => setFormData({ ...formData, nik: e.target.value })}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
              <input
                type="text"
                value={formData.nama_lengkap}
                onChange={(e) => setFormData({ ...formData, nama_lengkap: e.target.value })}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Tempat Lahir</label>
              <input
                type="text"
                value={formData.tempat_lahir}
                onChange={(e) => setFormData({ ...formData, tempat_lahir: e.target.value })}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Tanggal Lahir</label>
              <input
                type="date"
                value={formData.tanggal_lahir}
                onChange={(e) => setFormData({ ...formData, tanggal_lahir: e.target.value })}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Jenis Kelamin</label>
              <select
                value={formData.jenis_kelamin}
                onChange={(e) => setFormData({ ...formData, jenis_kelamin: e.target.value })}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
              >
                <option value="L">Laki-laki</option>
                <option value="P">Perempuan</option>
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Kategori Usia <span className="text-gray-400 text-xs">(otomatis)</span></label>
              {formData.tanggal_lahir ? (
                <span className={`inline-block px-3 py-2 rounded-xl text-sm font-medium ${colorKategori[(() => {
                  const usia = Math.floor((new Date() - new Date(formData.tanggal_lahir)) / (365.25*24*60*60*1000));
                  if (usia >= 70) return 'lansia_utama';
                  if (usia >= 60) return 'lansia';
                  return 'pra_lansia';
                })()] || 'bg-gray-100 text-gray-600'}`}>
                  {(() => {
                    const usia = Math.floor((new Date() - new Date(formData.tanggal_lahir)) / (365.25*24*60*60*1000));
                    if (usia >= 70) return 'Lansia Tua (≥70 thn)';
                    if (usia >= 60) return 'Lansia (60-69 thn)';
                    if (usia >= 45) return 'Pra Lansia (45-59 thn)';
                    return 'Belum Pra Lansia (<45 thn)';
                  })()}
                </span>
              ) : (
                <span className="inline-block px-3 py-2 rounded-xl text-sm bg-gray-100 text-gray-400">Isi tanggal lahir</span>
              )}
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Status Risiko</label>
              <select
                value={formData.status_risiko}
                onChange={(e) => setFormData({ ...formData, status_risiko: e.target.value })}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
              >
                <option value="risiko_rendah">Risiko Rendah</option>
                <option value="risiko_sedang">Risiko Sedang</option>
                <option value="risiko_tinggi">Risiko Tinggi (Risti)</option>
              </select>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Status Kesehatan</label>
              <select
                value={formData.status_kesehatan}
                onChange={(e) => setFormData({ ...formData, status_kesehatan: e.target.value })}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
              >
                <option value="sehat">Sehat</option>
                <option value="sakit_ringan">Sakit Ringan</option>
                <option value="sakit_berat">Sakit Berat</option>
              </select>
            </div>
              {formData.status_risiko === 'risiko_tinggi' && (
                <p className="mt-2 text-xs text-red-600 bg-red-50 p-2 rounded border border-red-200">
                  ⚠️ Status Risti akan ditandai untuk lansia dengan kondisi kesehatan tertentu atau keterbatasan signifikan yang memerlukan monitoring intensif.
                </p>
              )}
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Desa/Kelurahan</label>
              <select
                value={formData.id_desa}
                onChange={(e) => setFormData({ ...formData, id_desa: e.target.value })}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
              >
                <option value="">Pilih Desa</option>
                {villages.map((v) => (
                  <option key={v.id} value={v.id}>{v.nama_desa}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">No. HP</label>
              <input
                type="text"
                value={formData.no_telepon}
                onChange={(e) => setFormData({ ...formData, no_telepon: e.target.value })}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">BPJS</label>
              <input
                type="text"
                value={formData.bpjs}
                onChange={(e) => setFormData({ ...formData, bpjs: e.target.value })}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
              />
            </div>
            <div className="md:col-span-2">
              <label className="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
              <textarea
                value={formData.alamat}
                onChange={(e) => setFormData({ ...formData, alamat: e.target.value })}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
                rows={2}
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Nama Wali</label>
              <input type="text" value={formData.nama_keluarga} onChange={(e) => setFormData({ ...formData, nama_keluarga: e.target.value })}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]" />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Hubungan Wali</label>
              <select value={formData.hubungan_keluarga} onChange={(e) => setFormData({ ...formData, hubungan_keluarga: e.target.value })}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]">
                <option value="">Pilih Hubungan</option>
                <option value="suami">Suami</option>
                <option value="istri">Istri</option>
                <option value="anak">Anak</option>
                <option value="keluarga">Keluarga</option>
                <option value="lainnya">Lainnya</option>
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">No. HP Wali</label>
              <input type="text" value={formData.no_telepon_keluarga} onChange={(e) => setFormData({ ...formData, no_telepon_keluarga: e.target.value })}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]" />
            </div>
          </div>
          
          <div className="flex gap-3 pt-4">
            <Button type="submit" variant="success" className="flex-1">
              Simpan
            </Button>
            <Button type="button" variant="secondary" onClick={() => setModalOpen(false)}>
              Batal
            </Button>
          </div>
        </form>
      </Modal>
      
      <ConfirmDialog
        isOpen={!!deleteConfirm}
        title="Hapus Data Lansia"
        message={`Yakin ingin menghapus data lansia ${deleteConfirm?.name}?`}
        detail="Data kunjungan terkait akan tetap tersimpan."
        onConfirm={executeDelete}
        onCancel={() => setDeleteConfirm(null)}
      />
    </div>
  );
}
