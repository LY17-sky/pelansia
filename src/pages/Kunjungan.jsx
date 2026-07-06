import { useEffect, useState } from 'react';
import { Save, X, User, Calendar, Activity } from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent } from '../components/Card';
import { Button } from '../components/Button';
import { api } from '../utils/api';
import { useToast } from '../components/Toast';
import { HealthIndicator } from '../components/HealthIndicator';
import { hitungUsia, hitungKategoriLansia, statusKesehatanMapping, POLI_INTERNAL, klasifikasiIMT } from '../utils/constants';

const REKOMENDASI_OPTIONS = [
  { value: 'pemeriksaan_biasa', label: 'Pemeriksaan Umum', color: 'bg-blue-100 text-blue-700' },
  { value: 'rawat_inap', label: 'Perlu Rawat Inap', color: 'bg-red-100 text-red-600' },
  { value: 'rujuk_rs', label: 'Rujuk RS', color: 'bg-orange-100 text-orange-600' },
  { value: 'rawat_jalan', label: 'Rawat Jalan', color: 'bg-green-100 text-green-600' },
];
const labelRisiko = { risiko_rendah: 'Risiko Rendah', risiko_sedang: 'Risiko Sedang', risiko_tinggi: 'Risiko Tinggi' };
const colorRisiko = { risiko_rendah: 'bg-green-100 text-green-700', risiko_sedang: 'bg-amber-100 text-amber-700', risiko_tinggi: 'bg-red-100 text-red-700' };

export function KunjunganPage() {
  const toast = useToast();
  const [lansia, setLansia] = useState([]);
  const [selectedLansia, setSelectedLansia] = useState(null);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [success, setSuccess] = useState(false);
  const [formData, setFormData] = useState({
    id_lansia: '',
    tanggal_kunjungan: new Date().toISOString().split('T')[0],
    jam_kunjungan: new Date().toTimeString().slice(0, 5),
    jenis_kunjungan: 'baru',
    status_kesehatan: 'sehat',
    tekanan_darah: '',
    berat_badan: '',
    tinggi_badan: '',
    imt: '',
    nadi: '',
    respiratory_rate: '',
    status_disabilitas: 'tidak_ada',
    kelainan: '',
    keluhan: '',
    diagnosa: '',
    tindakan: '',
    rujukan: '',
    tujuan_rujukan: [],
    rekomendasi: 'pemeriksaan_biasa',
    obat: '',
    gula_darah: '',
    kolesterol: '',
    hemoglobin: '',
    spo2: '',
    suhu_tubuh: '',
    skrining_risiko_jatuh: 'tidak_ada',
    skrining_gangguan_kognitif: 'tidak_ada',
    skrining_depresi: 'tidak_ada',
    skrining_inkontinensia: 'tidak_ada',
    skrining_malnutrisi: 'tidak_ada',
  });
  
  useEffect(() => {
    loadLansia();
  }, []);
  
  const loadLansia = async () => {
    try {
      const response = await api.getLansia();
      setLansia(response.data);
    } catch (error) {
      console.error('Failed to load lansia:', error);
    } finally {
      setLoading(false);
    }
  };
  
  const calculateIMT = (bb, tb) => {
    if (bb && tb) {
      const tbM = tb / 100;
      const imt = bb / (tbM * tbM);
      return imt.toFixed(1);
    }
    return '';
  };
  
  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => {
      const newData = { ...prev, [name]: value };
      
      if (name === 'id_lansia') {
        const l = lansia.find(l => l.id == value);
        setSelectedLansia(l || null);
      }
      
      if (name === 'berat_badan' || name === 'tinggi_badan') {
        const bb = name === 'berat_badan' ? parseFloat(value) : parseFloat(prev.berat_badan);
        const tb = name === 'tinggi_badan' ? parseFloat(value) : parseFloat(prev.tinggi_badan);
        newData.imt = calculateIMT(bb, tb);
      }
      
      return newData;
    });
  };
  
  const parseTD = (td) => {
    const parts = td.split('/');
    if (parts.length === 2) {
      return { sistol: parseInt(parts[0]) || 0, diastol: parseInt(parts[1]) || 0 };
    }
    return { sistol: 0, diastol: 0 };
  };
  
  const handlePoliChange = (poli) => {
    setFormData(prev => {
      const current = prev.tujuan_rujukan || [];
      const updated = current.includes(poli)
        ? current.filter(p => p !== poli)
        : [...current, poli];
      return { ...prev, tujuan_rujukan: updated };
    });
  };
  
  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    
    try {
      const user = JSON.parse(localStorage.getItem('user'));
      const { sistol, diastol } = parseTD(formData.tekanan_darah);
      const usia = selectedLansia ? Math.floor((new Date() - new Date(selectedLansia.tanggal_lahir)) / (365.25 * 24 * 60 * 60 * 1000)) : 0;
      
      const classificationRes = await api.getHealthClassify({
        usia,
        td_sistol: sistol,
        td_diastol: diastol,
        imt: parseFloat(formData.imt) || 0,
        nadi: parseInt(formData.nadi) || 0,
        rr: parseInt(formData.respiratory_rate) || 0,
        disabilitas: formData.status_disabilitas || '',
        gula_darah: formData.gula_darah ? parseInt(formData.gula_darah) : 0,
        kolesterol: formData.kolesterol ? parseInt(formData.kolesterol) : 0,
        hemoglobin: formData.hemoglobin ? parseFloat(formData.hemoglobin) : 0,
        spo2: formData.spo2 ? parseInt(formData.spo2) : 0,
        suhu_tubuh: formData.suhu_tubuh ? parseFloat(formData.suhu_tubuh) : 0,
        jenis_kelamin: selectedLansia?.jenis_kelamin || 'L',
      });
      const classification = classificationRes.data;
      
      const dataToSend = {
        ...formData,
        id_petugas: user.id,
        tekanan_darah_sistol: sistol,
        tekanan_darah_diastol: diastol,
        imt: parseFloat(formData.imt) || 0,
        berat_badan: parseFloat(formData.berat_badan) || 0,
        tinggi_badan: parseFloat(formData.tinggi_badan) || 0,
        nadi: parseInt(formData.nadi) || 0,
        respiratory_rate: parseInt(formData.respiratory_rate) || 0,
        gula_darah: formData.gula_darah ? parseInt(formData.gula_darah) : null,
        kolesterol: formData.kolesterol ? parseInt(formData.kolesterol) : null,
        hemoglobin: formData.hemoglobin ? parseFloat(formData.hemoglobin) : null,
        spo2: formData.spo2 ? parseInt(formData.spo2) : null,
        suhu_tubuh: formData.suhu_tubuh ? parseFloat(formData.suhu_tubuh) : null,
        skrining_risiko_jatuh: formData.skrining_risiko_jatuh,
        skrining_gangguan_kognitif: formData.skrining_gangguan_kognitif,
        skrining_depresi: formData.skrining_depresi,
        skrining_inkontinensia: formData.skrining_inkontinensia,
        skrining_malnutrisi: formData.skrining_malnutrisi,
      };
      
      dataToSend.status_kesehatan = classification.status_db;
      
      await api.createVisit(dataToSend);
      toast('Data kunjungan berhasil disimpan!', 'success');
      setSuccess(true);
      setSelectedLansia(null);
      setTimeout(() => {
        setSuccess(false);
        setFormData({
          id_lansia: '',
          tanggal_kunjungan: new Date().toISOString().split('T')[0],
          jam_kunjungan: new Date().toTimeString().slice(0, 5),
          jenis_kunjungan: 'baru',
          status_kesehatan: 'sehat',
          tekanan_darah: '',
          berat_badan: '',
          tinggi_badan: '',
          imt: '',
          nadi: '',
          respiratory_rate: '',
          status_disabilitas: 'tidak_ada',
          kelainan: '',
          keluhan: '',
          diagnosa: '',
          tindakan: '',
          rujukan: '',
          tujuan_rujukan: [],
          rekomendasi: 'pemeriksaan_biasa',
          obat: '',
          gula_darah: '',
          kolesterol: '',
          hemoglobin: '',
          spo2: '',
          suhu_tubuh: '',
          skrining_risiko_jatuh: 'tidak_ada',
          skrining_gangguan_kognitif: 'tidak_ada',
          skrining_depresi: 'tidak_ada',
          skrining_inkontinensia: 'tidak_ada',
          skrining_malnutrisi: 'tidak_ada',
        });
      }, 2000);
    } catch (error) {
      toast(error.message, 'error');
    } finally {
      setSubmitting(false);
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
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-800">Input Kunjungan</h1>
        <p className="text-gray-500">Catat kunjungan harian lansia</p>
      </div>
      
      {success && (
        <div className="p-4 bg-green-50 border border-green-200 rounded-xl text-green-600">
          Data kunjungan berhasil disimpan!
        </div>
      )}
      
      <form onSubmit={handleSubmit}>
        <Card>
          <CardHeader>
            <CardTitle>Data Kunjungan</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div className="md:col-span-2">
                <label className="block text-sm font-medium text-gray-700 mb-1">Pilih Lansia</label>
                <select
                  name="id_lansia"
                  value={formData.id_lansia}
                  onChange={handleChange}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
                  required
                >
                  <option value="">Pilih Lansia</option>
                  {lansia.map((l) => (
                    <option key={l.id} value={l.id}>{l.nama_lengkap} - {l.nik}</option>
                  ))}
                </select>
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                <input
                  type="date"
                  name="tanggal_kunjungan"
                  value={formData.tanggal_kunjungan}
                  onChange={handleChange}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
                  required
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Jam</label>
                <input
                  type="time"
                  name="jam_kunjungan"
                  value={formData.jam_kunjungan}
                  onChange={handleChange}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
                  required
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Jenis Kunjungan</label>
                <div className="flex gap-4 mt-2">
                  <label className="flex items-center gap-2 cursor-pointer">
                    <input
                      type="radio"
                      name="jenis_kunjungan"
                      value="baru"
                      checked={formData.jenis_kunjungan === 'baru'}
                      onChange={handleChange}
                      className="w-4 h-4 text-blue-500"
                    />
                    <span className="text-sm text-gray-700">Baru</span>
                  </label>
                  <label className="flex items-center gap-2 cursor-pointer">
                    <input
                      type="radio"
                      name="jenis_kunjungan"
                      value="lama"
                      checked={formData.jenis_kunjungan === 'lama'}
                      onChange={handleChange}
                      className="w-4 h-4 text-blue-500"
                    />
                    <span className="text-sm text-gray-700">Lama</span>
                  </label>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
        
        {selectedLansia && (
          <>
          <Card className="mt-4">
            <CardHeader>
              <CardTitle><User className="w-5 h-5 inline mr-2 text-[#4A90D9]" />Data Lansia</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                  <label className="text-xs text-gray-500">Nama</label>
                  <p className="font-medium">{selectedLansia.nama_lengkap}</p>
                </div>
                <div>
                  <label className="text-xs text-gray-500">NIK</label>
                  <p className="font-medium">{selectedLansia.nik}</p>
                </div>
                <div>
                  <label className="text-xs text-gray-500">Usia</label>
                  <p className="font-medium">{hitungUsia(selectedLansia.tanggal_lahir)} tahun</p>
                </div>
                <div>
                  <label className="text-xs text-gray-500">Kategori</label>
                  <span className={`inline-block px-2 py-1 rounded-full text-xs font-medium ${hitungKategoriLansia(hitungUsia(selectedLansia.tanggal_lahir)).color}`}>
                    {hitungKategoriLansia(hitungUsia(selectedLansia.tanggal_lahir)).label}
                  </span>
                </div>
                <div>
                  <label className="text-xs text-gray-500">Status Risiko</label>
                  <span className={`inline-block px-2 py-1 rounded-full text-xs font-medium ${colorRisiko[selectedLansia.status_risiko] || 'bg-gray-100 text-gray-600'}`}>
                    {labelRisiko[selectedLansia.status_risiko] || selectedLansia.status_risiko}
                  </span>
                </div>
                <div>
                  <label className="text-xs text-gray-500">Jenis Kelamin</label>
                  <p className="font-medium">{selectedLansia.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'}</p>
                </div>
              </div>
            </CardContent>
          </Card>
          
          {selectedLansia.status_risiko === 'risiko_tinggi' && (
            <div className="mt-4 p-4 bg-red-50 border border-red-200 rounded-xl">
              <div className="flex items-center gap-2 text-red-700">
                <span className="w-3 h-3 bg-red-500 rounded-full" />
                <strong>PERHATIAN:</strong> Lansia ini berstatus <strong>Risiko Tinggi</strong>. Harap lebih waspada dalam penanganan.
              </div>
            </div>
          )}
          </>
        )}
        
        <Card className="mt-4">
          <CardHeader>
            <CardTitle>Pemeriksaan Fisik</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Tekanan Darah (mmHg)</label>
                <input
                  type="text"
                  name="tekanan_darah"
                  value={formData.tekanan_darah}
                  onChange={handleChange}
                  placeholder="120/80"
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Berat Badan (kg)</label>
                <input
                  type="number"
                  name="berat_badan"
                  value={formData.berat_badan}
                  onChange={handleChange}
                  step="0.1"
                  placeholder="kg"
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Tinggi Badan (cm)</label>
                <input
                  type="number"
                  name="tinggi_badan"
                  value={formData.tinggi_badan}
                  onChange={handleChange}
                  placeholder="cm"
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">IMT</label>
                <div className="flex gap-2 items-center">
                  <input
                    type="text"
                    name="imt"
                    value={formData.imt}
                    readOnly
                    className="flex-1 px-4 py-2 border border-gray-200 rounded-xl bg-gray-50 text-gray-500"
                    placeholder="Otomatis terhitung"
                  />
                  {formData.imt && klasifikasiIMT(parseFloat(formData.imt)) && (
                    <span className={`px-2 py-1 rounded-full text-xs font-medium whitespace-nowrap ${klasifikasiIMT(parseFloat(formData.imt)).color}`}>
                      {klasifikasiIMT(parseFloat(formData.imt)).label}
                    </span>
                  )}
                </div>
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Nadi</label>
                <input
                  type="number"
                  name="nadi"
                  value={formData.nadi}
                  onChange={handleChange}
                  placeholder="x/menit"
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Respiratory Rate</label>
                <input
                  type="number"
                  name="respiratory_rate"
                  value={formData.respiratory_rate}
                  onChange={handleChange}
                  placeholder="x/menit"
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Status Disabilitas</label>
                <select
                  name="status_disabilitas"
                  value={formData.status_disabilitas}
                  onChange={handleChange}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
                >
                  <option value="tidak_ada">Tidak Ada</option>
                  <option value="ringan">Ringan</option>
                  <option value="sedang">Sedang</option>
                  <option value="berat">Berat</option>
                </select>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card className="mt-4">
          <CardHeader>
            <CardTitle>Pemeriksaan Tambahan (Opsional)</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Gula Darah (mg/dL)</label>
                <input type="number" name="gula_darah" value={formData.gula_darah} onChange={handleChange} placeholder="mg/dL"
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Kolesterol (mg/dL)</label>
                <input type="number" name="kolesterol" value={formData.kolesterol} onChange={handleChange} placeholder="mg/dL"
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Hemoglobin (g/dL)</label>
                <input type="number" name="hemoglobin" value={formData.hemoglobin} onChange={handleChange} step="0.1" placeholder="g/dL"
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">SpO2 (%)</label>
                <input type="number" name="spo2" value={formData.spo2} onChange={handleChange} placeholder="%"
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Suhu Tubuh (°C)</label>
                <input type="number" name="suhu_tubuh" value={formData.suhu_tubuh} onChange={handleChange} step="0.1" placeholder="°C"
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]" />
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card className="mt-4">
          <CardHeader>
            <CardTitle><Activity className="w-5 h-5 inline mr-2 text-[#4A90D9]" />Skrining Geriatri Dasar</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Risiko Jatuh</label>
                <select name="skrining_risiko_jatuh" value={formData.skrining_risiko_jatuh} onChange={handleChange}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]">
                  <option value="tidak_ada">Tidak Ada Risiko</option>
                  <option value="rendah">Risiko Rendah</option>
                  <option value="tinggi">Risiko Tinggi</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Gangguan Kognitif</label>
                <select name="skrining_gangguan_kognitif" value={formData.skrining_gangguan_kognitif} onChange={handleChange}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]">
                  <option value="tidak_ada">Tidak Ada Gangguan</option>
                  <option value="ringan">Gangguan Ringan</option>
                  <option value="berat">Gangguan Berat</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Depresi</label>
                <select name="skrining_depresi" value={formData.skrining_depresi} onChange={handleChange}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]">
                  <option value="tidak_ada">Tidak Ada Depresi</option>
                  <option value="ringan">Depresi Ringan</option>
                  <option value="berat">Depresi Berat</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Inkontinensia</label>
                <select name="skrining_inkontinensia" value={formData.skrining_inkontinensia} onChange={handleChange}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]">
                  <option value="tidak_ada">Tidak Ada</option>
                  <option value="kadang">Kadang-kadang</option>
                  <option value="sering">Sering</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Malnutrisi</label>
                <select name="skrining_malnutrisi" value={formData.skrining_malnutrisi} onChange={handleChange}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]">
                  <option value="tidak_ada">Tidak Ada Risiko</option>
                  <option value="risiko">Risiko Malnutrisi</option>
                  <option value="malnutrisi">Malnutrisi</option>
                </select>
              </div>
            </div>
          </CardContent>
        </Card>

        <HealthIndicator
          td_sistol={parseTD(formData.tekanan_darah).sistol}
          td_diastol={parseTD(formData.tekanan_darah).diastol}
          imt={formData.imt}
          nadi={formData.nadi}
          rr={formData.respiratory_rate}
          disabilitas={formData.status_disabilitas}
          gula_darah={formData.gula_darah}
          kolesterol={formData.kolesterol}
          hemoglobin={formData.hemoglobin}
          spo2={formData.spo2}
          suhu_tubuh={formData.suhu_tubuh}
          usia={selectedLansia ? Math.floor((new Date() - new Date(selectedLansia.tanggal_lahir)) / (365.25 * 24 * 60 * 60 * 1000)) : 0}
          jenis_kelamin={selectedLansia?.jenis_kelamin || 'L'}
        />
        
        <Card className="mt-4">
          <CardHeader>
            <CardTitle>Keluhan & Tindakan</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Keluhan</label>
                <textarea
                  name="keluhan"
                  value={formData.keluhan}
                  onChange={handleChange}
                  rows={2}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Kelainan</label>
                <textarea
                  name="kelainan"
                  value={formData.kelainan}
                  onChange={handleChange}
                  rows={2}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Diagnosa</label>
                <textarea
                  name="diagnosa"
                  value={formData.diagnosa}
                  onChange={handleChange}
                  rows={2}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Tindakan</label>
                <textarea
                  name="tindakan"
                  value={formData.tindakan}
                  onChange={handleChange}
                  rows={2}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
                />
              </div>
              
              <div className="md:col-span-2">
                <label className="block text-sm font-medium text-gray-700 mb-1">Obat</label>
                <textarea
                  name="obat"
                  value={formData.obat}
                  onChange={handleChange}
                  rows={2}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
                />
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card className="mt-4">
          <CardHeader>
            <CardTitle>Rujukan Internal</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              <label className="block text-sm font-medium text-gray-700 mb-2">Tujuan Poli</label>
              <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
                {POLI_INTERNAL.map(poli => (
                  <label key={poli} className="flex items-center gap-2 p-3 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50">
                    <input
                      type="checkbox"
                      checked={(formData.tujuan_rujukan || []).includes(poli)}
                      onChange={() => handlePoliChange(poli)}
                      className="w-4 h-4 text-blue-500"
                    />
                    <span className="text-sm text-gray-700">{poli}</span>
                  </label>
                ))}
              </div>
              <div className="mt-3">
                <label className="block text-sm font-medium text-gray-700 mb-1">Keterangan Rujukan</label>
                <textarea
                  name="rujukan"
                  value={formData.rujukan}
                  onChange={handleChange}
                  rows={2}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
                  placeholder="Catatan untuk rujukan..."
                />
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card className="mt-4">
          <CardHeader>
            <CardTitle>Rekomendasi</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              {REKOMENDASI_OPTIONS.map(opt => (
                <label key={opt.value} className={`flex items-center gap-3 p-3 border rounded-xl cursor-pointer ${
                  formData.rekomendasi === opt.value ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:bg-gray-50'
                }`}>
                  <input
                    type="radio"
                    name="rekomendasi"
                    value={opt.value}
                    checked={formData.rekomendasi === opt.value}
                    onChange={handleChange}
                    className="w-4 h-4 text-blue-500"
                  />
                  <div>
                    <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${opt.color}`}>
                      {opt.label}
                    </span>
                  </div>
                </label>
              ))}
            </div>
          </CardContent>
        </Card>
        
        <div className="flex gap-3 mt-6">
          <Button 
            type="submit" 
            variant="success" 
            disabled={submitting}
            className="flex items-center gap-2"
          >
            <Save className="w-4 h-4" />
            {submitting ? 'Menyimpan...' : 'Simpan'}
          </Button>
          <Button 
            type="reset" 
            variant="secondary"
            onClick={() => {
              setFormData({
                id_lansia: '',
                tanggal_kunjungan: new Date().toISOString().split('T')[0],
                jam_kunjungan: new Date().toTimeString().slice(0, 5),
                jenis_kunjungan: 'baru',
                status_kesehatan: 'sehat',
                tekanan_darah: '',
                berat_badan: '',
                tinggi_badan: '',
                imt: '',
                nadi: '',
                respiratory_rate: '',
                status_disabilitas: 'tidak_ada',
                kelainan: '',
                keluhan: '',
                diagnosa: '',
                tindakan: '',
                rujukan: '',
                tujuan_rujukan: [],
                rekomendasi: 'pemeriksaan_biasa',
                obat: '',
                gula_darah: '',
                kolesterol: '',
                hemoglobin: '',
                spo2: '',
                suhu_tubuh: '',
                skrining_risiko_jatuh: 'tidak_ada',
                skrining_gangguan_kognitif: 'tidak_ada',
                skrining_depresi: 'tidak_ada',
                skrining_inkontinensia: 'tidak_ada',
                skrining_malnutrisi: 'tidak_ada',
              });
              setSelectedLansia(null);
            }}
            className="flex items-center gap-2"
          >
            <X className="w-4 h-4" />
            Batal
          </Button>
        </div>
      </form>
    </div>
  );
}
