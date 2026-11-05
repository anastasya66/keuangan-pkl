import { useEffect, useState } from 'react';
import { IconAccounts } from '../../components/common/Icons';

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
    try {
      await onSubmit(formData);
    } catch (err) {
      if (err.response?.status === 422 && err.response?.data?.errors) {
        setErrors(err.response.data.errors);
      }
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#10262E]/35 backdrop-blur-xs animate-backdrop">
      <div className="relative w-full max-w-lg bg-white border border-[#D7EBEF] rounded-3xl shadow-2xl overflow-hidden animate-modal-pop">
        {/* Header */}
        <div className="px-6 py-4 border-b border-[#D7EBEF] flex items-center justify-between">
          <h3 className="text-base sm:text-lg font-bold text-[#10262E] flex items-center gap-2">
            <span className="p-2 rounded-xl bg-[#EAF8FA] text-[#27C5E5]">
              <IconAccounts className="w-5 h-5" />
            </span>
            <span>{account ? 'Ubah Rekening Akun' : 'Tambah Rekening Akun Baru'}</span>
          </h3>
          <button
            type="button"
            onClick={onClose}
            className="w-8 h-8 rounded-xl bg-[#F3FBFC] hover:bg-[#EAF8FA] text-[#8EA6AF] hover:text-[#16323B] transition-colors flex items-center justify-center cursor-pointer btn-press"
          >
            ✕
          </button>
        </div>

        {/* Form Body */}
        <form onSubmit={handleSubmit} className="p-6 space-y-4 text-xs">
          {/* Kode & Tipe */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
                Nomor Kode Akun <span className="text-rose-500">*</span>
              </label>
              <input
                type="text"
                value={formData.account_code}
                onChange={(e) => setFormData({ ...formData, account_code: e.target.value })}
                placeholder="Contoh: 1101, 4101"
                className={`w-full px-3.5 py-2.5 bg-[#F8FDFF] border ${
                  errors.account_code ? 'border-rose-300' : 'border-[#D7EBEF]'
                } rounded-xl text-[#16323B] font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all input-focus-glow`}
                required
              />
              {errors.account_code && (
                <p className="mt-1 text-[11px] text-rose-500 font-medium">{errors.account_code[0]}</p>
              )}
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
                Kategori Akun <span className="text-rose-500">*</span>
              </label>
              <select
                value={formData.account_type}
                onChange={handleTypeChange}
                className="w-full px-3.5 py-2.5 bg-[#F8FDFF] border border-[#D7EBEF] rounded-xl text-[#16323B] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all input-focus-glow"
              >
                <option value="assets">Aset (Kas, Bank, Piutang)</option>
                <option value="liabilities">Kewajiban (Hutang Toko)</option>
                <option value="equity">Modal (Ekuitas Pemilik)</option>
                <option value="revenue">Pendapatan (Penjualan Toko)</option>
                <option value="expenses">Beban (Biaya Operasional)</option>
              </select>
            </div>
          </div>

          {/* Nama Akun */}
          <div>
            <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
              Nama Rekening / Akun <span className="text-rose-500">*</span>
            </label>
            <input
              type="text"
              value={formData.account_name}
              onChange={(e) => setFormData({ ...formData, account_name: e.target.value })}
              placeholder="Contoh: Kas Toko, Rekening BCA, Beban Listrik"
              className={`w-full px-3.5 py-2.5 bg-[#F8FDFF] border ${
                errors.account_name ? 'border-rose-300' : 'border-[#D7EBEF]'
              } rounded-xl text-[#16323B] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all input-focus-glow`}
              required
            />
            {errors.account_name && (
              <p className="mt-1 text-[11px] text-rose-500 font-medium">{errors.account_name[0]}</p>
            )}
          </div>

          {/* Normal Balance */}
          <div>
            <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
              Posisi Saldo Normal
            </label>
            <div className="grid grid-cols-2 gap-3">
              <label
                className={`flex items-center justify-center gap-2 p-2.5 rounded-xl border text-xs font-bold cursor-pointer transition-all select-none btn-press ${
                  formData.normal_balance === 'debit'
                    ? 'bg-[#EAF8FA] border-[#38BFE0] text-[#167A93]'
                    : 'bg-[#F8FDFF] border-[#D7EBEF] text-[#577782]'
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
                Debit (Dr)
              </label>

              <label
                className={`flex items-center justify-center gap-2 p-2.5 rounded-xl border text-xs font-bold cursor-pointer transition-all select-none btn-press ${
                  formData.normal_balance === 'credit'
                    ? 'bg-[#EAF8FA] border-[#38BFE0] text-[#167A93]'
                    : 'bg-[#F8FDFF] border-[#D7EBEF] text-[#577782]'
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
                Kredit (Cr)
              </label>
            </div>
          </div>

          {/* Deskripsi */}
          <div>
            <label className="block text-xs font-semibold text-[#16323B] mb-1.5">Catatan Akun (Opsional)</label>
            <textarea
              rows="2"
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              placeholder="Keterangan singkat fungsi rekening akun ini..."
              className="w-full px-3.5 py-2.5 bg-[#F8FDFF] border border-[#D7EBEF] rounded-xl text-[#16323B] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#38BFE0]/25 transition-all input-focus-glow"
            />
          </div>

          {/* Status Aktif */}
          <div>
            <label className="inline-flex items-center gap-2.5 cursor-pointer select-none">
              <input
                type="checkbox"
                checked={formData.is_active}
                onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                className="w-4 h-4 rounded text-[#27C5E5] focus:ring-[#27C5E5] border-[#D7EBEF]"
              />
              <span className="text-xs font-semibold text-[#16323B]">Akun Aktif (Dapat dipilih dalam catatan jurnal)</span>
            </label>
          </div>

          {/* Footer Buttons */}
          <div className="pt-4 border-t border-[#D7EBEF] flex items-center justify-end gap-2.5">
            <button
              type="button"
              onClick={onClose}
              disabled={isSubmitting}
              className="px-4 py-2.5 rounded-xl bg-white hover:bg-[#F3FBFC] border border-[#D7EBEF] text-xs font-semibold text-[#577782] cursor-pointer btn-press"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={isSubmitting}
              className="px-5 py-2.5 rounded-xl bg-[#27C5E5] hover:bg-[#38BFE0] text-white font-semibold text-xs shadow-md shadow-[#27C5E5]/20 transition-all cursor-pointer disabled:opacity-50 btn-press"
            >
              {isSubmitting ? 'Menyimpan...' : account ? 'Simpan Perubahan' : 'Buat Akun'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
