import { useEffect, useState } from 'react';
import api from './services/api';

function App() {
  const [apiStatus, setApiStatus] = useState({
    status: 'checking', // 'checking' | 'connected' | 'disconnected'
    message: 'Memeriksa koneksi ke backend...',
    data: null,
    checkedAt: null,
  });

  const checkHealth = async () => {
    setApiStatus((prev) => ({ ...prev, status: 'checking', message: 'Menghubungkan ke API...' }));
    try {
      const response = await api.get('/health');
      if (response.data && response.data.success) {
        setApiStatus({
          status: 'connected',
          message: response.data.message || 'FinToko API is running',
          data: response.data.data,
          checkedAt: new Date().toLocaleTimeString(),
        });
      } else {
        throw new Error('Respons tidak valid');
      }
    } catch (error) {
      setApiStatus({
        status: 'disconnected',
        message: error.message || 'Gagal terhubung ke backend',
        data: null,
        checkedAt: new Date().toLocaleTimeString(),
      });
    }
  };

  useEffect(() => {
    checkHealth();
  }, []);

  return (
    <div className="min-h-screen bg-slate-900 text-slate-100 flex flex-col justify-between p-6">
      <header className="max-w-4xl mx-auto w-full pt-10 text-center">
        <div className="inline-flex items-center gap-2 px-3 py-1 bg-emerald-500/10 border border-emerald-500/30 rounded-full text-emerald-400 text-xs font-medium mb-4">
          <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          Tahap 1 — Project Foundation
        </div>
        <h1 className="text-4xl md:text-5xl font-extrabold tracking-tight bg-gradient-to-r from-emerald-400 via-teal-300 to-cyan-400 bg-clip-text text-transparent">
          FinToko
        </h1>
        <p className="mt-2 text-lg text-slate-400 font-medium">
          Financial Management System
        </p>
      </header>

      <main className="max-w-xl mx-auto w-full my-8">
        <div className="bg-slate-800/80 backdrop-blur border border-slate-700/80 rounded-2xl shadow-xl p-6 sm:p-8">
          <div className="flex items-center justify-between pb-4 border-b border-slate-700">
            <div>
              <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">
                Status Koneksi API
              </h2>
              <p className="text-xs text-slate-500 mt-0.5">
                Target: <code className="bg-slate-900 px-1.5 py-0.5 rounded text-cyan-300">{import.meta.env.VITE_API_BASE_URL || '/api/v1'}</code>
              </p>
            </div>
            {apiStatus.status === 'connected' && (
              <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                <span className="w-2 h-2 rounded-full bg-emerald-400"></span>
                Connected
              </span>
            )}
            {apiStatus.status === 'disconnected' && (
              <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                <span className="w-2 h-2 rounded-full bg-rose-400"></span>
                Disconnected
              </span>
            )}
            {apiStatus.status === 'checking' && (
              <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                <span className="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
                Checking...
              </span>
            )}
          </div>

          <div className="mt-6 space-y-4">
            <div className="bg-slate-900/60 rounded-xl p-4 border border-slate-700/50">
              <div className="text-xs text-slate-400 mb-1">Pesan Sistem:</div>
              <div className="text-sm font-mono text-slate-200">
                {apiStatus.message}
              </div>
              {apiStatus.checkedAt && (
                <div className="text-[11px] text-slate-500 mt-2">
                  Terakhir dicek: {apiStatus.checkedAt}
                </div>
              )}
            </div>

            {apiStatus.data && (
              <div className="bg-slate-900/60 rounded-xl p-4 border border-slate-700/50 grid grid-cols-2 gap-4">
                <div>
                  <div className="text-xs text-slate-400">Aplikasi</div>
                  <div className="text-sm font-semibold text-slate-200 mt-0.5">
                    {apiStatus.data.application}
                  </div>
                </div>
                <div>
                  <div className="text-xs text-slate-400">Versi API</div>
                  <div className="text-sm font-semibold text-emerald-400 mt-0.5">
                    v{apiStatus.data.version}
                  </div>
                </div>
              </div>
            )}

            <button
              onClick={checkHealth}
              disabled={apiStatus.status === 'checking'}
              className="w-full mt-2 py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 disabled:opacity-50 text-white font-medium text-sm transition-all shadow-lg shadow-emerald-900/30 flex items-center justify-center gap-2 cursor-pointer"
            >
              🔄 Uji Ulang Koneksi
            </button>
          </div>
        </div>
      </main>

      <footer className="text-center text-xs text-slate-500 pb-4">
        FinToko Foundation • Laravel 11/12 + React + Vite + MariaDB + Tailwind CSS
      </footer>
    </div>
  );
}

export default App;
