import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router';
import { ArrowLeft, Printer } from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent } from '../components/Card';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../components/Table';
import { Button } from '../components/Button';
import { api } from '../utils/api';
import { HealthIndicator } from '../components/HealthIndicator';
import { statusKesehatanMapping, hitungKategoriLansia, hitungUsia, klasifikasiIMT } from '../utils/constants';

const labelRisiko = { risiko_rendah: 'Risiko Rendah', risiko_sedang: 'Risiko Sedang', risiko_tinggi: 'Risiko Tinggi' };
const colorRisiko = { risiko_rendah: 'bg-green-100 text-green-700', risiko_sedang: 'bg-amber-100 text-amber-700', risiko_tinggi: 'bg-red-100 text-red-700' };
const labelKategori = { pra_lansia: 'Pra Lansia', lansia: 'Lansia', lansia_utama: 'Lansia Ristik' };
const labelRekomendasi = { pemeriksaan_biasa: 'Pemeriksaan Umum', rawat_inap: 'Rawat Inap', rujuk_rs: 'Rujuk RS', rawat_jalan: 'Rawat Jalan' };
const colorRekomendasi = { pemeriksaan_biasa: 'bg-blue-100 text-blue-700', rawat_inap: 'bg-red-100 text-red-700', rujuk_rs: 'bg-orange-100 text-orange-700', rawat_jalan: 'bg-green-100 text-green-700' };

const skriningLabels = {
  risiko_jatuh: { tidak_ada: 'Tidak Ada', rendah: 'Risiko Rendah', tinggi: 'Risiko Tinggi' },
  gangguan_kognitif: { tidak_ada: 'Tidak Ada', ringan: 'Gangguan Ringan', berat: 'Gangguan Berat' },
  depresi: { tidak_ada: 'Tidak Ada', ringan: 'Depresi Ringan', berat: 'Depresi Berat' },
  inkontinensia: { tidak_ada: 'Tidak Ada', kadang: 'Kadang-kadang', sering: 'Sering' },
  malnutrisi: { tidak_ada: 'Tidak Ada', risiko: 'Risiko Malnutrisi', malnutrisi: 'Malnutrisi' },
};
const skriningColors = {
  tidak_ada: 'bg-green-100 text-green-700',
  rendah: 'bg-amber-100 text-amber-700',
  ringan: 'bg-amber-100 text-amber-700',
  kadang: 'bg-amber-100 text-amber-700',
  risiko: 'bg-amber-100 text-amber-700',
  tinggi: 'bg-red-100 text-red-700',
  berat: 'bg-red-100 text-red-700',
  sering: 'bg-red-100 text-red-700',
  malnutrisi: 'bg-red-100 text-red-700',
};

export function RiwayatLansiaPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadRiwayat();
  }, [id]);

  const loadRiwayat = async () => {
    try {
      const response = await api.getRiwayat(id);
      setData(response.data);
    } catch (error) {
      console.error('Failed to load riwayat:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#4A90D9]" />
      </div>
    );
  }

  if (!data || !data.lansia) {
    return (
      <div className="text-center py-12 text-gray-500">
        Data lansia tidak ditemukan
      </div>
    );
  }

  const { lansia, visits } = data;
  const usia = Math.floor((new Date() - new Date(lansia.tanggal_lahir)) / (365.25 * 24 * 60 * 60 * 1000));

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <button onClick={() => navigate('/lansia')} className="p-2 rounded-lg hover:bg-gray-100">
          <ArrowLeft className="w-5 h-5 text-gray-600" />
        </button>
        <div>
          <h1 className="text-2xl font-bold text-gray-800">Riwayat Kunjungan</h1>
          <p className="text-gray-500">Detail data dan riwayat kunjungan lansia</p>
        </div>
      </div>

      {lansia.status_risiko === 'risiko_tinggi' && (
        <div className="p-4 bg-red-50 border border-red-200 rounded-xl">
          <div className="flex items-center gap-2 text-red-700">
            <span className="w-3 h-3 bg-red-500 rounded-full" />
            <strong>PERHATIAN:</strong> Lansia ini berstatus <strong>Risiko Tinggi</strong>.
          </div>
        </div>
      )}

      <Card>
        <CardHeader>
          <CardTitle>Data Lansia</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
              <label className="text-xs text-gray-500">Nama</label>
              <p className="font-medium">{lansia.nama_lengkap}</p>
            </div>
            <div>
              <label className="text-xs text-gray-500">NIK</label>
              <p className="font-medium">{lansia.nik}</p>
            </div>
            <div>
              <label className="text-xs text-gray-500">Usia</label>
              <p className="font-medium">{usia} tahun</p>
            </div>
            <div>
              <label className="text-xs text-gray-500">Jenis Kelamin</label>
              <p className="font-medium">{lansia.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'}</p>
            </div>
            <div>
              <label className="text-xs text-gray-500">Kategori</label>
              <span className={`inline-block px-2 py-1 rounded-full text-xs font-medium ${{
                pra_lansia: 'bg-blue-100 text-blue-600',
                lansia: 'bg-purple-100 text-purple-600',
                lansia_utama: 'bg-orange-100 text-orange-700'
              }[lansia.kategori_lansia] || 'bg-gray-100 text-gray-600'}`}>
                {labelKategori[lansia.kategori_lansia] || lansia.kategori_lansia}
              </span>
            </div>
            <div>
              <label className="text-xs text-gray-500">Status Risiko</label>
              <span className={`inline-block px-2 py-1 rounded-full text-xs font-medium ${colorRisiko[lansia.status_risiko] || 'bg-gray-100 text-gray-600'}`}>
                {labelRisiko[lansia.status_risiko] || lansia.status_risiko}
              </span>
            </div>
            <div>
              <label className="text-xs text-gray-500">Desa</label>
              <p className="font-medium">{lansia.nama_desa || '-'}</p>
            </div>
            <div>
              <label className="text-xs text-gray-500">BPJS</label>
              <p className="font-medium">{lansia.bpjs || '-'}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Riwayat Kunjungan ({visits.length})</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Tanggal</TableHead>
                <TableHead>Jam</TableHead>
                <TableHead>Jenis</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>TD</TableHead>
                <TableHead>IMT</TableHead>
                <TableHead>Diagnosa</TableHead>
                <TableHead>Risiko Jatuh</TableHead>
                <TableHead>Gangg. Kognitif</TableHead>
                <TableHead>Depresi</TableHead>
                <TableHead>Inkontinensia</TableHead>
                <TableHead>Malnutrisi</TableHead>
                <TableHead>Tujuan Rujukan</TableHead>
                <TableHead>Rekomendasi</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {visits.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={14} className="text-center py-8 text-gray-500">
                    Belum ada kunjungan
                  </TableCell>
                </TableRow>
              ) : (
                visits.map((v) => (
                  <>
                  <TableRow key={v.id}>
                    <TableCell>{v.tanggal_kunjungan}</TableCell>
                    <TableCell>{v.jam_kunjungan}</TableCell>
                    <TableCell>
                      <span className={`px-2 py-1 rounded-full text-xs font-medium ${v.jenis_kunjungan === 'baru' ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-600'}`}>
                        {v.jenis_kunjungan === 'baru' ? 'Baru' : 'Lama'}
                      </span>
                    </TableCell>
                    <TableCell>
                      {v.status_kesehatan && statusKesehatanMapping[v.status_kesehatan] ? (
                        <span className={`px-2 py-1 rounded-full text-xs font-medium ${statusKesehatanMapping[v.status_kesehatan].color}`}>
                          {statusKesehatanMapping[v.status_kesehatan].label}
                        </span>
                      ) : (
                        <span className="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">{v.status_kesehatan || '-'}</span>
                      )}
                    </TableCell>
                    <TableCell>{v.tekanan_darah_sistol}/{v.tekanan_darah_diastol}</TableCell>
                    <TableCell>
                      <div className="flex items-center gap-1">
                        <span>{v.imt || '-'}</span>
                        {v.imt && klasifikasiIMT(parseFloat(v.imt)) && (
                          <span className={`text-xs px-1.5 py-0.5 rounded ${klasifikasiIMT(parseFloat(v.imt)).color}`}>
                            {klasifikasiIMT(parseFloat(v.imt)).label}
                          </span>
                        )}
                      </div>
                    </TableCell>
                    <TableCell className="max-w-[200px] truncate">{v.diagnosa || '-'}</TableCell>
                    <TableCell>
                      <span className={`px-2 py-1 rounded-full text-xs font-medium ${skriningColors[v.skrining_risiko_jatuh] || 'bg-gray-100 text-gray-600'}`}>
                        {skriningLabels.risiko_jatuh[v.skrining_risiko_jatuh] || v.skrining_risiko_jatuh || '-'}
                      </span>
                    </TableCell>
                    <TableCell>
                      <span className={`px-2 py-1 rounded-full text-xs font-medium ${skriningColors[v.skrining_gangguan_kognitif] || 'bg-gray-100 text-gray-600'}`}>
                        {skriningLabels.gangguan_kognitif[v.skrining_gangguan_kognitif] || v.skrining_gangguan_kognitif || '-'}
                      </span>
                    </TableCell>
                    <TableCell>
                      <span className={`px-2 py-1 rounded-full text-xs font-medium ${skriningColors[v.skrining_depresi] || 'bg-gray-100 text-gray-600'}`}>
                        {skriningLabels.depresi[v.skrining_depresi] || v.skrining_depresi || '-'}
                      </span>
                    </TableCell>
                    <TableCell>
                      <span className={`px-2 py-1 rounded-full text-xs font-medium ${skriningColors[v.skrining_inkontinensia] || 'bg-gray-100 text-gray-600'}`}>
                        {skriningLabels.inkontinensia[v.skrining_inkontinensia] || v.skrining_inkontinensia || '-'}
                      </span>
                    </TableCell>
                    <TableCell>
                      <span className={`px-2 py-1 rounded-full text-xs font-medium ${skriningColors[v.skrining_malnutrisi] || 'bg-gray-100 text-gray-600'}`}>
                        {skriningLabels.malnutrisi[v.skrining_malnutrisi] || v.skrining_malnutrisi || '-'}
                      </span>
                    </TableCell>
                    <TableCell>
                      {v.tujuan_rujukan ? (
                        <div className="flex flex-wrap gap-1">
                          {v.tujuan_rujukan.split(',').map((p, i) => (
                            <span key={i} className="px-2 py-0.5 rounded-full text-xs bg-purple-100 text-purple-600">
                              {p.trim()}
                            </span>
                          ))}
                        </div>
                      ) : '-'}
                    </TableCell>
                    <TableCell>
                      {v.rekomendasi ? (
                        <span className={`px-2 py-1 rounded-full text-xs font-medium ${colorRekomendasi[v.rekomendasi] || 'bg-gray-100 text-gray-600'}`}>
                          {labelRekomendasi[v.rekomendasi] || v.rekomendasi}
                        </span>
                      ) : '-'}
                    </TableCell>
                  </TableRow>
                  <TableRow key={`${v.id}-detail`}>
                    <TableCell colSpan={14} className="pb-4 pt-0 px-4">
                      <HealthIndicator
                        td_sistol={v.tekanan_darah_sistol}
                        td_diastol={v.tekanan_darah_diastol}
                        imt={v.imt}
                        nadi={v.nadi}
                        rr={v.respiratory_rate}
                        disabilitas={v.status_disabilitas}
                        gula_darah={v.gula_darah}
                        kolesterol={v.kolesterol}
                        hemoglobin={v.hemoglobin}
                        spo2={v.spo2}
                        suhu_tubuh={v.suhu_tubuh}
                        usia={lansia ? Math.floor((new Date() - new Date(lansia.tanggal_lahir)) / (365.25 * 24 * 60 * 60 * 1000)) : 0}
                        jenis_kelamin={lansia?.jenis_kelamin || 'L'}
                      />
                    </TableCell>
                  </TableRow>
                  </>
                ))
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}