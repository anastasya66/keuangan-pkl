import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';
import accountingService from '../../services/accountingService';
import { formatRupiah } from '../../utils/currency';

export function GeneralLedgerPage() {
  const { user } = useAuth();
  const isAdmin = user?.role === 'admin';
  const basePath = isAdmin ? '/admin/accounting' : '/staff/accounting';

  const [accounts, setAccounts] = useState([]);
  const [selectedAccountId, setSelectedAccountId] = useState('');
  const [startDate, setStartDate] = useState(
    new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0]
  );
  const [endDate, setEndDate] = useState(new Date().toISOString().split('T')[0]);

  const [ledgerData, setLedgerData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [toast, setToast] = useState(null);

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 4000);
  };

  // Fetch accounts list for dropdown
  useEffect(() => {
    accountingService.getAccounts({ all: 'true' })
      .then((res) => {
        if (res?.data && res.data.length > 0) {
          setAccounts(res.data);
          setSelectedAccountId(res.data[0].id.toString());
        }
      })
      .catch((err) => {
        showToast(err.response?.data?.message || 'Gagal memuat daftar akun', 'error');
      });
  }, []);

  // Fetch ledger data for selected account
  const fetchLedger = useCallback(async () => {
    if (!selectedAccountId) return;
    setIsLoading(true);
    try {
      const response = await accountingService.getAccountLedger(selectedAccountId, {
        start_date: startDate,
        end_date: endDate,
      });
      if (response.status === 'success') {
        setLedgerData(response.data);
      }
    } catch (error) {
      showToast(error.response?.data?.message || 'Gagal memuat buku besar', 'error');
    } finally {
      setIsLoading(false);
    }
  }, [selectedAccountId, startDate, endDate]);

  useEffect(() => {
    if (selectedAccountId) {
      fetchLedger();
    }
  }, [selectedAccountId, fetchLedger]);

  const selectedAccountObj = accounts.find((a) => a.id.toString() === selectedAccountId);

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
      <div>
        <h1 className="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
          <span>Buku Besar (General Ledger)</span>
        </h1>
        <p className="text-sm text-slate-400 mt-0.5">
          Riwayat mutasi transaksi posted dan akumulasi saldo berjalan per akun akuntansi.
        </p>
      </div>

      {/* Control Panel: Pilih Akun & Rentang Tanggal */}
      <div className="bg-slate-900 border border-slate-800 rounded-2xl p-5 flex flex-col md:flex-row items-center justify-between gap-4">
        {/* Dropdown Akun */}
        <div className="w-full md:w-1/2">
          <label className="block text-xs font-semibold text-slate-400 mb-1">
            Pilih Akun Akuntansi
          </label>
          <select
            value={selectedAccountId}
            onChange={(e) => setSelectedAccountId(e.target.value)}
            className="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-100 font-medium focus:outline-none focus:border-emerald-500 cursor-pointer"
          >
            {accounts.map((acc) => (
              <option key={acc.id} value={acc.id}>
                {acc.account_code} - {acc.account_name} ({acc.account_type_label || acc.account_type})
              </option>
            ))}
          </select>
        </div>

        {/* Date Filter */}
        <div className="flex items-end gap-2 w-full md:w-auto">
          <div>
            <label className="block text-xs font-semibold text-slate-400 mb-1">
              Dari Tanggal
            </label>
            <input
              type="date"
              value={startDate}
              onChange={(e) => setStartDate(e.target.value)}
              className="px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
            />
          </div>

          <span className="text-slate-600 text-xs pb-2.5">s/d</span>

          <div>
            <label className="block text-xs font-semibold text-slate-400 mb-1">
              Sampai Tanggal
            </label>
            <input
              type="date"
              value={endDate}
              onChange={(e) => setEndDate(e.target.value)}
              className="px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
            />
          </div>

          <button
            type="button"
            onClick={fetchLedger}
            className="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition-all cursor-pointer shadow-lg shadow-emerald-500/20"
          >
            Terapkan
          </button>
        </div>
      </div>

      {/* Summary KPI Cards */}
      {ledgerData && (
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div className="p-4 bg-slate-900 border border-slate-800 rounded-2xl space-y-1">
            <span className="text-xs text-slate-400">Saldo Awal Periode</span>
            <div className="text-lg font-bold font-mono text-slate-200">
              {formatRupiah(ledgerData.opening_balance)}
            </div>
            <span className="text-[10px] text-slate-500 block">Sebelum {startDate}</span>
          </div>

          <div className="p-4 bg-slate-900 border border-slate-800 rounded-2xl space-y-1">
            <span className="text-xs text-slate-400">Total Mutasi Debit</span>
            <div className="text-lg font-bold font-mono text-emerald-400">
              {formatRupiah(ledgerData.total_debit)}
            </div>
            <span className="text-[10px] text-slate-500 block">Selama periode</span>
          </div>

          <div className="p-4 bg-slate-900 border border-slate-800 rounded-2xl space-y-1">
            <span className="text-xs text-slate-400">Total Mutasi Kredit</span>
            <div className="text-lg font-bold font-mono text-cyan-400">
              {formatRupiah(ledgerData.total_credit)}
            </div>
            <span className="text-[10px] text-slate-500 block">Selama periode</span>
          </div>

          <div className="p-4 bg-slate-900 border border-emerald-500/30 rounded-2xl space-y-1 bg-gradient-to-br from-slate-900 to-emerald-950/20">
            <span className="text-xs text-emerald-400 font-semibold">Saldo Akhir Periode</span>
            <div className="text-lg font-bold font-mono text-white">
              {formatRupiah(ledgerData.closing_balance)}
            </div>
            <span className="text-[10px] text-slate-400 block uppercase">
              Normal Balance: {ledgerData.account.normal_balance}
            </span>
          </div>
        </div>
      )}

      {/* Ledger Table */}
      <div className="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <div className="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <h2 className="text-base font-bold text-white">
              Transaksi Buku Besar: {ledgerData?.account.account_code} - {ledgerData?.account.account_name}
            </h2>
            <span className="text-xs font-mono px-2 py-0.5 rounded-md bg-slate-950 text-slate-400 border border-slate-800 uppercase">
              {ledgerData?.account.account_type}
            </span>
          </div>
          <span className="text-xs text-slate-400 font-mono">
            {ledgerData?.entries?.length || 0} entri
          </span>
        </div>

        {isLoading ? (
          <div className="py-16 text-center text-slate-400">
            <div className="inline-block w-6 h-6 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mb-2"></div>
            <p className="text-xs">Memuat data mutasi buku besar...</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-slate-950 text-slate-400 font-semibold border-b border-slate-800">
                <tr>
                  <th className="px-4 py-3 w-28 text-center">Tanggal</th>
                  <th className="px-4 py-3 w-44 font-mono">No. Jurnal</th>
                  <th className="px-4 py-3">Keterangan</th>
                  <th className="px-4 py-3 w-36 text-right font-mono">Debit (Rp)</th>
                  <th className="px-4 py-3 w-36 text-right font-mono">Kredit (Rp)</th>
                  <th className="px-4 py-3 w-40 text-right font-mono">Saldo Berjalan (Rp)</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800/60">
                {/* Saldo Awal Baris */}
                <tr className="bg-slate-950/40 text-slate-400 font-semibold">
                  <td className="px-4 py-2.5 text-center font-mono">{startDate}</td>
                  <td className="px-4 py-2.5 font-mono text-slate-500">-</td>
                  <td className="px-4 py-2.5 italic">Saldo Awal (Opening Balance)</td>
                  <td className="px-4 py-2.5 text-right font-mono">-</td>
                  <td className="px-4 py-2.5 text-right font-mono">-</td>
                  <td className="px-4 py-2.5 text-right font-mono text-slate-200">
                    {formatRupiah(ledgerData?.opening_balance || 0)}
                  </td>
                </tr>

                {ledgerData?.entries?.map((row) => (
                  <tr key={row.id} className="hover:bg-slate-800/20 transition-colors">
                    <td className="px-4 py-3 text-center text-slate-300 font-mono">
                      {row.journal_date}
                    </td>
                    <td className="px-4 py-3">
                      <Link
                        to={`${basePath}/journals/${row.journal_id}`}
                        className="font-mono font-bold text-emerald-400 hover:underline"
                      >
                        {row.journal_number}
                      </Link>
                      {row.reference_number && (
                        <p className="text-[10px] text-slate-500 font-mono">Ref: {row.reference_number}</p>
                      )}
                    </td>
                    <td className="px-4 py-3 text-slate-200">
                      <span>{row.description}</span>
                    </td>
                    <td className="px-4 py-3 text-right font-mono font-semibold text-emerald-400">
                      {row.debit > 0 ? formatRupiah(row.debit) : '-'}
                    </td>
                    <td className="px-4 py-3 text-right font-mono font-semibold text-cyan-400">
                      {row.credit > 0 ? formatRupiah(row.credit) : '-'}
                    </td>
                    <td className="px-4 py-3 text-right font-mono font-bold text-slate-100">
                      {formatRupiah(row.running_balance)}
                    </td>
                  </tr>
                ))}
              </tbody>
              <tfoot className="bg-slate-950 font-bold border-t-2 border-slate-800">
                <tr>
                  <td colSpan={3} className="px-4 py-3 text-right text-slate-300">
                    Total Periode Ini:
                  </td>
                  <td className="px-4 py-3 text-right font-mono text-emerald-400">
                    {formatRupiah(ledgerData?.total_debit || 0)}
                  </td>
                  <td className="px-4 py-3 text-right font-mono text-cyan-400">
                    {formatRupiah(ledgerData?.total_credit || 0)}
                  </td>
                  <td className="px-4 py-3 text-right font-mono text-white text-sm">
                    {formatRupiah(ledgerData?.closing_balance || 0)}
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

export default GeneralLedgerPage;
