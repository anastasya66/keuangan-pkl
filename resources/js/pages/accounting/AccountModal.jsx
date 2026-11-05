import { useEffect, useState } from 'react';

export default function AccountModal({ isOpen, onClose, onSubmit, account, isSubmitting }) {
  const [formData, setFormData] = useState({
    account_code: '',
    account_name: '',
    account_type: 'assets',
    normal_balance: 'debit',
    description: '',
    is_active: true,
  });

  const [errors, setErrors] = useState({});

  useEffect(() => {
    if (account) {
      setFormData({
        account_code: account.account_code || '',
        account_name: account.account_name || '',
        account_type: account.account_type || 'assets',
        normal_balance: account.normal_balance || 'debit',
        description: account.description || '',
        is_active: account.is_active ?? true,
      });
    } else {
      setFormData({
        account_code: '',
        account_name: '',
        account_type: 'assets',
        normal_balance: 'debit',
        description: '',
        is_active: true,
      });
    }
    setErrors({});
  }, [account, isOpen]);

  if (!isOpen) return null;

  const handleTypeChange = (e) => {
    const newType = e.target.value;
    const defaultBalance = ['assets', 'expenses'].includes(newType) ? 'debit' : 'credit';
    setFormData((prev) => ({
      ...prev,
      account_type: newType,
      normal_balance: defaultBalance,
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    const res = await onSubmit(formData);
    if (res?.errors) {
      setErrors(res.errors);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-fade-in">
      <div className="relative w-full max-w-lg bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden">
        {/* Header */}
        <div className="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
          <h3 className="text-lg font-bold text-white">
            {account ? 'Edit Akun Akuntansi' : 'Tambah Akun Akuntansi Baru'}
          </h3>
          <button
            type="button"
            onClick={onClose}
            className="text-slate-400 hover:text-white text-lg font-mono leading-none cursor-pointer"
          >
            ✕
          </button>
        </div>

        {/* Form Body */}
        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          {/* Kode & Tipe */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-slate-300 mb-1">
                Kode Akun <span className="text-rose-400">*</span>
              </label>
              <input
                type="text"
                value={formData.account_code}
                onChange={(e) => setFormData({ ...formData, account_code: e.target.value })}
                placeholder="Contoh: 1110"
                className={`w-full px-3 py-2 bg-slate-950 border ${
                  errors.account_code ? 'border-rose-500' : 'border-slate-800'
                } rounded-xl text-sm text-slate-100 focus:outline-none focus:border-emerald-500`}
                required
              />
              {errors.account_code && (
                <p className="mt-1 text-xs text-rose-400">{errors.account_code[0]}</p>
              )}
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-300 mb-1">
                Kategori Akun <span className="text-rose-400">*</span>
              </label>
              <select
                value={formData.account_type}
                onChange={handleTypeChange}
                className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-100 focus:outline-none focus:border-emerald-500"
              >
                <option value="assets">Assets (Aset)</option>
                <option value="liabilities">Liabilities (Kewajiban)</option>
                <option value="equity">Equity (Modal)</option>
                <option value="revenue">Revenue (Pendapatan)</option>
                <option value="expenses">Expenses (Beban)</option>
              </select>
            </div>
          </div>

          {/* Nama Akun */}
          <div>
            <label className="block text-xs font-semibold text-slate-300 mb-1">
              Nama Akun <span className="text-rose-400">*</span>
            </label>
            <input
              type="text"
              value={formData.account_name}
              onChange={(e) => setFormData({ ...formData, account_name: e.target.value })}
              placeholder="Contoh: Kas Toko Ritel"
              className={`w-full px-3 py-2 bg-slate-950 border ${
                errors.account_name ? 'border-rose-500' : 'border-slate-800'
              } rounded-xl text-sm text-slate-100 focus:outline-none focus:border-emerald-500`}
              required
            />
            {errors.account_name && (
              <p className="mt-1 text-xs text-rose-400">{errors.account_name[0]}</p>
            )}
          </div>

          {/* Normal Balance */}
          <div>
            <label className="block text-xs font-semibold text-slate-300 mb-1">
              Saldo Normal (Normal Balance)
            </label>
            <div className="grid grid-cols-2 gap-3">
              <label
                className={`flex items-center justify-center gap-2 p-2.5 rounded-xl border text-xs font-medium cursor-pointer transition-all ${
                  formData.normal_balance === 'debit'
                    ? 'bg-emerald-500/10 border-emerald-500/40 text-emerald-400'
                    : 'bg-slate-950 border-slate-800 text-slate-400'
                }`}
              >
                <input
                  type="radio"
                  name="normal_balance"
                  value="debit"
                  checked={formData.normal_balance === 'debit'}
                  onChange={(e) => setFormData({ ...formData, normal_balance: e.target.value })}
                  className="hidden"
                />
                Debit
              </label>

              <label
                className={`flex items-center justify-center gap-2 p-2.5 rounded-xl border text-xs font-medium cursor-pointer transition-all ${
                  formData.normal_balance === 'credit'
                    ? 'bg-emerald-500/10 border-emerald-500/40 text-emerald-400'
                    : 'bg-slate-950 border-slate-800 text-slate-400'
                }`}
              >
                <input
                  type="radio"
                  name="normal_balance"
                  value="credit"
                  checked={formData.normal_balance === 'credit'}
                  onChange={(e) => setFormData({ ...formData, normal_balance: e.target.value })}
                  className="hidden"
                />
                Kredit
              </label>
            </div>
          </div>

          {/* Deskripsi */}
          <div>
            <label className="block text-xs font-semibold text-slate-300 mb-1">
              Keterangan Tambahan
            </label>
            <textarea
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              placeholder="Catatan fungsi atau peruntukan akun ini..."
              rows={2}
              className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-100 focus:outline-none focus:border-emerald-500"
            />
          </div>

          {/* Status Aktif */}
          <div className="flex items-center justify-between p-3 bg-slate-950 border border-slate-800/80 rounded-xl">
            <div>
              <span className="text-xs font-semibold text-slate-200">Status Akun</span>
              <p className="text-[11px] text-slate-500">Akun nonaktif tidak bisa digunakan pada transaksi jurnal baru</p>
            </div>
            <label className="relative inline-flex items-center cursor-pointer">
              <input
                type="checkbox"
                checked={formData.is_active}
                onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                className="sr-only peer"
              />
              <div className="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
            </label>
          </div>

          {/* Buttons */}
          <div className="pt-2 flex items-center justify-end gap-3 border-t border-slate-800">
            <button
              type="button"
              onClick={onClose}
              disabled={isSubmitting}
              className="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white bg-slate-800/50 hover:bg-slate-800 border border-slate-700/50 transition-all cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={isSubmitting}
              className="px-5 py-2 rounded-xl text-xs font-semibold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition-all cursor-pointer shadow-lg shadow-emerald-500/20 disabled:opacity-50"
            >
              {isSubmitting ? 'Menyimpan...' : account ? 'Simpan Perubahan' : 'Tambah Akun'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

