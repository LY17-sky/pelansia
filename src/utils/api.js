const API_URL = '/api';

async function request(endpoint, options = {}) {
  const token = localStorage.getItem('token');
  
  const config = {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      ...(token && { Authorization: `Bearer ${token}` }),
      ...options.headers,
    },
  };
  
  const response = await fetch(`${API_URL}/${endpoint}`, config);
  const data = await response.json();
  
  if (!response.ok) {
    throw new Error(data.message || 'Terjadi kesalahan');
  }
  
  return data;
}

export const api = {
  login: (credentials) => request('login', {
    method: 'POST',
    body: JSON.stringify(credentials),
  }),
  
  getLansia: (search = '') => request(`lansia?search=${encodeURIComponent(search)}`),
  
  createLansia: (data) => request('lansia', {
    method: 'POST',
    body: JSON.stringify(data),
  }),
  
  updateLansia: (id, data) => request(`lansia/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  }),
  
  deleteLansia: (id) => request(`lansia/${id}`, {
    method: 'DELETE',
  }),
  
  getVisits: (startDate, endDate) => request(`visits?start_date=${startDate}&end_date=${endDate}`),
  
  createVisit: (data) => request('visits', {
    method: 'POST',
    body: JSON.stringify(data),
  }),
  
  getDashboard: () => request('dashboard'),
  
  getVillages: () => request('villages'),
  
  getLaporan: (startDate, endDate, filters = {}) => {
    let url = `laporan?start_date=${startDate}&end_date=${endDate}`;
    if (filters.rekomendasi) url += `&rekomendasi=${filters.rekomendasi}`;
    if (filters.status_risiko) url += `&status_risiko=${filters.status_risiko}`;
    if (filters.tujuan_rujukan) url += `&tujuan_rujukan=${filters.tujuan_rujukan}`;
    return request(url);
  },
  
  getRiwayat: (id) => request(`riwayat/${id}`),

  getUsers: () => request('users'),

  createUser: (data) => request('users', {
    method: 'POST',
    body: JSON.stringify(data),
  }),

  updateUser: (id, data) => request(`users/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  }),

  deleteUser: (id) => request(`users/${id}`, {
    method: 'DELETE',
  }),

  getPuskesmas: () => request('puskesmas'),

  createPuskesmas: (data) => request('puskesmas', {
    method: 'POST',
    body: JSON.stringify(data),
  }),

  updatePuskesmas: (id, data) => request(`puskesmas/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  }),

  deletePuskesmas: (id) => request(`puskesmas/${id}`, {
    method: 'DELETE',
  }),

  getVillagesByPuskesmas: (id) => request(`villages?id_puskesmas=${id}`),

  createVillage: (data) => request('villages', {
    method: 'POST',
    body: JSON.stringify(data),
  }),

  updateVillage: (id, data) => request(`villages/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  }),

  deleteVillage: (id) => request(`villages/${id}`, {
    method: 'DELETE',
  }),

  getProfile: () => request('profile'),

  updateProfile: (data) => request('profile', {
    method: 'PUT',
    body: JSON.stringify(data),
  }),

  getActivities: (page = 1) => request(`activities?page=${page}`),

  getSettings: () => request('settings'),

  updateSettings: (data) => request('settings', {
    method: 'PUT',
    body: JSON.stringify(data),
  }),

  getHealthClassify: (params) => {
    const query = new URLSearchParams(params).toString();
    return request(`health-classify?${query}`);
  },
};
