import { useCallback, useEffect, useState } from 'react';
import useAuth from '../../hooks/useAuth';
import accountingService from '../../services/accountingService';
import { formatRupiah } from '../../utils/currency';

export function TrialBalancePage() {
  const { user } = useAuth();

  const [asOfDate, setAsOfDate] = useState(new Date().toISOString().split('T')[0]);
  const [accountType, setAccountType] = useState('');
  const [reportData, setReportData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [toast, setToast] = useState(null);

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 4000);
  };

  const fetchTrialBalance = useCallback(async () => {
    setIsLoading(true);
    try {
      const params = { as_of_date: asOfDate };
      if (accountType) params.account_type = accountType;

      const response = await accountingService.getTrialBalance(params);
      if (response.status === 'success') {
        setReportData(response.data);
      }
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal memuat neraca saldo', 'error');
    } finally {
      setIsLoading(false);
    }
  }, [asOfDate, accountType]);

  useEffect(() => {
    fetchTrialBalance();
  }, [fetchTrialBalance]);

  const getTypeBadge = (type) => {
    const badges = {
      assets: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
      liabilities: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
      equity: 'bg-purple-500/10 text-purple-400 border-purple-500/30',
      revenue: 'bg-cyan-500/10 text-cyan-400 border-cyan-500/30',
      expenses: 'bg-rose-500/10 text-rose-400 border-rose-500/30',
    };
    return badges[type] || 'bg-slate-800 text-slate-400 border-slate-700';
  };

  return (
    <div className="space-y-6">
      {/* Toast Notification */}
      {toast && (
        <div
          className={`p-4 rounded-xl border flex items-center justify-between text-sm shadow-xl transition-all ${
            toast.type === 'error'
              ? 'bg-rose-500/10 border-rose-500/30 text-rose-300'
              : 'bg-emerald-500/10 border-emerald-500/30 text-emerald-300'
          }`}
        >
          <div className="flex items-center gap-2">
            <span>{toast.type === 'error' ? '⚠️' : '✅'}</span>
            <span className="font-medium">{toast.message}</span>
          </div>
          <button
            type="button"
            onClick={() => setToast(null)}
            className="text-xs text-slate-400 hover:text-white"
          >
            ✕
          </button>
        </div>
      )}

      {/* Header Page */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
            <span>Neraca Saldo (Trial Balance)</span>
          </h1>
          <p className="text-sm text-slate-400 mt-0.5">
            Daftar saldo debit dan kredit seluruh akun untuk memverifikasi keseimbangan persamaan akuntansi.
          </p>
        </div>

        <button
          type="button"
          onClick={() => window.print()}
          className="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition-all cursor-pointer flex items-center gap-2"
        >
          <span>🖨️ Cetak Laporan</span>
        </button>
      </div>

      {/* Control Panel: As Of Date & Kategori */}
      <div className="bg-slate-900 border border-slate-800 rounded-2xl p-4 flex flex-col md:flex-row items-center justify-between gap-3">
        <div className="flex flex-wrap items-center gap-3 w-full md:w-auto">
          <div>
            <label className="block text-xs font-semibold text-slate-400 mb-1">
              Per Tanggal (As of Date)
            </label>
            <input
              type="date"
              value={asOfDate}
              onChange={(e) => setAsOfDate(e.target.value)}
              className="px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-400 mb-1">
              Filter Kategori Akun
            </label>
            <select
              value={accountType}
              onChange={(e) => setAccountType(e.target.value)}
              className="px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
            >
              <option value="">Semua Kategori</option>
              <option value="assets">Assets (Aset)</option>
              <option value="liabilities">Liabilities (Kewajiban)</option>
              <option value="equity">Equity (Modal)</option>
              <option value="revenue">Revenue (Pendapatan)</option>
              <option value="expenses">Expenses (Beban)</option>
            </select>
          </div>
        </div>

        <button
          type="button"
          onClick={fetchTrialBalance}
          className="w-full md:w-auto px-5 py-2 rounded-xl text-xs font-semibold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition-all cursor-pointer shadow-lg shadow-emerald-500/20"
        >
          Muat Laporan
        </button>
      </div>

      {/* Equilibrium Status Indicator Card */}
      {reportData && (
        <div
          className={`p-4 rounded-2xl border flex flex-col sm:flex-row items-center justify-between gap-4 shadow-xl transition-all ${
            reportData.is_balanced
              ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-300'
              : 'bg-rose-500/10 border-rose-500/30 text-rose-300'
          }`}
        >
          <div className="flex items-center gap-3">
            <span className="text-2xl">{reportData.is_balanced ? '⚖️' : '⚠️'}</span>
            <div>
              <h3 className="font-bold text-sm">
                {reportData.is_balanced
                  ? 'Neraca Saldo Seimbang (Equilibrium Balance)'
                  : 'Peringatan: Neraca Saldo Tidak Seimbang!'}
              </h3>
              <p className="text-xs opacity-80">
                {reportData.is_balanced
                  ? 'Total nilai debit sama persis dengan total kredit dari seluruh jurnal yang diposting.'
                  : `Terdapat selisih sebesar ${formatRupiah(reportData.difference)}. Periksa kembali jurnal unbalance.`}
              </p>
            </div>
          </div>

          <div className="flex items-center gap-4 text-xs font-mono">
            <div className="text-right">
              <span className="block text-[10px] opacity-70">Total Debit</span>
              <span className="font-bold text-sm">{formatRupiah(reportData.total_debit)}</span>
            </div>
            <span className="text-slate-600">|</span>
            <div className="text-right">
              <span className="block text-[10px] opacity-70">Total Kredit</span>
              <span className="font-bold text-sm">{formatRupiah(reportData.total_credit)}</span>
            </div>
          </div>
        </div>
      )}

      {/* Trial Balance Table */}
      <div className="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <div className="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
          <h2 className="text-base font-bold text-white">
            Neraca Saldo per {asOfDate}
          </h2>
          <span className="text-xs text-slate-400 font-mono">
            {reportData?.accounts?.length || 0} akun
          </span>
        </div>

        {isLoading ? (
          <div className="py-16 text-center text-slate-400">
            <div className="inline-block w-6 h-6 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mb-2"></div>
            <p className="text-xs">Menghitung saldo akun neraca...</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-slate-950 text-slate-400 font-semibold border-b border-slate-800">
                <tr>
                  <th className="px-5 py-3 w-32 font-mono">Kode Akun</th>
                  <th className="px-5 py-3">Nama Akun</th>
                  <th className="px-5 py-3 w-36 text-center">Kategori</th>
                  <th className="px-5 py-3 w-44 text-right font-mono">Saldo Debit (Rp)</th>
                  <th className="px-5 py-3 w-44 text-right font-mono">Saldo Kredit (Rp)</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800/60">
                {reportData?.accounts?.map((row) => (
                  <tr key={row.account_id} className="hover:bg-slate-800/20 transition-colors">
                    <td className="px-5 py-3 font-mono font-bold text-emerald-400">
                      {row.account_code}
                    </td>
                    <td className="px-5 py-3 font-semibold text-slate-200">
                      {row.account_name}
                      {!row.is_active && (
                        <span className="ml-2 text-[10px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-400">
                          Nonaktif
                        </span>
                      )}
                    </td>
                    <td className="px-5 py-3 text-center">
                      <span className={`inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold border ${getTypeBadge(row.account_type)}`}>
                        {row.account_type}
                      </span>
                    </td>
                    <td className="px-5 py-3 text-right font-mono font-semibold text-emerald-400">
                      {row.debit_balance > 0 ? formatRupiah(row.debit_balance) : '-'}
                    </td>
                    <td className="px-5 py-3 text-right font-mono font-semibold text-cyan-400">
                      {row.credit_balance > 0 ? formatRupiah(row.credit_balance) : '-'}
                    </td>
                  </tr>
                ))}
              </tbody>
              <tfoot className="bg-slate-950 font-bold border-t-2 border-slate-800 text-sm">
                <tr>
                  <td colSpan={3} className="px-5 py-4 text-right text-slate-300">
                    Grand Total Neraca Saldo:
                  </td>
                  <td className="px-5 py-4 text-right font-mono text-emerald-400">
                    {formatRupiah(reportData?.total_debit || 0)}
                  </td>
                  <td className="px-5 py-4 text-right font-mono text-cyan-400">
                    {formatRupiah(reportData?.total_credit || 0)}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}

export default TrialBalancePage;
