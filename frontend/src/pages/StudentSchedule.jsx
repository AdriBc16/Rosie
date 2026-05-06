import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';

export default function StudentSchedule() {
  const navigate = useNavigate();
  useEffect(() => {
    navigate('/panel/estudiante', { replace: true });
  }, [navigate]);
  return null;
}
