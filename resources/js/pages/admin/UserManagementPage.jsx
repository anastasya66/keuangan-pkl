import { useCallback, useEffect, useState } from 'react';
import useAuth from '../../hooks/useAuth';
import userService from '../../services/userService';

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

  // Handle Create User
  const handleCreateSubmit = async (e) => {
    e.preventDefault();
    setCreateErrors({});
    setIsSubmitting(true);
    try {
      const response = await userService.createUser(createForm);
      if (response.success) {
        setFeedback({ type: 'success', message: 'Pengguna baru berhasil ditambahkan.' });
        setShowCreateModal(false);
        setCreateForm({ name: '', email: '', password: '', role: 'staff', is_active: true });
        loadUsers(1);
      }
    } catch (err) {
      if (err.response?.data?.errors) {
        setCreateErrors(err.response.data.errors);
      } else {
        setFeedback({ type: 'error', message: err.response?.data?.message || 'Gagal membuat pengguna' });
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  // Handle Toggle Status
  const handleToggleStatus = async (user) => {
    const newStatus = !user.is_active;
    const actionLabel = newStatus ? 'mengaktifkan' : 'menonaktifkan';
    if (!window.confirm(`Apakah Anda yakin ingin ${actionLabel} akun ${user.name}?`)) return;

    try {
      const response = await userService.updateUserStatus(user.id, newStatus);
      if (response.success) {
        setFeedback({ type: 'success', message: response.message });
        loadUsers(pagination.current_page);
      }
    } catch (err) {
      setFeedback({ type: 'error', message: err.response?.data?.message || 'Gagal mengubah status pengguna.' });
    }
  };

  // Handle Role Change
  const handleChangeRole = async (user) => {
    const newRole = user.role === 'admin' ? 'staff' : 'admin';
    if (!window.confirm(`Ubah role ${user.name} menjadi ${newRole.toUpperCase()}?`)) return;

    try {
      const response = await userService.updateUserRole(user.id, newRole);
      if (response.success) {
        setFeedback({ type: 'success', message: response.message });
        loadUsers(pagination.current_page);
      }
    } catch (err) {
      setFeedback({ type: 'error', message: err.response?.data?.message || 'Gagal mengubah role.' });
    }
  };

  // Handle Soft Delete
  const handleDelete = async (user) => {
    if (!window.confirm(`Hapus akun ${user.name} (soft-delete)? Tindakan ini akan mencabut seluruh akses akun.`)) {
      return;
    }

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
          <h1 className="text-2xl font-bold text-white">Kelola Akun Pengguna</h1>
          <p className="text-sm text-slate-400 mt-0.5">
            Manajemen hak akses, role administrator, dan staf kasir toko
          </p>
        </div>
        <button
          onClick={() => setShowCreateModal(true)}
          className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm transition-all shadow-lg shadow-emerald-900/30 cursor-pointer"
        >
          ➕ Tambah Pengguna
        </button>
      </div>

      {/* Alert Feedback */}
      {feedback.message && (
        <div
          className={`p-4 rounded-xl text-sm font-medium flex items-center justify-between ${
            feedback.type === 'success'
              ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30'
              : 'bg-rose-500/10 text-rose-400 border border-rose-500/30'
          }`}
        >
          <span>{feedback.message}</span>
          <button
            onClick={() => setFeedback({ type: '', message: '' })}
            className="text-xs opacity-70 hover:opacity-100"
          >
            ✕
          </button>
        </div>
      )}

      {/* Filter and Search Bar */}
      <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-4 flex flex-col md:flex-row gap-4">
        <div className="flex-1">
          <input
            type="text"
            placeholder="Cari berdasarkan nama atau email..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full px-3.5 py-2 rounded-xl bg-slate-900/80 border border-slate-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
          />
        </div>
        <div className="flex items-center gap-3">
          <select
            value={roleFilter}
            onChange={(e) => setRoleFilter(e.target.value)}
            className="px-3 py-2 rounded-xl bg-slate-900/80 border border-slate-700 text-slate-200 text-sm focus:outline-none"
          >
            <option value="">Semua Role</option>
            <option value="admin">Admin</option>
            <option value="staff">Staff</option>
          </select>

          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="px-3 py-2 rounded-xl bg-slate-900/80 border border-slate-700 text-slate-200 text-sm focus:outline-none"
          >
            <option value="">Semua Status</option>
            <option value="true">Aktif</option>
            <option value="false">Nonaktif</option>
          </select>
        </div>
      </div>

      {/* User Table */}
      <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl overflow-hidden shadow-xl">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm text-slate-300">
            <thead className="bg-slate-900/60 text-xs font-semibold uppercase text-slate-400 border-b border-slate-700">
              <tr>
                <th className="px-6 py-4">Nama & Email</th>
                <th className="px-6 py-4">Role</th>
                <th className="px-6 py-4">Status</th>
                <th className="px-6 py-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-700/60">
              {isLoading ? (
                <tr>
                  <td colSpan="4" className="px-6 py-8 text-center text-slate-400">
                    <span className="inline-block w-5 h-5 border-2 border-emerald-500/30 border-t-emerald-400 rounded-full animate-spin mr-2"></span>
                    Memuat data pengguna...
                  </td>
                </tr>
              ) : users.length === 0 ? (
                <tr>
                  <td colSpan="4" className="px-6 py-8 text-center text-slate-500">
                    Tidak ada pengguna yang sesuai dengan filter pencarian.
                  </td>
                </tr>
              ) : (
                users.map((u) => (
                  <tr key={u.id} className="hover:bg-slate-700/20 transition-colors">
                    <td className="px-6 py-4">
                      <div className="font-semibold text-white">{u.name}</div>
                      <div className="text-xs text-slate-400 font-mono mt-0.5">{u.email}</div>
                    </td>
                    <td className="px-6 py-4">
                      <span
                        className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ${
                          u.role === 'admin'
                            ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30'
                            : 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30'
                        }`}
                      >
                        {u.role.toUpperCase()}
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      <span
                        className={`inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold ${
                          u.is_active
                            ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'
                            : 'bg-rose-500/20 text-rose-300 border border-rose-500/30'
                        }`}
                      >
                        <span
                          className={`w-1.5 h-1.5 rounded-full ${
                            u.is_active ? 'bg-emerald-400' : 'bg-rose-400'
                          }`}
                        ></span>
                        {u.is_active ? 'Aktif' : 'Nonaktif'}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-right space-x-2">
                      <button
                        onClick={() => handleChangeRole(u)}
                        title="Ubah Role"
                        className="px-2.5 py-1 text-xs font-medium rounded-lg bg-slate-700 hover:bg-slate-600 text-slate-200 transition-colors"
                      >
                        Role
                      </button>
                      <button
                        onClick={() => handleToggleStatus(u)}
                        title={u.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                        className={`px-2.5 py-1 text-xs font-medium rounded-lg transition-colors ${
                          u.is_active
                            ? 'bg-amber-500/10 text-amber-400 hover:bg-amber-500/20 border border-amber-500/30'
                            : 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 border border-emerald-500/30'
                        }`}
                      >
                        {u.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                      </button>
                      <button
                        onClick={() => handleDelete(u)}
                        title="Hapus Pengguna"
                        className="px-2.5 py-1 text-xs font-medium rounded-lg bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 border border-rose-500/30 transition-colors"
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
          <div className="px-6 py-4 bg-slate-900/60 border-t border-slate-700/60 flex items-center justify-between text-xs text-slate-400">
            <div>
              Total: <span className="font-semibold text-white">{pagination.total}</span> pengguna
            </div>
            <div className="flex items-center gap-2">
              <button
                disabled={pagination.current_page <= 1}
                onClick={() => loadUsers(pagination.current_page - 1)}
                className="px-3 py-1.5 rounded-lg bg-slate-800 border border-slate-700 disabled:opacity-40 hover:bg-slate-700 text-white"
              >
                Sebelumnya
              </button>
              <span>
                Halaman {pagination.current_page} dari {pagination.last_page}
              </span>
              <button
                disabled={pagination.current_page >= pagination.last_page}
                onClick={() => loadUsers(pagination.current_page + 1)}
                className="px-3 py-1.5 rounded-lg bg-slate-800 border border-slate-700 disabled:opacity-40 hover:bg-slate-700 text-white"
              >
                Selanjutnya
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Modal Tambah Pengguna Baru */}
      {showCreateModal && (
        <div className="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-2xl">
            <div className="flex items-center justify-between pb-4 border-b border-slate-800 mb-4">
              <h3 className="text-lg font-bold text-white">Tambah Pengguna Baru</h3>
              <button
                onClick={() => setShowCreateModal(false)}
                className="text-slate-400 hover:text-white"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleCreateSubmit} className="space-y-4">
              <div>
                <label className="block text-xs font-medium text-slate-300 mb-1">Nama Lengkap</label>
                <input
                  type="text"
                  required
                  value={createForm.name}
                  onChange={(e) => setCreateForm({ ...createForm, name: e.target.value })}
                  placeholder="Misal: John Kasir"
                  className="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                />
                {createErrors.name && (
                  <p className="text-xs text-rose-400 mt-1">{createErrors.name[0]}</p>
                )}
              </div>

              <div>
                <label className="block text-xs font-medium text-slate-300 mb-1">Alamat Email</label>
                <input
                  type="email"
                  required
                  value={createForm.email}
                  onChange={(e) => setCreateForm({ ...createForm, email: e.target.value })}
                  placeholder="john@fintoko.test"
                  className="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                />
                {createErrors.email && (
                  <p className="text-xs text-rose-400 mt-1">{createErrors.email[0]}</p>
                )}
              </div>

              <div>
                <label className="block text-xs font-medium text-slate-300 mb-1">Password Awal</label>
                <input
                  type="password"
                  required
                  value={createForm.password}
                  onChange={(e) => setCreateForm({ ...createForm, password: e.target.value })}
                  placeholder="Minimal 8 karakter"
                  className="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                />
                {createErrors.password && (
                  <p className="text-xs text-rose-400 mt-1">{createErrors.password[0]}</p>
                )}
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-medium text-slate-300 mb-1">Role</label>
                  <select
                    value={createForm.role}
                    onChange={(e) => setCreateForm({ ...createForm, role: e.target.value })}
                    className="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-sm focus:outline-none"
                  >
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-medium text-slate-300 mb-1">Status Awal</label>
                  <select
                    value={createForm.is_active ? '1' : '0'}
                    onChange={(e) => setCreateForm({ ...createForm, is_active: e.target.value === '1' })}
                    className="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-sm focus:outline-none"
                  >
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                  </select>
                </div>
              </div>

              <div className="flex justify-end gap-3 pt-4 border-t border-slate-800 mt-6">
                <button
                  type="button"
                  onClick={() => setShowCreateModal(false)}
                  className="px-4 py-2 rounded-xl text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-300"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white disabled:opacity-50"
                >
                  {isSubmitting ? 'Menyimpan...' : 'Simpan Pengguna'}
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
