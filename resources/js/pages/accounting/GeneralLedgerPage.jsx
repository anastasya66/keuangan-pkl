import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';
import accountingService from '../../services/accountingService';
import { formatRupiah } from '../../utils/currency';
import PageHeader from '../../components/ui/PageHeader';

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
      <PageHeader
        title="Buku Besar (General Ledger)"
        subtitle="Riwayat mutasi transaksi posted dan akumulasi saldo berjalan per akun akuntansi."
      />

      {/* Control Panel: Pilih Akun & Rentang Tanggal */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-5 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
        {/* Dropdown Akun */}
        <div className="w-full md:w-1/2">
          <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
            Pilih Akun Akuntansi
          </label>
          <select
            value={selectedAccountId}
            onChange={(e) => setSelectedAccountId(e.target.value)}
            className="w-full px-3.5 py-2.5 bg-[#F3FBFC] border border-[#D7EBEF] rounded-xl text-sm text-[#16323B] font-medium focus:bg-white focus:outline-none focus:border-[#38BFE0] focus:ring-2 focus:ring-[#38BFE0]/20 cursor-pointer transition-all"
          >
            {accounts.map((acc) => (
              <option key={acc.id} value={acc.id}>
                {acc.account_code} - {acc.account_name} ({acc.account_type_label || acc.account_type})
              </option>
            ))}
          </select>
        </div>

        {/* Date Filter */}
        <div className="flex flex-wrap sm:flex-nowrap items-end gap-2.5 w-full md:w-auto">
          <div>
            <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
              Dari Tanggal
            </label>
            <input
              type="date"
              value={startDate}
              onChange={(e) => setStartDate(e.target.value)}
              className="px-3 py-2 bg-[#F3FBFC] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:bg-white focus:outline-none focus:border-[#38BFE0] focus:ring-2 focus:ring-[#38BFE0]/20 transition-all"
            />
          </div>

          <span className="text-[#5A7C85] text-xs pb-2.5 hidden sm:inline">s/d</span>

          <div>
            <label className="block text-xs font-semibold text-[#16323B] mb-1.5">
              Sampai Tanggal
            </label>
            <input
              type="date"
              value={endDate}
              onChange={(e) => setEndDate(e.target.value)}
              className="px-3 py-2 bg-[#F3FBFC] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B] focus:bg-white focus:outline-none focus:border-[#38BFE0] focus:ring-2 focus:ring-[#38BFE0]/20 transition-all"
            />
          </div>

          <button
            type="button"
            onClick={fetchLedger}
            className="px-4 py-2.5 rounded-xl text-xs font-semibold bg-[#27C5E5] hover:bg-[#38BFE0] text-white transition-all cursor-pointer shadow-md shadow-[#27C5E5]/20"
          >
            Terapkan
          </button>
        </div>
      </div>

      {/* Summary KPI Cards */}
      {ledgerData && (
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div className="p-4 bg-white border border-[#D7EBEF] rounded-2xl shadow-xs space-y-1">
            <span className="text-xs text-[#5A7C85]">Saldo Awal Periode</span>
            <div className="text-lg font-bold font-mono text-[#16323B]">
              {formatRupiah(ledgerData.opening_balance)}
            </div>
            <span className="text-[10px] text-[#5A7C85] block">Sebelum {startDate}</span>
          </div>

          <div className="p-4 bg-white border border-[#D7EBEF] rounded-2xl shadow-xs space-y-1">
            <span className="text-xs text-[#5A7C85]">Total Mutasi Debit</span>
            <div className="text-lg font-bold font-mono text-[#36B878]">
              {formatRupiah(ledgerData.total_debit)}
            </div>
            <span className="text-[10px] text-[#5A7C85] block">Selama periode</span>
          </div>

          <div className="p-4 bg-white border border-[#D7EBEF] rounded-2xl shadow-xs space-y-1">
            <span className="text-xs text-[#5A7C85]">Total Mutasi Kredit</span>
            <div className="text-lg font-bold font-mono text-[#27C5E5]">
              {formatRupiah(ledgerData.total_credit)}
            </div>
            <span className="text-[10px] text-[#5A7C85] block">Selama periode</span>
          </div>

          <div className="p-4 bg-[#F3FBFC] border border-[#A9DDE7] rounded-2xl shadow-xs space-y-1">
            <span className="text-xs text-[#27C5E5] font-bold">Saldo Akhir Periode</span>
            <div className="text-lg font-extrabold font-mono text-[#16323B]">
              {formatRupiah(ledgerData.closing_balance)}
            </div>
            <span className="text-[10px] text-[#5A7C85] block uppercase">
              Normal Balance: {ledgerData.account.normal_balance}
            </span>
          </div>
        </div>
      )}

      {/* Ledger Table */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl overflow-hidden shadow-xs">
        <div className="px-6 py-4 border-b border-[#D7EBEF] bg-[#F3FBFC] flex items-center justify-between">
          <div className="flex items-center gap-3">
            <h2 className="text-sm font-bold text-[#16323B]">
              Transaksi Buku Besar: {ledgerData?.account.account_code} - {ledgerData?.account.account_name}
            </h2>
            <span className="text-xs font-mono px-2 py-0.5 rounded-md bg-[#EAF8FA] text-[#27C5E5] border border-[#A9DDE7] uppercase">
              {ledgerData?.account.account_type}
            </span>
          </div>
          <span className="text-xs text-[#5A7C85] font-mono">
            {ledgerData?.entries?.length || 0} entri
          </span>
        </div>

        {isLoading ? (
          <div className="py-16 text-center text-[#5A7C85]">
            <div className="inline-block w-6 h-6 border-2 border-[#27C5E5] border-t-transparent rounded-full animate-spin mb-2"></div>
            <p className="text-xs">Memuat data mutasi buku besar...</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-[#F3FBFC] text-[#5A7C85] font-semibold border-b border-[#D7EBEF]">
                <tr>
                  <th className="px-4 py-3 w-28 text-center">Tanggal</th>
                  <th className="px-4 py-3 w-44 font-mono">No. Jurnal</th>
                  <th className="px-4 py-3">Keterangan</th>
                  <th className="px-4 py-3 w-36 text-right font-mono">Debit (Rp)</th>
                  <th className="px-4 py-3 w-36 text-right font-mono">Kredit (Rp)</th>
                  <th className="px-4 py-3 w-40 text-right font-mono">Saldo Berjalan (Rp)</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#D7EBEF]/60">
                {/* Saldo Awal Baris */}
                <tr className="bg-[#F3FBFC]/40 text-[#5A7C85] font-semibold italic">
                  <td className="px-4 py-2.5 text-center font-mono">{startDate}</td>
                  <td className="px-4 py-2.5 font-mono text-[#5A7C85]">-</td>
                  <td className="px-4 py-2.5">Saldo Awal (Opening Balance)</td>
                  <td className="px-4 py-2.5 text-right font-mono">-</td>
                  <td className="px-4 py-2.5 text-right font-mono">-</td>
                  <td className="px-4 py-2.5 text-right font-mono text-[#16323B] font-bold">
                    {formatRupiah(ledgerData?.opening_balance || 0)}
                  </td>
                </tr>

                {ledgerData?.entries?.map((row) => (
                  <tr key={row.id} className="hover:bg-[#F3FBFC]/50 transition-colors">
                    <td className="px-4 py-3 text-center text-[#5A7C85] font-mono">
                      {row.journal_date}
                    </td>
                    <td className="px-4 py-3">
                      <Link
                        to={`${basePath}/journals/${row.journal_id}`}
                        className="font-mono font-bold text-[#27C5E5] hover:underline"
                      >
                        {row.journal_number}
                      </Link>
                      {row.reference_number && (
                        <p className="text-[10px] text-[#5A7C85] font-mono">Ref: {row.reference_number}</p>
                      )}
                    </td>
                    <td className="px-4 py-3 text-[#16323B]">
                      <span>{row.description}</span>
                    </td>
                    <td className="px-4 py-3 text-right font-mono font-bold text-[#36B878]">
                      {row.debit > 0 ? formatRupiah(row.debit) : '-'}
                    </td>
                    <td className="px-4 py-3 text-right font-mono font-bold text-[#27C5E5]">
                      {row.credit > 0 ? formatRupiah(row.credit) : '-'}
                    </td>
                    <td className="px-4 py-3 text-right font-mono font-extrabold text-[#16323B]">
                      {formatRupiah(row.running_balance)}
                    </td>
                  </tr>
                ))}
              </tbody>
              <tfoot className="bg-[#F3FBFC] font-bold border-t-2 border-[#D7EBEF]">
                <tr>
                  <td colSpan={3} className="px-4 py-3 text-right text-[#16323B]">
                    Total Periode Ini:
                  </td>
                  <td className="px-4 py-3 text-right font-mono text-[#36B878] text-sm">
                    {formatRupiah(ledgerData?.total_debit || 0)}
                  </td>
                  <td className="px-4 py-3 text-right font-mono text-[#27C5E5] text-sm">
                    {formatRupiah(ledgerData?.total_credit || 0)}
                  </td>
                  <td className="px-4 py-3 text-right font-mono text-[#16323B] text-sm">
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
