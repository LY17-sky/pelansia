import { useEffect, useState } from 'react';
import { Plus, Edit, Trash2, Building2, MapPin } from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent } from '../components/Card';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../components/Table';
import { Button } from '../components/Button';
import { Modal } from '../components/Modal';
import { api } from '../utils/api';
import { useToast } from '../components/Toast';
import { ConfirmDialog } from '../components/ConfirmDialog';

export function PuskesmasPage() {
  const toast = useToast();
  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedPuskesmas, setSelectedPuskesmas] = useState(null);
  const [villages, setVillages] = useState([]);

  const [pkmModal, setPkmModal] = useState(false);
  const [editPkm, setEditPkm] = useState(null);
  const [pkmForm, setPkmForm] = useState({ nama_puskesmas: '', alamat: '', telepon: '', kode_puskesmas: '' });

  const [vlgModal, setVlgModal] = useState(false);
  const [editVlg, setEditVlg] = useState(null);
  const [vlgForm, setVlgForm] = useState({ nama_desa: '', kode_desa: '' });
  const [deleteConfirm, setDeleteConfirm] = useState(null);

  useEffect(() => {
    loadPuskesmas();
  }, []);

  const loadPuskesmas = async () => {
    try {
      const res = await api.getPuskesmas();
      setData(res.data);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const loadVillages = async (idPuskesmas) => {
    try {
      const res = await api.getVillagesByPuskesmas(idPuskesmas);
      setVillages(res.data);
    } catch (e) {
      console.error(e);
    }
  };

  const selectPuskesmas = (item) => {
    setSelectedPuskesmas(item);
    loadVillages(item.id);
  };

  const handlePkmSubmit = async (e) => {
    e.preventDefault();
    try {
      if (editPkm) {
        await api.updatePuskesmas(editPkm.id, pkmForm);
      } else {
        await api.createPuskesmas(pkmForm);
      }
      setPkmModal(false);
      setEditPkm(null);
      resetPkmForm();
      loadPuskesmas();
      toast(editPkm ? 'Puskesmas berhasil diperbarui' : 'Puskesmas berhasil ditambahkan', 'success');
    } catch (err) {
      toast(err.message, 'error');
    }
  };

  const handlePkmEdit = (item) => {
    setEditPkm(item);
    setPkmForm({ nama_puskesmas: item.nama_puskesmas, alamat: item.alamat || '', telepon: item.telepon || '', kode_puskesmas: item.kode_puskesmas || '' });
    setPkmModal(true);
  };

  const handlePkmDelete = async (id) => {
    const item = data.find(d => d.id === id);
    setDeleteConfirm({ type: 'puskesmas', id, name: item?.nama_puskesmas || '' });
  };

  const handleVlgDelete = async (id) => {
    const item = villages.find(v => v.id === id);
    setDeleteConfirm({ type: 'village', id, name: item?.nama_desa || '' });
  };

  const executeDelete = async () => {
    try {
      if (deleteConfirm.type === 'puskesmas') {
        await api.deletePuskesmas(deleteConfirm.id);
        if (selectedPuskesmas?.id === deleteConfirm.id) setSelectedPuskesmas(null);
        loadPuskesmas();
        toast('Puskesmas berhasil dihapus', 'success');
      } else {
        await api.deleteVillage(deleteConfirm.id);
        loadVillages(selectedPuskesmas.id);
        loadPuskesmas();
        toast('Desa berhasil dihapus', 'success');
      }
    } catch (err) {
      toast(err.message, 'error');
    }
    setDeleteConfirm(null);
  };

  const resetPkmForm = () => setPkmForm({ nama_puskesmas: '', alamat: '', telepon: '', kode_puskesmas: '' });

  const handleVlgSubmit = async (e) => {
    e.preventDefault();
    try {
      const payload = { ...vlgForm, id_puskesmas: selectedPuskesmas.id };
      if (editVlg) {
        await api.updateVillage(editVlg.id, payload);
      } else {
        await api.createVillage(payload);
      }
      setVlgModal(false);
      setEditVlg(null);
      resetVlgForm();
      loadVillages(selectedPuskesmas.id);
      loadPuskesmas();
      toast(editVlg ? 'Desa berhasil diperbarui' : 'Desa berhasil ditambahkan', 'success');
    } catch (err) {
      toast(err.message, 'error');
    }
  };

  const handleVlgEdit = (item) => {
    setEditVlg(item);
    setVlgForm({ nama_desa: item.nama_desa, kode_desa: item.kode_desa || '' });
    setVlgModal(true);
  };

  const resetVlgForm = () => setVlgForm({ nama_desa: '', kode_desa: '' });

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#4A90D9]" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-800">Manajemen Puskesmas</h1>
          <p className="text-gray-500">Kelola data puskesmas dan desa/kelurahan</p>
        </div>
        <Button variant="primary" onClick={() => { setEditPkm(null); resetPkmForm(); setPkmModal(true); }} className="flex items-center gap-2">
          <Plus className="w-4 h-4" /> Tambah Puskesmas
        </Button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Daftar Puskesmas</CardTitle>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Nama</TableHead>
                  <TableHead>Kode</TableHead>
                  <TableHead>Telepon</TableHead>
                  <TableHead>Desa</TableHead>
                  <TableHead>Aksi</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={5} className="text-center py-8 text-gray-500">Tidak ada data</TableCell>
                  </TableRow>
                ) : (
                  data.map((item) => (
                    <TableRow key={item.id} className={`cursor-pointer ${selectedPuskesmas?.id === item.id ? 'bg-blue-50 ring-2 ring-[#4A90D9]' : ''}`} onClick={() => selectPuskesmas(item)}>
                      <TableCell className="font-medium">
                        <div className="flex items-center gap-2">
                          <Building2 className="w-4 h-4 text-gray-400" />
                          {item.nama_puskesmas}
                        </div>
                      </TableCell>
                      <TableCell>{item.kode_puskesmas || '-'}</TableCell>
                      <TableCell>{item.telepon || '-'}</TableCell>
                      <TableCell><span className="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-600">{item.total_desa || 0}</span></TableCell>
                      <TableCell onClick={(e) => e.stopPropagation()}>
                        <div className="flex items-center gap-2">
                          <button onClick={() => handlePkmEdit(item)} className="p-1.5 rounded-lg bg-blue-50 text-blue-500 hover:bg-blue-100"><Edit className="w-4 h-4" /></button>
                          <button onClick={() => handlePkmDelete(item.id)} className="p-1.5 rounded-lg bg-red-50 text-red-500 hover:bg-red-100"><Trash2 className="w-4 h-4" /></button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>
              <div className="flex items-center justify-between">
                <span>Desa / Kelurahan {selectedPuskesmas ? `- ${selectedPuskesmas.nama_puskesmas}` : ''}</span>
                {selectedPuskesmas && (
                  <Button variant="primary" size="sm" onClick={() => { setEditVlg(null); resetVlgForm(); setVlgModal(true); }} className="flex items-center gap-1">
                    <Plus className="w-3 h-3" /> Tambah
                  </Button>
                )}
              </div>
            </CardTitle>
          </CardHeader>
          <CardContent>
            {!selectedPuskesmas ? (
              <div className="text-center py-12 text-gray-400">
                <MapPin className="w-12 h-12 mx-auto mb-3 opacity-50" />
                <p>Pilih puskesmas untuk melihat desa</p>
              </div>
            ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Nama Desa</TableHead>
                    <TableHead>Kode</TableHead>
                    <TableHead>Aksi</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {villages.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={3} className="text-center py-8 text-gray-500">Belum ada desa</TableCell>
                    </TableRow>
                  ) : (
                    villages.map((v) => (
                      <TableRow key={v.id}>
                        <TableCell className="font-medium">{v.nama_desa}</TableCell>
                        <TableCell>{v.kode_desa || '-'}</TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <button onClick={() => handleVlgEdit(v)} className="p-1.5 rounded-lg bg-blue-50 text-blue-500 hover:bg-blue-100"><Edit className="w-4 h-4" /></button>
                            <button onClick={() => handleVlgDelete(v.id)} className="p-1.5 rounded-lg bg-red-50 text-red-500 hover:bg-red-100"><Trash2 className="w-4 h-4" /></button>
                          </div>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            )}
          </CardContent>
        </Card>
      </div>

      <Modal isOpen={pkmModal} onClose={() => setPkmModal(false)} title={editPkm ? 'Edit Puskesmas' : 'Tambah Puskesmas'}>
        <form onSubmit={handlePkmSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Nama Puskesmas</label>
            <input type="text" value={pkmForm.nama_puskesmas} onChange={(e) => setPkmForm({ ...pkmForm, nama_puskesmas: e.target.value })}
              className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500" required />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Kode Puskesmas</label>
            <input type="text" value={pkmForm.kode_puskesmas} onChange={(e) => setPkmForm({ ...pkmForm, kode_puskesmas: e.target.value })}
              className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
            <textarea value={pkmForm.alamat} onChange={(e) => setPkmForm({ ...pkmForm, alamat: e.target.value })}
              className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500" rows={2} />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Telepon</label>
            <input type="text" value={pkmForm.telepon} onChange={(e) => setPkmForm({ ...pkmForm, telepon: e.target.value })}
              className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div className="flex gap-3 pt-4">
            <Button type="submit" variant="success" className="flex-1">Simpan</Button>
            <Button type="button" variant="secondary" onClick={() => setPkmModal(false)}>Batal</Button>
          </div>
        </form>
      </Modal>

      <Modal isOpen={vlgModal} onClose={() => setVlgModal(false)} title={editVlg ? 'Edit Desa' : 'Tambah Desa'}>
        <form onSubmit={handleVlgSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Nama Desa / Kelurahan</label>
            <input type="text" value={vlgForm.nama_desa} onChange={(e) => setVlgForm({ ...vlgForm, nama_desa: e.target.value })}
              className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500" required />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Kode Desa</label>
            <input type="text" value={vlgForm.kode_desa} onChange={(e) => setVlgForm({ ...vlgForm, kode_desa: e.target.value })}
              className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div className="flex gap-3 pt-4">
            <Button type="submit" variant="success" className="flex-1">Simpan</Button>
            <Button type="button" variant="secondary" onClick={() => setVlgModal(false)}>Batal</Button>
          </div>
        </form>
      </Modal>
      
      <ConfirmDialog
        isOpen={!!deleteConfirm}
        title={deleteConfirm?.type === 'puskesmas' ? 'Hapus Puskesmas' : 'Hapus Desa'}
        message={`Yakin ingin menghapus ${deleteConfirm?.type === 'puskesmas' ? 'puskesmas' : 'desa'} ${deleteConfirm?.name}?`}
        detail={deleteConfirm?.type === 'puskesmas' ? 'Semua desa di dalamnya juga akan dihapus.' : ''}
        onConfirm={executeDelete}
        onCancel={() => setDeleteConfirm(null)}
      />
    </div>
  );
}
