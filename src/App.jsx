import { useState, useEffect } from 'react';
import { BrowserRouter, Routes, Route, useNavigate, useLocation, Navigate } from 'react-router';
import { Sidebar } from './components/Sidebar';
import { Navbar } from './components/Navbar';
import { LoginPage } from './pages/Login';
import { DashboardPage } from './pages/Dashboard';
import { LansiaPage } from './pages/Lansia';
import { KunjunganPage } from './pages/Kunjungan';
import { LaporanPage } from './pages/Laporan';
import { RiwayatLansiaPage } from './pages/RiwayatLansia';
import { UsersPage } from './pages/Users';
import { PuskesmasPage } from './pages/Puskesmas';
import { ProfilePage } from './pages/Profile';
import { ToastProvider } from './components/Toast';
import { ActivityLogPage } from './pages/ActivityLog';
import { SettingsPage } from './pages/Settings';

function Layout({ children, user, onLogout }) {
  const location = useLocation();
  const navigate = useNavigate();
  
  return (
    <div className="min-h-screen bg-gray-50">
      <Sidebar 
        currentPath={location.pathname}
        onNavigate={(path) => navigate(path)}
        onLogout={onLogout}
        user={user}
      />
      <div className="lg:ml-64 min-h-screen flex flex-col">
        <Navbar user={user} />
        <main className="flex-1 p-6">
          {children}
        </main>
      </div>
    </div>
  );
}

function App() {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  
  useEffect(() => {
    const storedUser = localStorage.getItem('user');
    const token = localStorage.getItem('token');
    if (storedUser && token) {
      setUser(JSON.parse(storedUser));
    }
    setLoading(false);
  }, []);
  
  const handleLogin = (userData) => {
    setUser(userData);
  };
  
  const handleLogout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    setUser(null);
  };
  
  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#4A90D9]" />
      </div>
    );
  }
  
  return (
    <BrowserRouter>
      <ToastProvider>
      <Routes>
        <Route path="/login" element={<LoginPage onLogin={handleLogin} />} />
        <Route 
          path="/dashboard" 
          element={user ? (
            <Layout user={user} onLogout={handleLogout}>
              <DashboardPage />
            </Layout>
          ) : (
            <LoginPage onLogin={handleLogin} />
          )} 
        />
        <Route 
          path="/lansia" 
          element={user ? (
            <Layout user={user} onLogout={handleLogout}>
              <LansiaPage />
            </Layout>
          ) : (
            <LoginPage onLogin={handleLogin} />
          )} 
        />
        <Route 
          path="/kunjungan" 
          element={user && user.role === 'admin' ? (
            <Layout user={user} onLogout={handleLogout}>
              <KunjunganPage />
            </Layout>
          ) : user ? (
            <Navigate to="/dashboard" replace />
          ) : (
            <LoginPage onLogin={handleLogin} />
          )} 
        />
        <Route 
          path="/laporan" 
          element={user && user.role === 'super_admin' ? (
            <Layout user={user} onLogout={handleLogout}>
              <LaporanPage />
            </Layout>
          ) : (
            <LoginPage onLogin={handleLogin} />
          )} 
        />
        <Route 
          path="/lansia/riwayat/:id" 
          element={user ? (
            <Layout user={user} onLogout={handleLogout}>
              <RiwayatLansiaPage />
            </Layout>
          ) : (
            <LoginPage onLogin={handleLogin} />
          )} 
        />
        <Route 
          path="/users" 
          element={user && user.role === 'super_admin' ? (
            <Layout user={user} onLogout={handleLogout}>
              <UsersPage />
            </Layout>
          ) : (
            <LoginPage onLogin={handleLogin} />
          )} 
        />
        <Route 
          path="/puskesmas" 
          element={user && user.role === 'super_admin' ? (
            <Layout user={user} onLogout={handleLogout}>
              <PuskesmasPage />
            </Layout>
          ) : (
            <LoginPage onLogin={handleLogin} />
          )} 
        />
        <Route 
          path="/profile" 
          element={user ? (
            <Layout user={user} onLogout={handleLogout}>
              <ProfilePage />
            </Layout>
          ) : (
            <LoginPage onLogin={handleLogin} />
          )} 
        />
        <Route 
          path="/activities" 
          element={user && user.role === 'super_admin' ? (
            <Layout user={user} onLogout={handleLogout}>
              <ActivityLogPage />
            </Layout>
          ) : (
            <LoginPage onLogin={handleLogin} />
          )} 
        />
        <Route 
          path="/settings" 
          element={user && user.role === 'super_admin' ? (
            <Layout user={user} onLogout={handleLogout}>
              <SettingsPage />
            </Layout>
          ) : (
            <LoginPage onLogin={handleLogin} />
          )} 
        />
        <Route 
          path="*" 
          element={user ? (
            <Layout user={user} onLogout={handleLogout}>
              <DashboardPage />
            </Layout>
          ) : (
            <LoginPage onLogin={handleLogin} />
          )} 
        />
      </Routes>
      </ToastProvider>
    </BrowserRouter>
  );
}

export default App;
