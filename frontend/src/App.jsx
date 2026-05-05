import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './AuthContext';
import Login from './pages/Login';
import Register from './pages/Register';
import PortalLayout from './layouts/PortalLayout';

import StudentDashboard from './pages/StudentDashboard';
import StudentSchedule from './pages/StudentSchedule';
import TeacherDashboard from './pages/TeacherDashboard';
import HeadDashboard from './pages/HeadDashboard';
import './index.css';

const ProtectedRoute = ({ children, allowedRole }) => {
  const { user, role, loading } = useAuth();
  if (loading) return <div>Cargando...</div>;
  if (!user) return <Navigate to="/login" replace />;
  if (allowedRole && role !== allowedRole) return <Navigate to="/login" replace />;
  return children;
};

export default function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
          <Route path="/" element={<Navigate to="/login" replace />} />

          <Route path="/panel/estudiante" element={
            <ProtectedRoute allowedRole="estudiante">
              <StudentDashboard />
            </ProtectedRoute>
          } />

          <Route path="/panel/estudiante/horario" element={
            <ProtectedRoute allowedRole="estudiante">
              <StudentSchedule />
            </ProtectedRoute>
          } />

          <Route path="/panel/docente" element={
            <ProtectedRoute allowedRole="docente">
              <TeacherDashboard />
            </ProtectedRoute>
          } />

          <Route path="/panel/jefe-carrera" element={
            <ProtectedRoute allowedRole="jefe">
              <HeadDashboard />
            </ProtectedRoute>
          } />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}
