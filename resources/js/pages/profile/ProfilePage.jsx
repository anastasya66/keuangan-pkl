import { useState } from 'react';
import useAuth from '../../hooks/useAuth';
import authService from '../../services/authService';

export function ProfilePage() {
  const { user, fetchUser } = useAuth();

  // Profile Form State
  const [profileForm, setProfileForm] = useState({
    name: user?.name || '',
    email: user?.email || '',
  });
  const [isUpdatingProfile, setIsUpdatingProfile] = useState(false);
  const [profileFeedback, setProfileFeedback] = useState({ type: '', message: '' });
  const [profileErrors, setProfileErrors] = useState({});

  // Password Form State
  const [passwordForm, setPasswordForm] = useState({
    current_password: '',
    password: '',
    password_confirmation: '',
  });
  const [isUpdatingPassword, setIsUpdatingPassword] = useState(false);
  const [passwordFeedback, setPasswordFeedback] = useState({ type: '', message: '' });
  const [passwordErrors, setPasswordErrors] = useState({});

  // Submit Profile Update
  const handleProfileSubmit = async (e) => {
    e.preventDefault();
    setProfileFeedback({ type: '', message: '' });
    setProfileErrors({});
    setIsUpdatingProfile(true);

    try {
      const response = await authService.updateProfile(profileForm);
      if (response.success) {
        setProfileFeedback({ type: 'success', message: 'Profil Anda berhasil diperbarui.' });
        await fetchUser();
      }
    } catch (err) {
      if (err.response?.data?.errors) {
        setProfileErrors(err.response.data.errors);
      } else {
        setProfileFeedback({
          type: 'error',
          message: err.response?.data?.message || 'Gagal memperbarui profil.',
        });
      }
    } finally {
      setIsUpdatingProfile(false);
    }
  };

  // Submit Password Change
  const handlePasswordSubmit = async (e) => {
    e.preventDefault();
    setPasswordFeedback({ type: '', message: '' });
    setPasswordErrors({});
    setIsUpdatingPassword(true);

    try {
      const response = await authService.changePassword(passwordForm);
      if (response.success) {
        setPasswordFeedback({
          type: 'success',
          message: 'Password berhasil diubah. Sesi pada perangkat lain telah dicabut demi keamanan.',
        });
        setPasswordForm({ current_password: '', password: '', password_confirmation: '' });
      }
    } catch (err) {
      if (err.response?.data?.errors) {
        setPasswordErrors(err.response.data.errors);
      } else {
        setPasswordFeedback({
          type: 'error',
          message: err.response?.data?.message || 'Gagal mengubah password.',
        });
      }
    } finally {
      setIsUpdatingPassword(false);
    }
  };

  return (
    <div className="max-w-4xl mx-auto space-y-8">
      <div>
        <h1 className="text-2xl font-bold text-white">Pengaturan Profil Pengguna</h1>
        <p className="text-sm text-slate-400 mt-1">
          Kelola data diri, alamat email, dan keamanan kata sandi akun FinToko Anda
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
        {/* Update Profile Card */}
        <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-6 shadow-xl backdrop-blur">
          <h2 className="text-base font-bold text-white mb-4 pb-2 border-b border-slate-700">
            Informasi Pribadi
          </h2>

          {profileFeedback.message && (
            <div
              className={`mb-4 p-3 rounded-xl text-xs font-medium ${
                profileFeedback.type === 'success'
                  ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30'
                  : 'bg-rose-500/10 text-rose-400 border border-rose-500/30'
              }`}
            >
              {profileFeedback.message}
            </div>
          )}

          <form onSubmit={handleProfileSubmit} className="space-y-4">
            <div>
              <label className="block text-xs font-medium text-slate-300 mb-1">Nama Lengkap</label>
              <input
                type="text"
                required
                value={profileForm.name}
                onChange={(e) => setProfileForm({ ...profileForm, name: e.target.value })}
                className="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
              />
              {profileErrors.name && (
                <p className="text-xs text-rose-400 mt-1">{profileErrors.name[0]}</p>
              )}
            </div>

            <div>
              <label className="block text-xs font-medium text-slate-300 mb-1">Alamat Email</label>
              <input
                type="email"
                required
                value={profileForm.email}
                onChange={(e) => setProfileForm({ ...profileForm, email: e.target.value })}
                className="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
              />
              {profileErrors.email && (
                <p className="text-xs text-rose-400 mt-1">{profileErrors.email[0]}</p>
              )}
            </div>

            <div>
              <label className="block text-xs font-medium text-slate-400 mb-1">Role Akun</label>
              <div className="px-3 py-2 rounded-xl bg-slate-900/50 border border-slate-800 text-slate-400 text-sm font-semibold uppercase">
                {user?.role} (Permanen)
              </div>
              <p className="text-[11px] text-slate-500 mt-1">Role hanya dapat diubah oleh Administrator.</p>
            </div>

            <button
              type="submit"
              disabled={isUpdatingProfile}
              className="w-full mt-2 py-2 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-semibold text-xs transition-all shadow-md"
            >
              {isUpdatingProfile ? 'Menyimpan...' : 'Simpan Perubahan Profil'}
            </button>
          </form>
        </div>

        {/* Change Password Card */}
        <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-6 shadow-xl backdrop-blur">
          <h2 className="text-base font-bold text-white mb-4 pb-2 border-b border-slate-700">
            Perbarui Kata Sandi
          </h2>

          {passwordFeedback.message && (
            <div
              className={`mb-4 p-3 rounded-xl text-xs font-medium ${
                passwordFeedback.type === 'success'
                  ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30'
                  : 'bg-rose-500/10 text-rose-400 border border-rose-500/30'
              }`}
            >
              {passwordFeedback.message}
            </div>
          )}

          <form onSubmit={handlePasswordSubmit} className="space-y-4">
            <div>
              <label className="block text-xs font-medium text-slate-300 mb-1">
                Password Saat Ini
              </label>
              <input
                type="password"
                required
                value={passwordForm.current_password}
                onChange={(e) =>
                  setPasswordForm({ ...passwordForm, current_password: e.target.value })
                }
                placeholder="••••••••"
                className="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
              />
              {passwordErrors.current_password && (
                <p className="text-xs text-rose-400 mt-1">{passwordErrors.current_password[0]}</p>
              )}
            </div>

            <div>
              <label className="block text-xs font-medium text-slate-300 mb-1">
                Password Baru (Min. 8 karakter)
              </label>
              <input
                type="password"
                required
                value={passwordForm.password}
                onChange={(e) => setPasswordForm({ ...passwordForm, password: e.target.value })}
                placeholder="••••••••"
                className="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
              />
              {passwordErrors.password && (
                <p className="text-xs text-rose-400 mt-1">{passwordErrors.password[0]}</p>
              )}
            </div>

            <div>
              <label className="block text-xs font-medium text-slate-300 mb-1">
                Konfirmasi Password Baru
              </label>
              <input
                type="password"
                required
                value={passwordForm.password_confirmation}
                onChange={(e) =>
                  setPasswordForm({ ...passwordForm, password_confirmation: e.target.value })
                }
                placeholder="••••••••"
                className="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
              />
              {passwordErrors.password_confirmation && (
                <p className="text-xs text-rose-400 mt-1">
                  {passwordErrors.password_confirmation[0]}
                </p>
              )}
            </div>

            <button
              type="submit"
              disabled={isUpdatingPassword}
              className="w-full mt-2 py-2 px-4 rounded-xl bg-teal-600 hover:bg-teal-500 disabled:opacity-50 text-white font-semibold text-xs transition-all shadow-md"
            >
              {isUpdatingPassword ? 'Memproses...' : 'Ubah Kata Sandi'}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}

export default ProfilePage;
