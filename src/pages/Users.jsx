import { useEffect, useState } from 'react';
import { Plus, Edit, Trash2, Search } from 'lucide-react';
import { Card } from '../components/Card';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../components/Table';
import { Button } from '../components/Button';
import { Modal } from '../components/Modal';
import { api } from '../utils/api';
import { useToast } from '../components/Toast';
import { ConfirmDialog } from '../components/ConfirmDialog';

const roleLabels = {
  super_admin: 'Super Admin',
  admin: 'Admin',
};

const roleColors = {
  super_admin: 'bg-purple-100 text-purple-700',
  admin: 'bg-blue-100 text-blue-700',
};

export function UsersPage() {
  const toast = useToast();
  const [users, setUsers] = useState([]);
  const [puskesmas, setPuskesmas] = useState([]);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [editData, setEditData] = useState(null);
  const [deleteConfirm, setDeleteConfirm] = useState(null);
  const [formData, setFormData] = useState({
    username: '',
    password: '',
    nama_lengkap: '',
    email: '',
      role: 'admin',
    id_puskesmas: '',
    status: 'active',
  });

  useEffect(() => {
    loadUsers();
    loadPuskesmas();
  }, []);

  useEffect(() => {
    const timer = setTimeout(() => loadUsers(search), 300);
    return () => clearTimeout(timer);
  }, [search]);

  const loadUsers = async (q = '') => {
    try {
      const res = await api.getUsers();
      const filtered = q ? res.data.filter(u =>
        u.nama_lengkap.toLowerCase().includes(q.toLowerCase()) ||
        u.username.toLowerCase().includes(q.toLowerCase())
      ) : res.data;
      setUsers(filtered);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const loadPuskesmas = async () => {
    try {
      const res = await api.getPuskesmas();
      setPuskesmas(res.data);
    } catch (e) {
      console.error(e);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      if (editData) {
        await api.updateUser(editData.id, formData);
      } else {
        await api.createUser(formData);
      }
      setModalOpen(false);
      loadUsers();
      toast(editData ? 'User berhasil diperbarui' : 'User berhasil ditambahkan', 'success');
    } catch (err) {
      toast(err.message, 'error');
    }
  };

  const handleEdit = (item) => {
    setEditData(item);
    setFormData({
      username: item.username,
      password: '',
      nama_lengkap: item.nama_lengkap,
      email: item.email || '',
      role: item.role,
      id_puskesmas: item.id_puskesmas || '',
      status: item.status,
    });
    setModalOpen(true);
  };

  const handleDelete = async (id) => {
    const item = users.find(u => u.id === id);
    setDeleteConfirm({ id, name: item?.nama_lengkap || '' });
  };

  const executeDelete = async () => {
    try {
      await api.deleteUser(deleteConfirm.id);
      loadUsers();
      toast('User berhasil dinonaktifkan', 'success');
    } catch (err) {
      toast(err.message, 'error');
    }
    setDeleteConfirm(null);
  };

  const resetForm = () => {
    setFormData({
      username: '',
      password: '',
      nama_lengkap: '',
      email: '',
    role: 'admin',
      id_puskesmas: '',
      status: 'active',
    });
  };

  const openCreate = () => {
      setModalOpen(false);
      setEditData(null);
    resetForm();
    setModalOpen(true);
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-800">Pengguna Sistem</h1>
          <p className="text-gray-500">Kelola pengguna sistem</p>
        </div>
        <Button variant="primary" onClick={openCreate} className="flex items-center gap-2">
          <Plus className="w-4 h-4" /> Tambah User
        </Button>
      </div>

      <Card>
        <div className="mb-4">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
              placeholder="Cari nama atau username..."
            />
          </div>
        </div>

        {loading ? (
          <div className="flex items-center justify-center h-64">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500" />
          </div>
        ) : (
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Username</TableHead>
                <TableHead>Nama Lengkap</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Role</TableHead>
                <TableHead>Puskesmas</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Aksi</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {users.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={7} className="text-center py-8 text-gray-500">
                    Tidak ada data
                  </TableCell>
                </TableRow>
              ) : (
                users.map((item) => (
                  <TableRow key={item.id}>
                    <TableCell className="font-medium">{item.username}</TableCell>
                    <TableCell>{item.nama_lengkap}</TableCell>
                    <TableCell>{item.email || '-'}</TableCell>
                    <TableCell>
                      <span className={`px-2 py-1 rounded-full text-xs font-medium ${roleColors[item.role] || 'bg-gray-100 text-gray-600'}`}>
                        {roleLabels[item.role] || item.role}
                      </span>
                    </TableCell>
                    <TableCell>{item.nama_puskesmas || '-'}</TableCell>
                    <TableCell>
                      <span className={`px-2 py-1 rounded-full text-xs font-medium ${item.status === 'active' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'}`}>
                        {item.status === 'active' ? 'Aktif' : 'Nonaktif'}
                      </span>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <button onClick={() => handleEdit(item)} className="p-1.5 rounded-lg bg-blue-50 text-blue-500 hover:bg-blue-100">
                          <Edit className="w-4 h-4" />
                        </button>
                        <button onClick={() => handleDelete(item.id)} className="p-1.5 rounded-lg bg-red-50 text-red-500 hover:bg-red-100">
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        )}
      </Card>

      <Modal isOpen={modalOpen} onClose={() => setModalOpen(false)} title={editData ? 'Edit User' : 'Tambah User'} size="lg">
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Username</label>
              <input type="text" value={formData.username} onChange={(e) => setFormData({ ...formData, username: e.target.value })}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]" required />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Password {editData && <span className="text-gray-400 text-xs">(kosongkan jika tidak diubah)</span>}</label>
              <input type="password" value={formData.password} onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]"
                required={!editData} />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
              <input type="text" value={formData.nama_lengkap} onChange={(e) => setFormData({ ...formData, nama_lengkap: e.target.value })}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]" required />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <input type="email" value={formData.email} onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]" />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Role</label>
              <select value={formData.role} onChange={(e) => setFormData({ ...formData, role: e.target.value })}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]">
                <option value="super_admin">Super Admin</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Puskesmas</label>
              <select value={formData.id_puskesmas} onChange={(e) => setFormData({ ...formData, id_puskesmas: e.target.value })}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]">
                <option value="">Pilih Puskesmas</option>
                {puskesmas.map((p) => (
                  <option key={p.id} value={p.id}>{p.nama_puskesmas}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
              <select value={formData.status} onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#4A90D9]">
                <option value="active">Aktif</option>
                <option value="inactive">Nonaktif</option>
              </select>
            </div>
          </div>
          <div className="flex gap-3 pt-4">
            <Button type="submit" variant="success" className="flex-1">Simpan</Button>
            <Button type="button" variant="secondary" onClick={() => setModalOpen(false)}>Batal</Button>
          </div>
        </form>
      </Modal>
      
      <ConfirmDialog
        isOpen={!!deleteConfirm}
        title="Nonaktifkan User"
        message={`Yakin ingin menonaktifkan user ${deleteConfirm?.name}?`}
        detail="User tidak akan bisa login lagi."
        onConfirm={executeDelete}
        onCancel={() => setDeleteConfirm(null)}
      />
    </div>
  );
}
