import { useEffect, useState } from 'react';
import accountingService from '../../services/accountingService';
import { formatRupiah } from '../../utils/currency';

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
      // Fetch active accounts for select options
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

      // Aturan double-entry: jika isi debit > 0, otomatis kosongkan kredit
      if (field === 'debit' && value !== '' && parseFloat(value) > 0) {
        next[index].credit = '';
      }
      // Sebaliknya, jika isi kredit > 0, otomatis kosongkan debit
      if (field === 'credit' && value !== '' && parseFloat(value) > 0) {
        next[index].debit = '';
      }

      return next;
    });
  };

  // Hitung total debit dan kredit
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
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-fade-in overflow-y-auto">
      <div className="relative w-full max-w-4xl bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-8">
        {/* Header */}
        <div className="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
          <div>
            <h3 className="text-lg font-bold text-white">
              {journal ? 'Edit Jurnal Umum (Draft)' : 'Buat Jurnal Umum Baru'}
            </h3>
            <p className="text-xs text-slate-400">Pencatatan akuntansi berbasis prinsip double-entry</p>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="text-slate-400 hover:text-white text-lg font-mono leading-none cursor-pointer"
          >
            ✕
          </button>
        </div>

        {/* Form Body */}
        <form onSubmit={handleSubmit} className="p-6 space-y-5">
          {/* Header Data */}
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label className="block text-xs font-semibold text-slate-300 mb-1">
                Tanggal Jurnal <span className="text-rose-400">*</span>
              </label>
              <input
                type="date"
                value={journalDate}
                onChange={(e) => setJournalDate(e.target.value)}
                className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-100 focus:outline-none focus:border-emerald-500"
                required
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-300 mb-1">
                No. Referensi Dokumen
              </label>
              <input
                type="text"
                value={referenceNumber}
                onChange={(e) => setReferenceNumber(e.target.value)}
                placeholder="Faktur / Kuitansi / Bukti Kas"
                className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-100 focus:outline-none focus:border-emerald-500"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-300 mb-1">
                Status Awal
              </label>
              <div className="px-3 py-2 bg-slate-950/60 border border-slate-800 rounded-xl text-xs font-mono text-amber-400 flex items-center gap-2">
                <span className="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                <span>Draft (Dapat diedit & diposting kemudian)</span>
              </div>
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-300 mb-1">
              Keterangan Transaksi Jurnal <span className="text-rose-400">*</span>
            </label>
            <input
              type="text"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="Contoh: Penerimaan setoran modal awal secara tunai"
              className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-100 focus:outline-none focus:border-emerald-500"
              required
            />
          </div>

          {/* Baris Transaksi (Double Entry Lines) */}
          <div className="space-y-3">
            <div className="flex items-center justify-between">
              <span className="text-xs font-bold uppercase tracking-wider text-slate-300">
                Detail Baris Transaksi (Minimal 2 Baris)
              </span>
              <button
                type="button"
                onClick={handleAddItem}
                className="px-3 py-1 rounded-lg text-xs font-semibold bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/20 transition-all cursor-pointer"
              >
                + Tambah Baris
              </button>
            </div>

            <div className="overflow-x-auto border border-slate-800 rounded-xl bg-slate-950/40">
              <table className="w-full text-left text-xs">
                <thead className="bg-slate-950 text-slate-400 font-semibold border-b border-slate-800">
                  <tr>
                    <th className="px-3 py-2.5 w-1/3">Akun Akuntansi</th>
                    <th className="px-3 py-2.5">Keterangan Baris (Opsional)</th>
                    <th className="px-3 py-2.5 w-32 text-right">Debit (Rp)</th>
                    <th className="px-3 py-2.5 w-32 text-right">Kredit (Rp)</th>
                    <th className="px-3 py-2.5 w-12 text-center"></th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-800/60">
                  {items.map((item, idx) => (
                    <tr key={idx} className="hover:bg-slate-800/20">
                      <td className="p-2">
                        <select
                          value={item.account_id}
                          onChange={(e) => handleItemChange(idx, 'account_id', e.target.value)}
                          className="w-full px-2.5 py-1.5 bg-slate-950 border border-slate-800 rounded-lg text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
                          required
                        >
                          <option value="">-- Pilih Akun --</option>
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
                          placeholder="Keterangan baris..."
                          className="w-full px-2.5 py-1.5 bg-slate-950 border border-slate-800 rounded-lg text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
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
                          className="w-full px-2.5 py-1.5 bg-slate-950 border border-slate-800 rounded-lg text-xs text-right text-emerald-400 font-mono focus:outline-none focus:border-emerald-500"
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
                          className="w-full px-2.5 py-1.5 bg-slate-950 border border-slate-800 rounded-lg text-xs text-right text-cyan-400 font-mono focus:outline-none focus:border-emerald-500"
                        />
                      </td>
                      <td className="p-2 text-center">
                        {items.length > 2 && (
                          <button
                            type="button"
                            onClick={() => handleRemoveItem(idx)}
                            className="p-1 rounded-md text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 cursor-pointer"
                            title="Hapus baris"
                          >
                            ✕
                          </button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
                <tfoot className="bg-slate-950 font-semibold border-t border-slate-800">
                  <tr>
                    <td colSpan={2} className="px-3 py-2.5 text-right text-slate-400">
                      Total
                    </td>
                    <td className="px-3 py-2.5 text-right font-mono text-emerald-400">
                      {formatRupiah(totalDebit)}
                    </td>
                    <td className="px-3 py-2.5 text-right font-mono text-cyan-400">
                      {formatRupiah(totalCredit)}
                    </td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>

            {/* Equilibrium Banner */}
            <div
              className={`p-3 rounded-xl border flex items-center justify-between text-xs font-semibold ${
                isBalanced
                  ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-300'
                  : 'bg-rose-500/10 border-rose-500/30 text-rose-300'
              }`}
            >
              <div className="flex items-center gap-2">
                <span>{isBalanced ? '⚖️' : '⚠️'}</span>
                <span>
                  {isBalanced
                    ? 'Debit dan Kredit Seimbang (Balance)'
                    : `Tidak Seimbang: Terdapat selisih ${formatRupiah(difference)}`}
                </span>
              </div>
              <span className="font-mono text-[11px]">
                Debit: {formatRupiah(totalDebit)} | Kredit: {formatRupiah(totalCredit)}
              </span>
            </div>

            {errors.items && (
              <p className="text-xs text-rose-400 mt-1 font-medium">{errors.items[0]}</p>
            )}
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
              disabled={isSubmitting || !isBalanced}
              className="px-5 py-2 rounded-xl text-xs font-semibold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition-all cursor-pointer shadow-lg shadow-emerald-500/20 disabled:opacity-50"
            >
              {isSubmitting ? 'Menyimpan...' : journal ? 'Perbarui Jurnal Draft' : 'Simpan Jurnal Draft'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
