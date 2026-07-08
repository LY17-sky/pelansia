import { useEffect, useState } from 'react';
import { Download, Search } from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent } from '../components/Card';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../components/Table';
import { Button } from '../components/Button';
import { api } from '../utils/api';
import { useToast } from '../components/Toast';
import { statusKesehatanMapping, hitungKategoriLansia, hitungUsia, klasifikasiIMT } from '../utils/constants';

const labelRisiko = { risiko_rendah: 'Risiko Rendah', risiko_sedang: 'Risiko Sedang', risiko_tinggi: 'Risiko Tinggi' };
const colorRisiko = { risiko_rendah: 'bg-green-100 text-green-700', risiko_sedang: 'bg-amber-100 text-amber-700', risiko_tinggi: 'bg-red-100 text-red-700' };
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

export function LaporanPage() {
  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(false);
  const [startDate, setStartDate] = useState(() => {
    const date = new Date();
    date.setDate(1);
    return date.toISOString().split('T')[0];
  });
  const [endDate, setEndDate] = useState(new Date().toISOString().split('T')[0]);
  const [filterRekomendasi, setFilterRekomendasi] = useState('');
  const [filterRisiko, setFilterRisiko] = useState('');
  const toast = useToast();
  
  useEffect(() => {
    loadLaporan();
  }, [startDate, endDate]);
  
  const loadLaporan = async () => {
    setLoading(true);
    try {
      const response = await api.getLaporan(startDate, endDate, {
        rekomendasi: filterRekomendasi,
        status_risiko: filterRisiko,
      });
      setData(response.data);
    } catch (error) {
      console.error('Failed to load laporan:', error);
    } finally {
      setLoading(false);
    }
  };
  
  const exportToCSV = () => {
    const headers = ['Nama Lansia', 'NIK', 'Kategori', 'Tanggal', 'Status Kesehatan', 'TD', 'IMT', 'Nadi', 'RR', 'Risiko Jatuh', 'Gangg. Kognitif', 'Depresi', 'Inkontinensia', 'Malnutrisi'];
    const rows = data.map(item => [
      item.nama_lengkap,
      item.nik,
      hitungKategoriLansia(item.usia ? parseInt(item.usia) : (item.kategori_lansia === 'lansia_utama' ? 70 : item.kategori_lansia === 'lansia' ? 65 : 50)).label,
      item.tanggal_kunjungan,
      (item.status_kesehatan && statusKesehatanMapping[item.status_kesehatan]?.label) || item.status_kesehatan || '-',
      `${item.tekanan_darah_sistol || ''}/${item.tekanan_darah_diastol || ''}`,
      item.imt || '-',
      item.nadi || '-',
      item.respiratory_rate || '-',
      (item.skrining_risiko_jatuh && skriningLabels.risiko_jatuh[item.skrining_risiko_jatuh]) || item.skrining_risiko_jatuh || '-',
      (item.skrining_gangguan_kognitif && skriningLabels.gangguan_kognitif[item.skrining_gangguan_kognitif]) || item.skrining_gangguan_kognitif || '-',
      (item.skrining_depresi && skriningLabels.depresi[item.skrining_depresi]) || item.skrining_depresi || '-',
      (item.skrining_inkontinensia && skriningLabels.inkontinensia[item.skrining_inkontinensia]) || item.skrining_inkontinensia || '-',
      (item.skrining_malnutrisi && skriningLabels.malnutrisi[item.skrining_malnutrisi]) || item.skrining_malnutrisi || '-',
    ]);
    
    const csvContent = [headers, ...rows]
      .map(row => row.join(','))
      .join('\n');
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `laporan_lansia_${startDate}_${endDate}.csv`;
    link.click();
    api.createNotification({ type: 'laporan_terkirim', title: 'Laporan Terkirim', message: `Laporan CSV ${startDate} s.d ${endDate} berhasil diexport` }).catch(() => {});
  };
  
  const exportToExcel = () => {
    const headers = ['Nama Lansia', 'NIK', 'Kategori', 'Tanggal', 'Status Kesehatan', 'TD Sistol', 'TD Diastol', 'IMT', 'Nadi', 'RR', 'Risiko Jatuh', 'Gangg. Kognitif', 'Depresi', 'Inkontinensia', 'Malnutrisi'];
    const rows = data.map(item => [
      item.nama_lengkap,
      item.nik,
      hitungKategoriLansia(item.usia ? parseInt(item.usia) : (item.kategori_lansia === 'lansia_utama' ? 70 : item.kategori_lansia === 'lansia' ? 65 : 50)).label,
      item.tanggal_kunjungan,
      (item.status_kesehatan && statusKesehatanMapping[item.status_kesehatan]?.label) || item.status_kesehatan || '-',
      item.tekanan_darah_sistol || '-',
      item.tekanan_darah_diastol || '-',
      item.imt || '-',
      item.nadi || '-',
      item.respiratory_rate || '-',
      (item.skrining_risiko_jatuh && skriningLabels.risiko_jatuh[item.skrining_risiko_jatuh]) || item.skrining_risiko_jatuh || '-',
      (item.skrining_gangguan_kognitif && skriningLabels.gangguan_kognitif[item.skrining_gangguan_kognitif]) || item.skrining_gangguan_kognitif || '-',
      (item.skrining_depresi && skriningLabels.depresi[item.skrining_depresi]) || item.skrining_depresi || '-',
      (item.skrining_inkontinensia && skriningLabels.inkontinensia[item.skrining_inkontinensia]) || item.skrining_inkontinensia || '-',
      (item.skrining_malnutrisi && skriningLabels.malnutrisi[item.skrining_malnutrisi]) || item.skrining_malnutrisi || '-',
    ]);
    
    let html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="UTF-8"></head><body>';
    html += '<table border="1">';
    html += '<tr>' + headers.map(h => `<th>${h}</th>`).join('') + '</tr>';
    rows.forEach(row => {
      html += '<tr>' + row.map(cell => `<td>${cell}</td>`).join('') + '</tr>';
    });
    html += '</table></body></html>';
    
    const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `laporan_lansia_${startDate}_${endDate}.xls`;
    link.click();
    api.createNotification({ type: 'laporan_terkirim', title: 'Laporan Terkirim', message: `Laporan Excel ${startDate} s.d ${endDate} berhasil diexport` }).catch(() => {});
  };
  
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-800">Laporan</h1>
        <p className="text-gray-500">Laporan data kunjungan lansia</p>
      </div>
      
      <Card>
        <CardHeader>
          <CardTitle>Filter Tanggal</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex flex-col sm:flex-row gap-4 items-end flex-wrap">
            <div className="flex-1 min-w-[150px]">
              <label className="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
              <input type="date" value={startDate} onChange={(e) => setStartDate(e.target.value)}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]" />
            </div>
            <div className="flex-1 min-w-[150px]">
              <label className="block text-sm font-medium text-gray-700 mb-1">End Date</label>
              <input type="date" value={endDate} onChange={(e) => setEndDate(e.target.value)}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]" />
            </div>
            <div className="flex-1 min-w-[150px]">
              <label className="block text-sm font-medium text-gray-700 mb-1">Rekomendasi</label>
              <select value={filterRekomendasi} onChange={(e) => setFilterRekomendasi(e.target.value)}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]">
                <option value="">Semua</option>
                <option value="pemeriksaan_biasa">Pemeriksaan Umum</option>
                <option value="rawat_jalan">Rawat Jalan</option>
                <option value="rawat_inap">Rawat Inap</option>
                <option value="rujuk_rs">Rujuk RS</option>
              </select>
            </div>
            <div className="flex-1 min-w-[150px]">
              <label className="block text-sm font-medium text-gray-700 mb-1">Status Risiko</label>
              <select value={filterRisiko} onChange={(e) => setFilterRisiko(e.target.value)}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]">
                <option value="">Semua</option>
                <option value="risiko_rendah">Risiko Rendah</option>
                <option value="risiko_sedang">Risiko Sedang</option>
                <option value="risiko_tinggi">Risiko Tinggi</option>
              </select>
            </div>
            <Button variant="primary" onClick={loadLaporan} className="flex items-center gap-2">
              <Search className="w-4 h-4" /> Cari
            </Button>
          </div>
        </CardContent>
      </Card>
      
      <div className="flex gap-3">
        <Button 
          variant="success" 
          onClick={exportToCSV}
          disabled={data.length === 0}
          className="flex items-center gap-2"
        >
          <Download className="w-4 h-4" />
          Export CSV
        </Button>
        <Button 
          variant="success" 
          onClick={exportToExcel}
          disabled={data.length === 0}
          className="flex items-center gap-2"
        >
          <Download className="w-4 h-4" />
          Export Excel
        </Button>
      </div>
      
      <Card>
        {loading ? (
          <div className="flex items-center justify-center h-64">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#4A90D9]" />
          </div>
        ) : (
          <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Nama Lansia</TableHead>
                  <TableHead>Kategori</TableHead>
                  <TableHead>Tanggal</TableHead>
                  <TableHead>Status Kesehatan</TableHead>
                  <TableHead>TD</TableHead>
                  <TableHead>IMT</TableHead>
                  <TableHead>Nadi</TableHead>
                  <TableHead>RR</TableHead>
                  <TableHead>Risiko Jatuh</TableHead>
                  <TableHead>Gangg. Kognitif</TableHead>
                  <TableHead>Depresi</TableHead>
                  <TableHead>Inkontinensia</TableHead>
                  <TableHead>Malnutrisi</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={13} className="text-center py-8 text-gray-500">
                      Tidak ada data
                    </TableCell>
                  </TableRow>
                ) : (
                  data.map((item, index) => (
                    <TableRow key={index}>
                      <TableCell className="font-medium">{item.nama_lengkap}</TableCell>
                      <TableCell>
                        {item.kategori_lansia ? (
                          <span className={`inline-block px-2 py-1 rounded-full text-xs font-medium ${hitungKategoriLansia(item.kategori_lansia === 'lansia_utama' ? 70 : item.kategori_lansia === 'lansia' ? 65 : 50).color}`}>
                            {hitungKategoriLansia(item.kategori_lansia === 'lansia_utama' ? 70 : item.kategori_lansia === 'lansia' ? 65 : 50).label}
                          </span>
                        ) : item['usia'] ? (
                          <span className={`inline-block px-2 py-1 rounded-full text-xs font-medium ${hitungKategoriLansia(parseInt(item.usia)).color}`}>
                            {hitungKategoriLansia(parseInt(item.usia)).label}
                          </span>
                        ) : '-'}
                      </TableCell>
                      <TableCell>{item.tanggal_kunjungan}</TableCell>
                      <TableCell>
                        {item.status_kesehatan && statusKesehatanMapping[item.status_kesehatan] ? (
                          <span className={`px-2 py-1 rounded-full text-xs font-medium ${statusKesehatanMapping[item.status_kesehatan].color}`}>
                            {statusKesehatanMapping[item.status_kesehatan].label}
                          </span>
                        ) : (
                          <span className="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                            {item.status_kesehatan || '-'}
                          </span>
                        )}
                      </TableCell>
                      <TableCell>{item.tekanan_darah_sistol}/{item.tekanan_darah_diastol}</TableCell>
                      <TableCell>
                        <span>{item.imt || '-'}</span>
                        {item.imt && klasifikasiIMT(parseFloat(item.imt)) && (
                          <span className={`ml-1 text-xs px-1.5 py-0.5 rounded ${klasifikasiIMT(parseFloat(item.imt)).color}`}>
                            {klasifikasiIMT(parseFloat(item.imt)).label}
                          </span>
                        )}
                      </TableCell>
                      <TableCell>{item.nadi || '-'}</TableCell>
                      <TableCell>{item.respiratory_rate || '-'}</TableCell>
                      <TableCell>
                        <span className={`px-2 py-1 rounded-full text-xs font-medium ${skriningColors[item.skrining_risiko_jatuh] || 'bg-gray-100 text-gray-600'}`}>
                          {skriningLabels.risiko_jatuh[item.skrining_risiko_jatuh] || item.skrining_risiko_jatuh || '-'}
                        </span>
                      </TableCell>
                      <TableCell>
                        <span className={`px-2 py-1 rounded-full text-xs font-medium ${skriningColors[item.skrining_gangguan_kognitif] || 'bg-gray-100 text-gray-600'}`}>
                          {skriningLabels.gangguan_kognitif[item.skrining_gangguan_kognitif] || item.skrining_gangguan_kognitif || '-'}
                        </span>
                      </TableCell>
                      <TableCell>
                        <span className={`px-2 py-1 rounded-full text-xs font-medium ${skriningColors[item.skrining_depresi] || 'bg-gray-100 text-gray-600'}`}>
                          {skriningLabels.depresi[item.skrining_depresi] || item.skrining_depresi || '-'}
                        </span>
                      </TableCell>
                      <TableCell>
                        <span className={`px-2 py-1 rounded-full text-xs font-medium ${skriningColors[item.skrining_inkontinensia] || 'bg-gray-100 text-gray-600'}`}>
                          {skriningLabels.inkontinensia[item.skrining_inkontinensia] || item.skrining_inkontinensia || '-'}
                        </span>
                      </TableCell>
                      <TableCell>
                        <span className={`px-2 py-1 rounded-full text-xs font-medium ${skriningColors[item.skrining_malnutrisi] || 'bg-gray-100 text-gray-600'}`}>
                          {skriningLabels.malnutrisi[item.skrining_malnutrisi] || item.skrining_malnutrisi || '-'}
                        </span>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
          </Table>
        )}
      </Card>
    </div>
  );
}
