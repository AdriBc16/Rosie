import { useState } from 'react';
import { useAuth } from '../AuthContext';
import { useNavigate, Link } from 'react-router-dom';
import logoRosie from '../assets/rosie.png';
export default function Login() {
  const [role, setRole] = useState('docente');
  const [correo, setCorreo] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    try {
      const data = await login(role, correo, password);
      if (data.role === 'jefe') navigate('/panel/jefe-carrera');
      else if (data.role === 'docente') navigate('/panel/docente');
      else navigate('/panel/estudiante');
    } catch (err) {
      setError(err.message);
    }
  };

  return (
    <main className="min-h-screen relative flex items-center justify-center p-6 md:p-12 overflow-hidden bg-[#020202] text-[#f6dddc]">
      <div className="absolute top-[-10%] right-[-10%] w-[500px] h-[500px] rounded-full bg-[#ff5f6d] opacity-5 blur-[150px]" />
      <div className="absolute bottom-[-10%] left-[-10%] w-[400px] h-[400px] rounded-full bg-[#ffc371] opacity-5 blur-[150px]" />

      <div className="w-full max-w-5xl grid grid-cols-1 lg:grid-cols-2 gap-6 items-center relative z-10">
        <div className="hidden lg:block space-y-6">
          <h1 className="font-h1 text-5xl text-[#f6dddc] leading-tight font-extrabold">
            Optimiza tu <br />
            <span className="text-transparent bg-clip-text bg-gradient-to-r from-[#ff5f6d] to-[#ffc371]">aprendizaje</span>
          </h1>
          <p className="text-lg text-[#a88989] max-w-md">
            Accede a tu panel personalizado para gestionar examenes, revisar materiales y conectar con tu comunidad educativa.
          </p>
          <div className="relative w-full aspect-[4/3] overflow-hidden flex items-center justify-center">
            {/* <img
              alt="Portal Educativo Rosie"
              className="w-full h-full object-cover grayscale-[0.2] hover:grayscale-0 transition-all duration-700"
              src="https://lh3.googleusercontent.com/aida/ADBb0ujp1UHy7YCFpBwwWncHWfm09nvIVfxJ6YragUh6mYvRNQlGp7evCk_dO5xV266lPmCD3UgfhqCi7FY8lHy9BFfR1jYr72ixGT_NODgAeGReNXZW2qHyXPx38Le4EE_569zAP2fkEKDHx1PO4vs30Z26qmE9iR8r49xFNnZEYvW3e4CDZlJgwJKFWjb36Uk-h9WX1L9MLhglmWJCUKNLjJvIfocQs7WA8t0uxA2AZIW7_rhA3FjV-5XyToeq4F8bAzmfVfiaQsvPWA"
            /> */}
            <img
              alt="Portal Educativo Rosie"
              className="w-full h-full object-contain transition-all duration-700 hover:scale-105"
              src={logoRosie}
            />
            <div className="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent" />
          </div>
        </div>

        <div className="flex justify-center">
          <div className="w-full max-w-[440px] glass-panel rounded-[32px] p-8 shadow-2xl relative">
            <div className="space-y-6">
              <div className="text-center lg:text-left">
                <h2 className="font-h2 text-3xl text-white font-bold">Bienvenido de nuevo</h2>
                <p className="text-base text-gray-400">Ingresa tus credenciales para continuar.</p>
              </div>

              <div className="grid grid-cols-3 gap-2 p-1.5 bg-black rounded-2xl border border-[#2d2d2d]">
                {['docente', 'estudiante', 'jefe'].map((r) => (
                  <button
                    key={r}
                    type="button"
                    onClick={() => setRole(r)}
                    className={`flex items-center justify-center gap-2 py-2.5 rounded-xl font-bold text-sm transition-all ${role === r ? 'bg-white/5 text-white border border-white/10' : 'text-gray-400 hover:text-white'}`}
                  >
                    <span className="material-symbols-outlined text-[18px]">{r === 'docente' ? 'school' : r === 'estudiante' ? 'person' : 'badge'}</span>
                    {r === 'docente' ? 'Docente' : r === 'estudiante' ? 'Estudiante' : 'Jefe'}
                  </button>
                ))}
              </div>

              {error && (
                <div className="rounded-xl border border-red-500/40 bg-red-500/10 text-red-300 text-sm px-4 py-3">
                  {error}
                </div>
              )}

              <form className="space-y-5" onSubmit={handleSubmit}>
                <div className="space-y-2">
                  <label className="block text-xs font-bold tracking-[0.1em] text-[#ff5f6d] ml-1">CORREO ELECTRONICO</label>
                  <div className="relative group">
                    <input
                      className="w-full bg-[#121212] border border-[#2d2d2d] rounded-xl px-4 py-4 text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-[#ff5f6d] transition-all"
                      placeholder="nombre@ejemplo.com"
                      type="email"
                      value={correo}
                      onChange={(e) => setCorreo(e.target.value)}
                      required
                    />
                    <span className="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-gray-600 group-focus-within:text-[#ff5f6d] transition-colors">alternate_email</span>
                  </div>
                </div>

                <div className="space-y-2">
                  <div className="flex justify-between items-center px-1">
                    <label className="text-xs font-bold tracking-[0.1em] text-[#ff5f6d]">CONTRASENA</label>
                    <span className="text-[11px] font-bold text-gray-500">Password default: UPB123</span>
                  </div>
                  <div className="relative group">
                    <input
                      className="w-full bg-[#121212] border border-[#2d2d2d] rounded-xl px-4 py-4 text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-[#ff5f6d] transition-all"
                      placeholder="........"
                      type="password"
                      value={password}
                      onChange={(e) => setPassword(e.target.value)}
                      required
                    />
                    <span className="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-gray-600 group-focus-within:text-[#ff5f6d] transition-colors">lock_open</span>
                  </div>
                </div>

                <button className="w-full bg-gradient-to-r from-[#ff5f6d] to-[#ffc371] hover:brightness-110 active:scale-[0.98] transition-all py-4 rounded-xl text-white font-extrabold text-base shadow-[0_8px_30px_rgb(255,95,109,0.3)]" type="submit">
                  Iniciar Sesion
                </button>
              </form>

              <div className="pt-4 text-center">
                <p className="text-xs text-gray-500">
                  Eres nuevo en Rosie?{' '}
                  <Link className="text-white font-bold hover:underline" to="/register">
                    Crea una cuenta
                  </Link>
                </p>
              </div>
            </div>
            <div className="absolute -bottom-10 -right-10 w-32 h-32 bg-[#ff5f6d]/5 blur-[80px] rounded-full" />
          </div>
        </div>
      </div>
    </main>
  );
}

