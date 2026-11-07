import { useCallback, useEffect, useState } from 'react';
import useAuth from '../../hooks/useAuth';
import userService from '../../services/userService';
import { IconUsers } from '../../components/common/Icons';

export function UserManagementPage() {
  const { user: currentUser } = useAuth();

  const [users, setUsers] = useState([]);
  const [pagination, setPagination] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [search, setSearch] = useState('');
  const [roleFilter, setRoleFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [isLoading, setIsLoading] = useState(true);
  const [feedback, setFeedback] = useState({ type: '', message: '' });

  // Modal State for Create User
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [createForm, setCreateForm] = useState({
    name: '',
    email: '',
    password: '',
    role: 'staff',
    is_active: true,
  });
  const [createErrors, setCreateErrors] = useState({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Load users list
  const loadUsers = useCallback(
    async (page = 1) => {
      setIsLoading(true);
      try {
        const params = {
          page,
          search: search.trim() || undefined,
          role: roleFilter || undefined,
          is_active: statusFilter !== '' ? statusFilter : undefined,
        };
        const response = await userService.getUsers(params);
        if (response.success) {
          setUsers(response.data);
          setPagination(response.pagination);
        }
      } catch (err) {
        setFeedback({ type: 'error', message: err.response?.data?.message || 'Gagal memuat pengguna' });
      } finally {
        setIsLoading(false);
      }
    },
    [search, roleFilter, statusFilter]
  );

  useEffect(() => {
    loadUsers(1);
  }, [loadUsers]);

  const handleCreateSubmit = async (e) => {
    e.preventDefault();
    setCreateErrors({});
    setIsSubmitting(true);

    try {
      const response = await userService.createUser(createForm);
      if (response.success) {
        setFeedback({ type: 'success', message: 'Pengguna baru berhasil dibuat.' });
        setShowCreateModal(false);
        setCreateForm({
          name: '',
          email: '',
          password: '',
          role: 'staff',
          is_active: true,
        });
        loadUsers(1);
      }
    } catch (err) {
      if (err.response?.status === 422 && err.response?.data?.errors) {
        setCreateErrors(err.response.data.errors);
      } else {
        setFeedback({
          type: 'error',
          message: err.response?.data?.message || 'Terjadi kesalahan saat membuat pengguna.',
        });
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleToggleStatus = async (user) => {
    if (user.id === currentUser?.id) {
      setFeedback({ type: 'error', message: 'Anda tidak dapat menonaktifkan akun sendiri.' });
      return;
    }
    const newStatus = !user.is_active;
    try {
      const response = await userService.updateStatus(user.id, newStatus);
      if (response.success) {
        setFeedback({ type: 'success', message: response.message });
        loadUsers(pagination.current_page);
      }
    } catch (err) {
      setFeedback({ type: 'error', message: err.response?.data?.message || 'Gagal mengubah status pengguna.' });
    }
  };

  const handleChangeRole = async (user) => {
    if (user.id === currentUser?.id) {
      setFeedback({ type: 'error', message: 'Anda tidak dapat mengubah role akun sendiri.' });
      return;
    }
    const newRole = user.role === 'admin' ? 'staff' : 'admin';
    try {
      const response = await userService.updateRole(user.id, newRole);
      if (response.success) {
        setFeedback({ type: 'success', message: response.message });
        loadUsers(pagination.current_page);
      }
    } catch (err) {
      setFeedback({ type: 'error', message: err.response?.data?.message || 'Gagal memperbarui role pengguna.' });
    }
  };

  const handleDelete = async (user) => {
    if (user.id === currentUser?.id) {
      setFeedback({ type: 'error', message: 'Anda tidak dapat menghapus akun sendiri.' });
      return;
    }
    const confirm = window.confirm(`Apakah Anda yakin ingin menghapus akun ${user.name}?`);
    if (!confirm) return;

    try {
      const response = await userService.deleteUser(user.id);
      if (response.success) {
        setFeedback({ type: 'success', message: response.message });
        loadUsers(pagination.current_page);
      }
    } catch (err) {
      setFeedback({ type: 'error', message: err.response?.data?.message || 'Gagal menghapus pengguna.' });
    }
  };

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl sm:text-2xl font-black text-[#10262E] flex items-center gap-2.5">
            <span className="p-2 rounded-xl bg-white border border-[#D7EBEF] text-[#27C5E5] shadow-2xs">
              <IconUsers className="w-5 h-5" />
            </span>
            <span>Kelola Tim Toko</span>
          </h1>
          <p className="text-xs text-[#577782] mt-1">
            Atur akun staf, kasir toko, dan hak akses admin tokomu
          </p>
        </div>
        <button
          onClick={() => setShowCreateModal(true)}
          className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#27C5E5] hover:bg-[#38BFE0] text-white font-semibold text-xs shadow-md shadow-[#27C5E5]/20 transition-all cursor-pointer btn-press"
        >
          <span>+ Tambah Anggota Tim</span>
        </button>
      </div>

      {/* Alert Feedback */}
      {feedback.message && (
        <div
          className={`p-4 rounded-2xl text-xs font-semibold flex items-center justify-between border shadow-sm animate-toast ${
            feedback.type === 'success'
              ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
              : 'bg-rose-50 text-rose-800 border-rose-200'
          }`}
        >
          <span>{feedback.message}</span>
          <button
            onClick={() => setFeedback({ type: '', message: '' })}
            className="text-xs opacity-70 hover:opacity-100 cursor-pointer btn-press"
          >
            ✕
          </button>
        </div>
      )}

      {/* Filter and Search Bar */}
      <div className="bg-white border border-[#D7EBEF] rounded-3xl p-4 flex flex-col md:flex-row gap-4 shadow-xs">
        <div className="flex-1">
          <input
            type="text"
            placeholder="Cari berdasarkan nama atau email pengguna..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full px-3.5 py-2 rounded-xl bg-[#F8FDFF] border border-[#D7EBEF] text-[#16323B] placeholder-[#8EA6AF] text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25"
          />
        </div>
        <div className="flex items-center gap-2.5">
          <select
            value={roleFilter}
            onChange={(e) => setRoleFilter(e.target.value)}
            className="px-3 py-2 rounded-xl bg-[#F8FDFF] border border-[#D7EBEF] text-[#16323B] text-xs focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25"
          >
            <option value="">Semua Role</option>
            <option value="admin">Administrator</option>
            <option value="staff">Staff Toko</option>
          </select>

          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="px-3 py-2 rounded-xl bg-[#F8FDFF] border border-[#D7EBEF] text-[#16323B] text-xs focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25"
          >
            <option value="">Semua Status</option>
            <option value="true">Aktif</option>
            <option value="false">Nonaktif</option>
          </select>
        </div>
      </div>

      {/* User Table */}
      <div className="bg-white border border-[#D7EBEF] rounded-3xl overflow-hidden shadow-xs">
        <div className="overflow-x-auto no-scrollbar">
          <table className="w-full text-left text-xs text-[#16323B]">
            <thead className="bg-[#F3FBFC] text-xs font-semibold uppercase tracking-wider text-[#577782] border-b border-[#D7EBEF]">
              <tr>
                <th className="px-6 py-3.5">Nama &amp; Email</th>
                <th className="px-6 py-3.5">Role</th>
                <th className="px-6 py-3.5 text-center">Status</th>
                <th className="px-6 py-3.5 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[#EBF5F8]">
              {isLoading ? (
                <tr>
                  <td colSpan="4" className="px-6 py-10 text-center text-[#8EA6AF]">
                    <span className="inline-block w-5 h-5 border-2 border-[#38BFE0]/30 border-t-[#38BFE0] rounded-full animate-spin mr-2"></span>
                    Memuat data pengguna...
                  </td>
                </tr>
              ) : users.length === 0 ? (
                <tr>
                  <td colSpan="4" className="px-6 py-10 text-center text-[#8EA6AF]">
                    Tidak ada pengguna yang sesuai dengan kriteria pencarian.
                  </td>
                </tr>
              ) : (
                users.map((u) => (
                  <tr key={u.id} className="hover:bg-[#F3FBFC] transition-colors">
                    <td className="px-6 py-3.5">
                      <div className="font-bold text-[#10262E]">{u.name}</div>
                      <div className="text-[11px] text-[#577782] font-mono mt-0.5">{u.email}</div>
                    </td>
                    <td className="px-6 py-3.5">
                      <span
                        className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border ${
                          u.role === 'admin'
                            ? 'bg-purple-50 text-purple-700 border-purple-200'
                            : 'bg-[#EAF8FA] text-[#167A93] border-[#38BFE0]/30'
                        }`}
                      >
                        {u.role.toUpperCase()}
                      </span>
                    </td>
                    <td className="px-6 py-3.5 text-center">
                      <span
                        className={`inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold border ${
                          u.is_active
                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                            : 'bg-rose-50 text-rose-700 border-rose-200'
                        }`}
                      >
                        {u.is_active ? 'Aktif' : 'Nonaktif'}
                      </span>
                    </td>
                    <td className="px-6 py-3.5 text-right space-x-1.5 whitespace-nowrap">
                      <button
                        onClick={() => handleChangeRole(u)}
                        title="Ubah Role"
                        className="px-2.5 py-1 text-xs font-semibold rounded-lg bg-white hover:bg-[#F3FBFC] text-[#16323B] border border-[#D7EBEF] transition-colors cursor-pointer shadow-2xs"
                      >
                        Ganti Role
                      </button>
                      <button
                        onClick={() => handleToggleStatus(u)}
                        title={u.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                        className={`px-2.5 py-1 text-xs font-semibold rounded-lg border transition-colors cursor-pointer ${
                          u.is_active
                            ? 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100'
                            : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'
                        }`}
                      >
                        {u.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                      </button>
                      <button
                        onClick={() => handleDelete(u)}
                        title="Hapus Pengguna"
                        className="px-2.5 py-1 text-xs font-semibold rounded-lg bg-rose-50 text-rose-600 border border-rose-200 hover:bg-rose-100 transition-colors cursor-pointer"
                      >
                        Hapus
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination Bar */}
        {pagination.total > 0 && (
          <div className="px-6 py-3.5 bg-[#F3FBFC]/40 border-t border-[#D7EBEF] flex items-center justify-between text-xs text-[#577782]">
            <div>
              Total: <span className="font-bold text-[#10262E]">{pagination.total}</span> pengguna
            </div>
            <div className="flex items-center gap-1.5">
              <button
                disabled={pagination.current_page <= 1}
                onClick={() => loadUsers(pagination.current_page - 1)}
                className="px-3 py-1.5 rounded-lg bg-white border border-[#D7EBEF] disabled:opacity-40 hover:bg-[#F3FBFC] text-[#16323B] font-medium transition-colors cursor-pointer"
              >
                Sebelumnya
              </button>
              <span className="px-3 py-1.5 rounded-lg bg-[#EAF8FA] border border-[#38BFE0]/30 text-[#167A93] font-bold">
                Hal {pagination.current_page} / {pagination.last_page}
              </span>
              <button
                disabled={pagination.current_page >= pagination.last_page}
                onClick={() => loadUsers(pagination.current_page + 1)}
                className="px-3 py-1.5 rounded-lg bg-white border border-[#D7EBEF] disabled:opacity-40 hover:bg-[#F3FBFC] text-[#16323B] font-medium transition-colors cursor-pointer"
              >
                Selanjutnya
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Modal Tambah Pengguna Baru */}
      {showCreateModal && (
        <div className="fixed inset-0 z-50 bg-[#10262E]/35 backdrop-blur-xs flex items-center justify-center p-4 animate-backdrop">
          <div className="bg-white border border-[#D7EBEF] rounded-3xl w-full max-w-md p-6 sm:p-8 shadow-2xl animate-modal-pop">
            <div className="flex items-center justify-between pb-4 border-b border-[#D7EBEF] mb-4">
              <h3 className="text-base sm:text-lg font-bold text-[#10262E] flex items-center gap-2">
                <span className="p-2 rounded-xl bg-[#EAF8FA] text-[#27C5E5]">
                  <IconUsers className="w-5 h-5" />
                </span>
                <span>Tambah Anggota Tim</span>
              </h3>
              <button
                onClick={() => setShowCreateModal(false)}
                className="w-8 h-8 rounded-xl bg-[#F3FBFC] hover:bg-[#EAF8FA] text-[#8EA6AF] hover:text-[#16323B] transition-colors flex items-center justify-center cursor-pointer btn-press"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleCreateSubmit} className="space-y-4">
              <div>
                <label className="block text-xs font-semibold text-[#16323B] mb-1">Nama Lengkap</label>
                <input
                  type="text"
                  required
                  value={createForm.name}
                  onChange={(e) => setCreateForm({ ...createForm, name: e.target.value })}
                  placeholder="Misal: Budi Santoso"
                  className="w-full px-3.5 py-2.5 rounded-xl bg-[#F8FDFF] border border-[#D7EBEF] text-[#16323B] text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all input-focus-glow"
                />
                {createErrors.name && (
                  <p className="text-[11px] text-rose-500 mt-1">{createErrors.name[0]}</p>
                )}
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#16323B] mb-1">Email</label>
                <input
                  type="email"
                  required
                  value={createForm.email}
                  onChange={(e) => setCreateForm({ ...createForm, email: e.target.value })}
                  placeholder="nama@fintoko.test"
                  className="w-full px-3.5 py-2.5 rounded-xl bg-[#F8FDFF] border border-[#D7EBEF] text-[#16323B] text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all input-focus-glow"
                />
                {createErrors.email && (
                  <p className="text-[11px] text-rose-500 mt-1">{createErrors.email[0]}</p>
                )}
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#16323B] mb-1">Kata Sandi (Password)</label>
                <input
                  type="password"
                  required
                  value={createForm.password}
                  onChange={(e) => setCreateForm({ ...createForm, password: e.target.value })}
                  placeholder="Minimal 8 karakter"
                  className="w-full px-3.5 py-2.5 rounded-xl bg-[#F8FDFF] border border-[#D7EBEF] text-[#16323B] text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all input-focus-glow"
                />
                {createErrors.password && (
                  <p className="text-[11px] text-rose-500 mt-1">{createErrors.password[0]}</p>
                )}
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#16323B] mb-1">Peran / Jabatan</label>
                <select
                  value={createForm.role}
                  onChange={(e) => setCreateForm({ ...createForm, role: e.target.value })}
                  className="w-full px-3.5 py-2.5 rounded-xl bg-[#F8FDFF] border border-[#D7EBEF] text-[#16323B] text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all input-focus-glow"
                >
                  <option value="staff">Staff Toko (Kasir / Operasional)</option>
                  <option value="admin">Administrator (Akses Penuh)</option>
                </select>
              </div>

              <div className="pt-2">
                <label className="inline-flex items-center gap-2.5 cursor-pointer select-none">
                  <input
                    type="checkbox"
                    checked={createForm.is_active}
                    onChange={(e) => setCreateForm({ ...createForm, is_active: e.target.checked })}
                    className="w-4 h-4 rounded text-[#27C5E5] focus:ring-[#27C5E5] border-[#D7EBEF]"
                  />
                  <span className="text-xs font-semibold text-[#16323B]">Akun Langsung Aktif</span>
                </label>
              </div>

              <div className="pt-4 border-t border-[#D7EBEF] flex items-center justify-end gap-2.5">
                <button
                  type="button"
                  onClick={() => setShowCreateModal(false)}
                  className="px-4 py-2.5 rounded-xl bg-white hover:bg-[#F3FBFC] border border-[#D7EBEF] text-xs font-semibold text-[#577782] cursor-pointer btn-press"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="px-5 py-2.5 rounded-xl bg-[#27C5E5] hover:bg-[#38BFE0] text-white font-semibold text-xs shadow-md shadow-[#27C5E5]/20 transition-all cursor-pointer disabled:opacity-50 btn-press"
                >
                  {isSubmitting ? 'Menyimpan...' : 'Simpan Akun'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}

export default UserManagementPage;
