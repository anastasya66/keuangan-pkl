import { useState, useEffect } from 'react';
import useAuth from '../../hooks/useAuth';
import authService from '../../services/authService';
import settingsService from '../../services/settingsService';

export function ProfilePage() {
  const { user, fetchUser } = useAuth();

  // Profile Form State
  const [profileForm, setProfileForm] = useState({
    name: user?.name || '',
    username: user?.username || '',
    email: user?.email || '',
    phone: user?.phone || '',
  });
  const [isUpdatingProfile, setIsUpdatingProfile] = useState(false);
  const [profileFeedback, setProfileFeedback] = useState({ type: '', message: '' });
  const [profileErrors, setProfileErrors] = useState({});

  // Login Histories State
  const [loginHistories, setLoginHistories] = useState([]);

  useEffect(() => {
    async function loadHistories() {
      try {
        const res = await settingsService.getLoginHistories();
        if (res.success) setLoginHistories(res.data);
      } catch (err) {
        console.error(err);
      }
    }
    loadHistories();
  }, []);

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
        <h1 className="text-2xl font-extrabold text-[#16323B]">Pengaturan Profil Pengguna</h1>
        <p className="text-sm text-[#5A7C85] mt-1">
          Kelola data diri, alamat email, dan keamanan kata sandi akun FinToko Anda
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
        {/* Update Profile Card */}
        <div className="bg-white border border-[#D7EBEF] rounded-2xl p-6 shadow-xs">
          <h2 className="text-sm font-bold text-[#16323B] mb-4 pb-2.5 border-b border-[#D7EBEF]">
            Informasi Pribadi
          </h2>

          {profileFeedback.message && (
            <div
              className={`mb-4 p-3 rounded-xl text-xs font-semibold ${
                profileFeedback.type === 'success'
                  ? 'bg-emerald-50 text-emerald-800 border border-emerald-200'
                  : 'bg-rose-50 text-rose-800 border border-rose-200'
              }`}
            >
              {profileFeedback.message}
            </div>
          )}

          <form onSubmit={handleProfileSubmit} className="space-y-4">
            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1.5">Nama Lengkap</label>
              <input
                type="text"
                required
                value={profileForm.name}
                onChange={(e) => setProfileForm({ ...profileForm, name: e.target.value })}
                className="w-full px-3 py-2 rounded-xl bg-[#F3FBFC] border border-[#D7EBEF] text-[#16323B] text-sm focus:bg-white focus:outline-none focus:border-[#38BFE0] focus:ring-2 focus:ring-[#38BFE0]/20 transition-all"
              />
              {profileErrors.name && (
                <p className="text-xs text-rose-600 mt-1 font-medium">{profileErrors.name[0]}</p>
              )}
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1.5">Alamat Email</label>
              <input
                type="email"
                required
                value={profileForm.email}
                onChange={(e) => setProfileForm({ ...profileForm, email: e.target.value })}
                className="w-full px-3 py-2 rounded-xl bg-[#F3FBFC] border border-[#D7EBEF] text-[#16323B] text-sm focus:bg-white focus:outline-none focus:border-[#38BFE0] focus:ring-2 focus:ring-[#38BFE0]/20 transition-all"
              />
              {profileErrors.email && (
                <p className="text-xs text-rose-600 mt-1 font-medium">{profileErrors.email[0]}</p>
              )}
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1.5">Username (Opsional)</label>
              <input
                type="text"
                value={profileForm.username}
                onChange={(e) => setProfileForm({ ...profileForm, username: e.target.value })}
                placeholder="nama_pengguna"
                className="w-full px-3 py-2 rounded-xl bg-[#F3FBFC] border border-[#D7EBEF] text-[#16323B] text-sm focus:bg-white focus:outline-none focus:border-[#38BFE0] focus:ring-2 focus:ring-[#38BFE0]/20 transition-all font-mono"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1.5">Nomor Telepon / WA</label>
              <input
                type="text"
                value={profileForm.phone}
                onChange={(e) => setProfileForm({ ...profileForm, phone: e.target.value })}
                placeholder="081234567890"
                className="w-full px-3 py-2 rounded-xl bg-[#F3FBFC] border border-[#D7EBEF] text-[#16323B] text-sm focus:bg-white focus:outline-none focus:border-[#38BFE0] focus:ring-2 focus:ring-[#38BFE0]/20 transition-all"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1.5">Role Akun</label>
              <div className="px-3 py-2 rounded-xl bg-[#F3FBFC] border border-[#D7EBEF] text-[#5A7C85] text-xs font-semibold uppercase">
                {user?.role} (Permanen)
              </div>
              <p className="text-[11px] text-[#5A7C85] mt-1">Role hanya dapat diubah oleh Administrator.</p>
            </div>

            <button
              type="submit"
              disabled={isUpdatingProfile}
              className="w-full mt-2 py-2.5 px-4 rounded-xl bg-[#27C5E5] hover:bg-[#38BFE0] disabled:opacity-50 text-white font-semibold text-xs transition-all shadow-md shadow-[#27C5E5]/20 cursor-pointer"
            >
              {isUpdatingProfile ? 'Menyimpan...' : 'Simpan Perubahan Profil'}
            </button>
          </form>
        </div>

        {/* Change Password Card */}
        <div className="bg-white border border-[#D7EBEF] rounded-2xl p-6 shadow-xs">
          <h2 className="text-sm font-bold text-[#16323B] mb-4 pb-2.5 border-b border-[#D7EBEF]">
            Perbarui Kata Sandi
          </h2>

          {passwordFeedback.message && (
            <div
              className={`mb-4 p-3 rounded-xl text-xs font-semibold ${
                passwordFeedback.type === 'success'
                  ? 'bg-emerald-50 text-emerald-800 border border-emerald-200'
                  : 'bg-rose-50 text-rose-800 border border-rose-200'
              }`}
            >
              {passwordFeedback.message}
            </div>
          )}

          <form onSubmit={handlePasswordSubmit} className="space-y-4">
            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
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
                className="w-full px-3 py-2 rounded-xl bg-[#F3FBFC] border border-[#D7EBEF] text-[#16323B] text-sm focus:bg-white focus:outline-none focus:border-[#38BFE0] focus:ring-2 focus:ring-[#38BFE0]/20 transition-all"
              />
              {passwordErrors.current_password && (
                <p className="text-xs text-rose-600 mt-1 font-medium">{passwordErrors.current_password[0]}</p>
              )}
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
                Password Baru (Min. 8 karakter)
              </label>
              <input
                type="password"
                required
                value={passwordForm.password}
                onChange={(e) => setPasswordForm({ ...passwordForm, password: e.target.value })}
                placeholder="••••••••"
                className="w-full px-3 py-2 rounded-xl bg-[#F3FBFC] border border-[#D7EBEF] text-[#16323B] text-sm focus:bg-white focus:outline-none focus:border-[#38BFE0] focus:ring-2 focus:ring-[#38BFE0]/20 transition-all"
              />
              {passwordErrors.password && (
                <p className="text-xs text-rose-600 mt-1 font-medium">{passwordErrors.password[0]}</p>
              )}
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
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
                className="w-full px-3 py-2 rounded-xl bg-[#F3FBFC] border border-[#D7EBEF] text-[#16323B] text-sm focus:bg-white focus:outline-none focus:border-[#38BFE0] focus:ring-2 focus:ring-[#38BFE0]/20 transition-all"
              />
              {passwordErrors.password_confirmation && (
                <p className="text-xs text-rose-600 mt-1 font-medium">
                  {passwordErrors.password_confirmation[0]}
                </p>
              )}
            </div>

            <button
              type="submit"
              disabled={isUpdatingPassword}
              className="w-full mt-2 py-2.5 px-4 rounded-xl bg-[#27C5E5] hover:bg-[#38BFE0] disabled:opacity-50 text-white font-semibold text-xs transition-all shadow-md shadow-[#27C5E5]/20 cursor-pointer"
            >
              {isUpdatingPassword ? 'Memproses...' : 'Ubah Kata Sandi'}
            </button>
          </form>
        </div>
      </div>

      {/* Login History Section */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-6 shadow-xs space-y-4">
        <h2 className="text-sm font-bold text-[#16323B] pb-2.5 border-b border-[#D7EBEF] flex items-center justify-between">
          <span>Riwayat Sesi & Aktivitas Login Akun</span>
          <span className="text-[11px] font-normal text-[#577782]">Keamanan Akun Terproteksi</span>
        </h2>

        <div className="overflow-x-auto">
          <table className="w-full text-xs text-left">
            <thead>
              <tr className="border-b border-[#D7EBEF] text-[#577782] font-semibold bg-[#F8FDFE]">
                <th className="py-2.5 px-3">Waktu Login</th>
                <th className="py-2.5 px-3">IP Address</th>
                <th className="py-2.5 px-3">Perangkat / Browser</th>
                <th className="py-2.5 px-3 text-center">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[#EAF4F6]">
              {loginHistories.length === 0 ? (
                <tr>
                  <td colSpan="4" className="py-6 text-center text-[#577782]">
                    Belum ada riwayat login tercatat.
                  </td>
                </tr>
              ) : (
                loginHistories.map((lh) => (
                  <tr key={lh.id} className="hover:bg-[#F8FDFE]">
                    <td className="py-2.5 px-3 font-mono text-[11px] text-[#16323B]">
                      {lh.login_at?.slice(0, 19).replace('T', ' ')}
                    </td>
                    <td className="py-2.5 px-3 font-mono text-[#577782]">{lh.ip_address || '127.0.0.1'}</td>
                    <td className="py-2.5 px-3 text-[#577782] max-w-xs truncate" title={lh.user_agent}>
                      {lh.user_agent || 'Web Browser'}
                    </td>
                    <td className="py-2.5 px-3 text-center">
                      <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-600 text-white">
                        {lh.status || 'Berhasil'}
                      </span>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}

export default ProfilePage;
