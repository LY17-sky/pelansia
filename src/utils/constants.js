export function hitungUsia(tanggal_lahir) {
  if (!tanggal_lahir) return 0;
  return Math.floor((new Date() - new Date(tanggal_lahir)) / (365.25 * 24 * 60 * 60 * 1000));
}

export function hitungKategoriLansia(usia) {
  if (usia >= 70) return { key: 'lansia_utama', label: 'Lansia Tua', color: 'bg-orange-100 text-orange-700' };
  if (usia >= 60) return { key: 'lansia', label: 'Lansia', color: 'bg-purple-100 text-purple-600' };
  return { key: 'pra_lansia', label: 'Pra Lansia', color: 'bg-blue-100 text-blue-600' };
}

export function klasifikasiIMT(imt) {
  if (!imt || imt <= 0) return null;
  if (imt < 18.5) return { label: 'Kurus', color: 'bg-yellow-100 text-yellow-600' };
  if (imt < 25) return { label: 'Normal', color: 'bg-green-100 text-green-600' };
  if (imt < 30) return { label: 'Gemuk', color: 'bg-orange-100 text-orange-600' };
  return { label: 'Obesitas', color: 'bg-red-100 text-red-600' };
}

export const statusKesehatanMapping = {
  sehat: { label: 'Sehat', color: 'bg-green-100 text-green-600' },
  sakit_ringan: { label: 'Sakit Ringan', color: 'bg-yellow-100 text-yellow-600' },
  sakit_berat: { label: 'Sakit Berat', color: 'bg-red-100 text-red-600' },
};

export const POLI_INTERNAL = [
  'Poli Umum', 'Poli Gigi', 'Poli KIA', 'Poli Kandungan',
  'Poli Anak', 'Poli Mata', 'Poli THT', 'Poli Kulit',
  'Poli Syaraf', 'Poli Jiwa', 'Poli Fisioterapi',
  'Laboratorium', 'Konseling', 'IGD'
];
