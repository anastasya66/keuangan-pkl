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
      assets: 'bg-emerald-50 text-emerald-700 border-emerald-200',
      liabilities: 'bg-amber-50 text-amber-700 border-amber-200',
      equity: 'bg-purple-50 text-purple-700 border-purple-200',
      revenue: 'bg-sky-50 text-sky-700 border-sky-200',
      expenses: 'bg-rose-50 text-rose-700 border-rose-200',
    };
    return badges[type] || 'bg-slate-100 text-slate-700 border-slate-200';
  };

  return (
    <div className="space-y-6">
      {/* Toast Notification */}
      {toast && (
        <div
          className={`p-4 rounded-xl border flex items-center justify-between text-sm shadow-md transition-all ${
            toast.type === 'error'
              ? 'bg-rose-50 border-rose-200 text-rose-800'
              : 'bg-emerald-50 border-emerald-200 text-emerald-800'
          }`}
        >
          <div className="flex items-center gap-2">
            <span>{toast.type === 'error' ? '⚠️' : '✅'}</span>
            <span className="font-medium">{toast.message}</span>
          </div>
          <button
            type="button"
            onClick={() => setToast(null)}
            className="text-xs text-[#5A7C85] hover:text-[#16323B] cursor-pointer"
          >
            ✕
          </button>
        </div>
      )}

      {/* Header Page */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-extrabold tracking-tight text-[#16323B] flex items-center gap-2">
            <span>Neraca Saldo (Trial Balance)</span>
          </h1>
          <p className="text-sm text-[#5A7C85] mt-1">
            Daftar saldo debit dan kredit seluruh akun untuk memverifikasi keseimbangan persamaan akuntansi.
          </p>
        </div>

        <button
          type="button"
          onClick={() => window.print()}
          className="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold bg-white hover:bg-[#F3FBFC] text-[#16323B] border border-[#D7EBEF] transition-all cursor-pointer flex items-center gap-2 shadow-xs"
        >
          <span>🖨️ Cetak Laporan</span>
        </button>
      </div>

      {/* Control Panel: As Of Date & Kategori */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-4 shadow-xs flex flex-col md:flex-row items-center justify-between gap-3">
        <div className="flex flex-wrap items-center gap-3 w-full md:w-auto">
          <div>
            <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
              Per Tanggal (As of Date)
            </label>
            <input
              type="date"
              value={asOfDate}
              onChange={(e) => setAsOfDate(e.target.value)}
              className="px-3 py-2 bg-[#F3FBFC] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:bg-white focus:outline-none focus:border-[#38BFE0] focus:ring-2 focus:ring-[#38BFE0]/20 transition-all"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
              Filter Kategori Akun
            </label>
            <select
              value={accountType}
              onChange={(e) => setAccountType(e.target.value)}
              className="px-3 py-2 bg-[#F3FBFC] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:bg-white focus:outline-none focus:border-[#38BFE0] focus:ring-2 focus:ring-[#38BFE0]/20 transition-all"
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
          className="w-full md:w-auto px-5 py-2.5 rounded-xl text-xs font-semibold bg-[#27C5E5] hover:bg-[#38BFE0] text-white transition-all cursor-pointer shadow-md shadow-[#27C5E5]/20"
        >
          Muat Laporan
        </button>
      </div>

      {/* Equilibrium Status Indicator Card */}
      {reportData && (
        <div
          className={`p-4 rounded-2xl border flex flex-col sm:flex-row items-center justify-between gap-4 shadow-xs transition-all ${
            reportData.is_balanced
              ? 'bg-emerald-50 border-emerald-200 text-emerald-900'
              : 'bg-rose-50 border-rose-200 text-rose-900'
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
              <p className="text-xs opacity-80 mt-0.5">
                {reportData.is_balanced
                  ? 'Total nilai debit sama persis dengan total kredit dari seluruh jurnal yang diposting.'
                  : `Terdapat selisih sebesar ${formatRupiah(reportData.difference)}. Periksa kembali jurnal unbalance.`}
              </p>
            </div>
          </div>

          <div className="flex items-center gap-4 text-xs font-mono">
            <div className="text-right">
              <span className="block text-[10px] opacity-70">Total Debit</span>
              <span className="font-bold text-sm text-[#36B878]">{formatRupiah(reportData.total_debit)}</span>
            </div>
            <span className="text-slate-300">|</span>
            <div className="text-right">
              <span className="block text-[10px] opacity-70">Total Kredit</span>
              <span className="font-bold text-sm text-[#27C5E5]">{formatRupiah(reportData.total_credit)}</span>
            </div>
          </div>
        </div>
      )}

      {/* Trial Balance Table */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl overflow-hidden shadow-xs">
        <div className="px-6 py-4 border-b border-[#D7EBEF] bg-[#F3FBFC] flex items-center justify-between">
          <h2 className="text-sm font-bold text-[#16323B]">
            Neraca Saldo per {asOfDate}
          </h2>
          <span className="text-xs text-[#5A7C85] font-mono">
            {reportData?.accounts?.length || 0} akun
          </span>
        </div>

        {isLoading ? (
          <div className="py-16 text-center text-[#5A7C85]">
            <div className="inline-block w-6 h-6 border-2 border-[#27C5E5] border-t-transparent rounded-full animate-spin mb-2"></div>
            <p className="text-xs">Menghitung saldo akun neraca...</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-[#F3FBFC] text-[#5A7C85] font-semibold border-b border-[#D7EBEF]">
                <tr>
                  <th className="px-5 py-3 w-32 font-mono">Kode Akun</th>
                  <th className="px-5 py-3">Nama Akun</th>
                  <th className="px-5 py-3 w-36 text-center">Kategori</th>
                  <th className="px-5 py-3 w-44 text-right font-mono">Saldo Debit (Rp)</th>
                  <th className="px-5 py-3 w-44 text-right font-mono">Saldo Kredit (Rp)</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#D7EBEF]/60">
                {reportData?.accounts?.map((row) => (
                  <tr key={row.account_id} className="hover:bg-[#F3FBFC]/50 transition-colors">
                    <td className="px-5 py-3 font-mono font-bold text-[#27C5E5]">
                      {row.account_code}
                    </td>
                    <td className="px-5 py-3 font-semibold text-[#16323B]">
                      {row.account_name}
                      {!row.is_active && (
                        <span className="ml-2 text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-500">
                          Nonaktif
                        </span>
                      )}
                    </td>
                    <td className="px-5 py-3 text-center">
                      <span className={`inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold border ${getTypeBadge(row.account_type)}`}>
                        {row.account_type}
                      </span>
                    </td>
                    <td className="px-5 py-3 text-right font-mono font-bold text-[#36B878]">
                      {row.debit_balance > 0 ? formatRupiah(row.debit_balance) : '-'}
                    </td>
                    <td className="px-5 py-3 text-right font-mono font-bold text-[#27C5E5]">
                      {row.credit_balance > 0 ? formatRupiah(row.credit_balance) : '-'}
                    </td>
                  </tr>
                ))}
              </tbody>
              <tfoot className="bg-[#F3FBFC] font-bold border-t-2 border-[#D7EBEF] text-sm">
                <tr>
                  <td colSpan={3} className="px-5 py-4 text-right text-[#16323B]">
                    Grand Total Neraca Saldo:
                  </td>
                  <td className="px-5 py-4 text-right font-mono text-[#36B878] font-extrabold">
                    {formatRupiah(reportData?.total_debit || 0)}
                  </td>
                  <td className="px-5 py-4 text-right font-mono text-[#27C5E5] font-extrabold">
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
