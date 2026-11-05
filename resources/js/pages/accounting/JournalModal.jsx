import { useEffect, useState } from 'react';
import accountingService from '../../services/accountingService';
import { formatRupiah } from '../../utils/currency';
import { IconJournal } from '../../components/common/Icons';

export default function JournalModal({ isOpen, onClose, onSubmit, journal, isSubmitting }) {
  const [journalDate, setJournalDate] = useState(new Date().toISOString().split('T')[0]);
  const [description, setDescription] = useState('');
  const [referenceNumber, setReferenceNumber] = useState('');
  const [items, setItems] = useState([
    { account_id: '', description: '', debit: '', credit: '' },
    { account_id: '', description: '', debit: '', credit: '' },
  ]);

  const [activeAccounts, setActiveAccounts] = useState([]);
  const [errors, setErrors] = useState({});

  useEffect(() => {
    if (isOpen) {
      accountingService.getAccounts({ all: 'true', is_active: 'true' })
        .then((res) => {
          if (res?.data) setActiveAccounts(res.data);
        })
        .catch(() => {});

      if (journal) {
        setJournalDate(journal.journal_date || new Date().toISOString().split('T')[0]);
        setDescription(journal.description || '');
        setReferenceNumber(journal.reference_number || '');
        if (journal.details?.length >= 2) {
          setItems(journal.details.map((d) => ({
            account_id: d.account_id,
            description: d.description || '',
            debit: d.debit > 0 ? d.debit : '',
            credit: d.credit > 0 ? d.credit : '',
          })));
        }
      } else {
        setJournalDate(new Date().toISOString().split('T')[0]);
        setDescription('');
        setReferenceNumber('');
        setItems([
          { account_id: '', description: '', debit: '', credit: '' },
          { account_id: '', description: '', debit: '', credit: '' },
        ]);
      }
      setErrors({});
    }
  }, [journal, isOpen]);

  if (!isOpen) return null;

  const handleAddItem = () => {
    setItems((prev) => [...prev, { account_id: '', description: '', debit: '', credit: '' }]);
  };

  const handleRemoveItem = (index) => {
    if (items.length <= 2) return;
    setItems((prev) => prev.filter((_, idx) => idx !== index));
  };

  const handleItemChange = (index, field, value) => {
    setItems((prev) => {
      const next = [...prev];
      next[index] = { ...next[index], [field]: value };

      if (field === 'debit' && value !== '' && parseFloat(value) > 0) {
        next[index].credit = '';
      }
      if (field === 'credit' && value !== '' && parseFloat(value) > 0) {
        next[index].debit = '';
      }

      return next;
    });
  };

  const totalDebit = items.reduce((sum, item) => sum + (parseFloat(item.debit) || 0), 0);
  const totalCredit = items.reduce((sum, item) => sum + (parseFloat(item.credit) || 0), 0);
  const difference = Math.abs(totalDebit - totalCredit);
  const isBalanced = Math.round(difference * 100) === 0 && totalDebit > 0;

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});

    if (!isBalanced) {
      setErrors({ items: ['Total debit dan kredit harus seimbang (tidak boleh ada selisih).'] });
      return;
    }

    const payload = {
      journal_date: journalDate,
      description,
      reference_number: referenceNumber || null,
      items: items.map((i) => ({
        account_id: parseInt(i.account_id, 10),
        description: i.description || null,
        debit: parseFloat(i.debit) || 0,
        credit: parseFloat(i.credit) || 0,
      })),
    };

    const res = await onSubmit(payload);
    if (res?.errors) {
      setErrors(res.errors);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#10262E]/35 backdrop-blur-xs animate-backdrop overflow-y-auto">
      <div className="relative w-full max-w-4xl bg-white border border-[#D7EBEF] rounded-3xl shadow-2xl overflow-hidden my-8 animate-modal-pop">
        {/* Header */}
        <div className="px-6 py-4 border-b border-[#D7EBEF] bg-[#F3FBFC] flex items-center justify-between">
          <div className="flex items-center gap-3">
            <span className="p-2 rounded-xl bg-white text-[#27C5E5] border border-[#D7EBEF] shadow-2xs">
              <IconJournal className="w-5 h-5" />
            </span>
            <div>
              <h3 className="text-base sm:text-lg font-bold text-[#16323B]">
                {journal ? 'Ubah Catatan Transaksi (Draf)' : 'Catat Transaksi Keuangan Baru'}
              </h3>
              <p className="text-xs text-[#5A7C85] mt-0.5">Pencatatan keuangan toko dengan saldo seimbang</p>
            </div>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="w-8 h-8 rounded-xl bg-white border border-[#D7EBEF] text-[#5A7C85] hover:text-[#16323B] hover:bg-[#F3FBFC] transition-colors flex items-center justify-center cursor-pointer btn-press"
          >
            ✕
          </button>
        </div>

        {/* Form Body */}
        <form onSubmit={handleSubmit} className="p-6 space-y-5">
          {/* Header Data */}
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1">
                Tanggal Transaksi <span className="text-rose-500">*</span>
              </label>
              <input
                type="date"
                value={journalDate}
                onChange={(e) => setJournalDate(e.target.value)}
                className="w-full px-3 py-2 bg-[#F3FBFC] border border-[#D7EBEF] rounded-xl text-sm text-[#16323B] focus:bg-white focus:outline-none focus:border-[#38BFE0] focus:ring-2 focus:ring-[#38BFE0]/20 transition-all input-focus-glow"
                required
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1">
                No. Bukti / Nota (Opsional)
              </label>
              <input
                type="text"
                value={referenceNumber}
                onChange={(e) => setReferenceNumber(e.target.value)}
                placeholder="No. Nota / Faktur / Kuitansi"
                className="w-full px-3 py-2 bg-[#F3FBFC] border border-[#D7EBEF] rounded-xl text-sm text-[#16323B] placeholder-[#5A7C85]/60 focus:bg-white focus:outline-none focus:border-[#38BFE0] focus:ring-2 focus:ring-[#38BFE0]/20 transition-all input-focus-glow"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#16323B] mb-1">
                Status Catatan
              </label>
              <div className="px-3 py-2 bg-amber-50 border border-amber-200/80 rounded-xl text-xs font-medium text-amber-800 flex items-center gap-2">
                <span className="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                <span>Draf (Dapat diubah kembali)</span>
              </div>
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-[#16323B] mb-1">
              Keterangan Transaksi <span className="text-rose-500">*</span>
            </label>
            <input
              type="text"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="Contoh: Penerimaan pembayaran penjualan tunai toko"
              className="w-full px-3 py-2 bg-[#F3FBFC] border border-[#D7EBEF] rounded-xl text-sm text-[#16323B] placeholder-[#5A7C85]/60 focus:bg-white focus:outline-none focus:border-[#38BFE0] focus:ring-2 focus:ring-[#38BFE0]/20 transition-all input-focus-glow"
              required
            />
          </div>

          {/* Baris Transaksi */}
          <div className="space-y-3">
            <div className="flex items-center justify-between">
              <span className="text-xs font-bold uppercase tracking-wider text-[#16323B]">
                Rincian Akun (Minimal 2 Baris)
              </span>
              <button
                type="button"
                onClick={handleAddItem}
                className="px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#EAF8FA] border border-[#A9DDE7] text-[#27C5E5] hover:bg-[#38BFE0] hover:text-white transition-all cursor-pointer btn-press"
              >
                + Tambah Baris
              </button>
            </div>

            <div className="overflow-x-auto border border-[#D7EBEF] rounded-xl bg-white shadow-xs">
              <table className="w-full text-left text-xs">
                <thead className="bg-[#F3FBFC] text-[#5A7C85] font-semibold border-b border-[#D7EBEF]">
                  <tr>
                    <th className="px-3 py-2.5 w-1/3">Pilih Akun Kas / Beban</th>
                    <th className="px-3 py-2.5">Catatan Tambahan</th>
                    <th className="px-3 py-2.5 w-32 text-right">Debit (Rp)</th>
                    <th className="px-3 py-2.5 w-32 text-right">Kredit (Rp)</th>
                    <th className="px-3 py-2.5 w-10 text-center"></th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-[#D7EBEF]/60">
                  {items.map((item, idx) => (
                    <tr key={idx} className="hover:bg-[#F3FBFC]/50 transition-colors">
                      <td className="p-2">
                        <select
                          value={item.account_id}
                          onChange={(e) => handleItemChange(idx, 'account_id', e.target.value)}
                          className="w-full px-2.5 py-1.5 bg-[#F3FBFC] border border-[#D7EBEF] rounded-lg text-xs text-[#16323B] focus:bg-white focus:outline-none focus:border-[#38BFE0]"
                          required
                        >
                          <option value="">-- Pilih Rekening Akun --</option>
                          {activeAccounts.map((acc) => (
                            <option key={acc.id} value={acc.id}>
                              {acc.account_code} - {acc.account_name} ({acc.account_type})
                            </option>
                          ))}
                        </select>
                      </td>
                      <td className="p-2">
                        <input
                          type="text"
                          value={item.description}
                          onChange={(e) => handleItemChange(idx, 'description', e.target.value)}
                          placeholder="Catatan baris..."
                          className="w-full px-2.5 py-1.5 bg-[#F3FBFC] border border-[#D7EBEF] rounded-lg text-xs text-[#16323B] placeholder-[#5A7C85]/60 focus:bg-white focus:outline-none focus:border-[#38BFE0]"
                        />
                      </td>
                      <td className="p-2">
                        <input
                          type="number"
                          step="0.01"
                          min="0"
                          value={item.debit}
                          onChange={(e) => handleItemChange(idx, 'debit', e.target.value)}
                          placeholder="0"
                          className="w-full px-2.5 py-1.5 bg-[#F3FBFC] border border-[#D7EBEF] rounded-lg text-xs text-right text-[#36B878] font-semibold font-mono focus:bg-white focus:outline-none focus:border-[#38BFE0]"
                        />
                      </td>
                      <td className="p-2">
                        <input
                          type="number"
                          step="0.01"
                          min="0"
                          value={item.credit}
                          onChange={(e) => handleItemChange(idx, 'credit', e.target.value)}
                          placeholder="0"
                          className="w-full px-2.5 py-1.5 bg-[#F3FBFC] border border-[#D7EBEF] rounded-lg text-xs text-right text-[#27C5E5] font-semibold font-mono focus:bg-white focus:outline-none focus:border-[#38BFE0]"
                        />
                      </td>
                      <td className="p-2 text-center">
                        {items.length > 2 && (
                          <button
                            type="button"
                            onClick={() => handleRemoveItem(idx)}
                            className="p-1 rounded-md text-[#5A7C85] hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer btn-press"
                            title="Hapus baris"
                          >
                            ✕
                          </button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
                <tfoot className="bg-[#F3FBFC] font-semibold border-t border-[#D7EBEF]">
                  <tr>
                    <td colSpan={2} className="px-3 py-2.5 text-right text-[#5A7C85]">
                      Total
                    </td>
                    <td className="px-3 py-2.5 text-right font-mono font-bold text-[#36B878]">
                      {formatRupiah(totalDebit)}
                    </td>
                    <td className="px-3 py-2.5 text-right font-mono font-bold text-[#27C5E5]">
                      {formatRupiah(totalCredit)}
                    </td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>

            {/* Equilibrium Banner */}
            <div
              className={`p-3.5 rounded-xl border flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs font-semibold ${
                isBalanced
                  ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
                  : 'bg-rose-50 border-rose-200 text-rose-800'
              }`}
            >
              <div className="flex items-center gap-2">
                <span>{isBalanced ? '⚖️' : '⚠️'}</span>
                <span>
                  {isBalanced
                    ? 'Debit dan Kredit Seimbang (Sudah Pas)'
                    : `Belum Seimbang: Masih ada selisih ${formatRupiah(difference)}`}
                </span>
              </div>
              <span className="font-mono text-[11px] opacity-90">
                Debit: {formatRupiah(totalDebit)} | Kredit: {formatRupiah(totalCredit)}
              </span>
            </div>

            {errors.items && (
              <p className="text-xs text-rose-600 mt-1 font-medium">{errors.items[0]}</p>
            )}
          </div>

          {/* Buttons */}
          <div className="pt-3 flex items-center justify-end gap-3 border-t border-[#D7EBEF]">
            <button
              type="button"
              onClick={onClose}
              disabled={isSubmitting}
              className="px-4 py-2.5 rounded-xl text-xs font-semibold text-[#5A7C85] hover:text-[#16323B] bg-white hover:bg-[#F3FBFC] border border-[#D7EBEF] transition-all cursor-pointer btn-press"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={isSubmitting || !isBalanced}
              className="px-5 py-2.5 rounded-xl text-xs font-semibold bg-[#27C5E5] hover:bg-[#38BFE0] text-white transition-all cursor-pointer shadow-md shadow-[#27C5E5]/20 disabled:opacity-50 btn-press"
            >
              {isSubmitting ? 'Menyimpan...' : journal ? 'Simpan Perubahan Draf' : 'Simpan Draf Transaksi'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
