import { createContext, useContext, useState, useEffect } from 'react';
import axios from 'axios';

const AuthContext = createContext();

export const useAuth = () => useContext(AuthContext);

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [role, setRole] = useState(null);
  const [loading, setLoading] = useState(true);
  const [dashboardData, setDashboardData] = useState({});

  useEffect(() => {
    fetchMe();
  }, []);

  const fetchMe = async () => {
    try {
      const res = await axios.get('/portal/api/me');
      setUser(res.data.data.user);
      setRole(res.data.data.user.role);
      setDashboardData(res.data.data);
    } catch (error) {
      setUser(null);
      setRole(null);
    } finally {
      setLoading(false);
    }
  };

  const login = async (role, correo, password) => {
    try {
      const res = await axios.post('/portal/api/login', { role, correo, password });
      await fetchMe();
      return res.data;
    } catch (err) {
      throw new Error(err.response?.data?.message || 'Error de login');
    }
  };

  const logout = async () => {
    await axios.post('/portal/api/logout');
    setUser(null);
    setRole(null);
    setDashboardData({});
  };

  return (
    <AuthContext.Provider value={{ user, role, dashboardData, login, logout, loading, refreshDashboard: fetchMe }}>
      {children}
    </AuthContext.Provider>
  );
};
