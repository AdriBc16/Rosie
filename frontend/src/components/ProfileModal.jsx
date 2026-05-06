import { useState, useEffect } from 'react';
import axios from 'axios';
import { useAuth } from '../AuthContext';

export default function ProfileModal({ onClose }) {
  const { user, refreshDashboard } = useAuth();

  const [form, setForm] = useState({
    nombre: '',
    apellido: '',
    password: '',
    password_confirmation: '',
  });
  const [saving, setSaving] = useState(false);
  const [feedback, setFeedback] = useState({ msg: '', ok: true });

  useEffect(() => {
    if (!user) return;
    const parts = (user.name || '').split(' ');
    setForm((p) => ({
      ...p,
      nombre: parts[0] || '',
      apellido: parts.slice(1).join(' ') || '',
    }));
  }, [user]);

  const handleSave = async (e) => {
    e.preventDefault();
    setFeedback({ msg: '', ok: true });

    if (form.password && form.password !== form.password_confirmation) {
      setFeedback({ msg: 'Las contraseñas no coinciden.', ok: false });
      return;
    }

    setSaving(true);
    try {
      const payload = {
        nombre: form.nombre.trim(),
        apellido: form.apellido.trim(),
      };
      if (form.password) {
        payload.password = form.password;
        payload.password_confirmation = form.password_confirmation;
      }
      const res = await axios.put('/portal/api/perfil', payload);
      setFeedback({ msg: res.data.message || 'Perfil actualizado.', ok: true });
      await refreshDashboard();
      setForm((p) => ({ ...p, password: '', password_confirmation: '' }));
    } catch (err) {
      setFeedback({ msg: err.response?.data?.message || 'Error al actualizar.', ok: false });
    } finally {
      setSaving(false);
    }
  };

  return (
    <div
      className="fixed inset-0 z-[200] bg-black/75 backdrop-blur-sm flex items-center justify-center p-6"
      onClick={(e) => { if (e.target === e.currentTarget) onClose(); }}
    >
      <div className="w-full max-w-md rounded-2xl border border-neutral-800 bg-[#101010] flex flex-col shadow-2xl">
        {/* Header */}
        <div className="flex items-center justify-between p-5 border-b border-neutral-800">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-xl bg-gradient-to-br from-rose-500 to-orange-400 flex items-center justify-center">
              <span className="material-symbols-outlined text-white text-lg">manage_accounts</span>
            </div>
            <div>
              <h3 className="text-white font-bold text-base">Editar Perfil</h3>
              <p className="text-[11px] text-neutral-500">{user?.email}</p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="w-8 h-8 flex items-center justify-center rounded-lg bg-neutral-900 border border-neutral-800 text-neutral-400 hover:text-white hover:border-neutral-700 transition-all"
          >
            <span className="material-symbols-outlined text-base">close</span>
          </button>
        </div>

        {/* Form */}
        <form onSubmit={handleSave} className="p-5 space-y-4">
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <label className="block text-[10px] font-bold text-rose-400 uppercase tracking-widest">Nombre</label>
              <input
                type="text"
                value={form.nombre}
                onChange={(e) => setForm((p) => ({ ...p, nombre: e.target.value }))}
                required
                placeholder="Nombre"
                className="w-full bg-neutral-900 border border-neutral-800 rounded-xl px-3 py-2.5 text-sm text-white placeholder-neutral-600 focus:outline-none focus:border-rose-500/60 focus:bg-rose-500/5 transition-all"
              />
            </div>
            <div className="space-y-1.5">
              <label className="block text-[10px] font-bold text-rose-400 uppercase tracking-widest">Apellido</label>
              <input
                type="text"
                value={form.apellido}
                onChange={(e) => setForm((p) => ({ ...p, apellido: e.target.value }))}
                placeholder="Apellido"
                className="w-full bg-neutral-900 border border-neutral-800 rounded-xl px-3 py-2.5 text-sm text-white placeholder-neutral-600 focus:outline-none focus:border-rose-500/60 focus:bg-rose-500/5 transition-all"
              />
            </div>
          </div>

          <div className="pt-2 border-t border-neutral-800/60">
            <p className="text-[10px] text-neutral-500 mb-3 uppercase font-bold tracking-widest">Cambiar contraseña <span className="text-neutral-700 normal-case font-normal">(opcional)</span></p>
            <div className="space-y-3">
              <div className="space-y-1.5">
                <label className="block text-[10px] font-bold text-neutral-400 uppercase tracking-widest">Nueva contraseña</label>
                <input
                  type="password"
                  value={form.password}
                  onChange={(e) => setForm((p) => ({ ...p, password: e.target.value }))}
                  placeholder="Mínimo 6 caracteres"
                  className="w-full bg-neutral-900 border border-neutral-800 rounded-xl px-3 py-2.5 text-sm text-white placeholder-neutral-600 focus:outline-none focus:border-rose-500/60 focus:bg-rose-500/5 transition-all"
                />
              </div>
              <div className="space-y-1.5">
                <label className="block text-[10px] font-bold text-neutral-400 uppercase tracking-widest">Confirmar contraseña</label>
                <input
                  type="password"
                  value={form.password_confirmation}
                  onChange={(e) => setForm((p) => ({ ...p, password_confirmation: e.target.value }))}
                  placeholder="Repite la contraseña"
                  className="w-full bg-neutral-900 border border-neutral-800 rounded-xl px-3 py-2.5 text-sm text-white placeholder-neutral-600 focus:outline-none focus:border-rose-500/60 focus:bg-rose-500/5 transition-all"
                />
              </div>
            </div>
          </div>

          {feedback.msg && (
            <div className={`px-3 py-2.5 rounded-xl border text-xs font-medium ${feedback.ok ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-300' : 'border-red-500/40 bg-red-500/10 text-red-300'}`}>
              {feedback.msg}
            </div>
          )}

          <div className="flex gap-3 pt-1">
            <button
              type="button"
              onClick={onClose}
              className="flex-1 py-2.5 rounded-xl text-sm font-bold text-neutral-400 bg-neutral-900 border border-neutral-800 hover:border-neutral-700 hover:text-white transition-all"
            >
              Cancelar
            </button>
            <button
              type="submit"
              disabled={saving}
              className="flex-1 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-rose-600 to-orange-500 hover:brightness-110 disabled:opacity-50 transition-all shadow-lg shadow-rose-900/20"
            >
              {saving ? 'Guardando...' : 'Guardar cambios'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
