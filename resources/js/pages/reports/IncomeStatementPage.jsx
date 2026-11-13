import React, { useEffect, useState } from 'react';
import { FileText, FileSpreadsheet, TrendingUp, TrendingDown, RefreshCw } from 'lucide-react';
import reportService from '../../services/reportService';
import { formatRupiah } from '../../utils/currency';
import PageHeader from '../../components/ui/PageHeader';

export default function IncomeStatementPage() {
  const today = new Date().toISOString().split('T')[0];
  const firstDayOfMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1)
    .toISOString()
    .split('T')[0];

  const [startDate, setStartDate] = useState(firstDayOfMonth);
  const [endDate, setEndDate] = useState(today);
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [exportingPdf, setExportingPdf] = useState(false);
  const [exportingExcel, setExportingExcel] = useState(false);
  const [error, setError] = useState(null);

  const fetchReport = async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await reportService.getIncomeStatement({
        start_date: startDate,
        end_date: endDate,
      });
      setData(res.data);
    } catch (err) {
      console.error('Gagal mengambil Laporan Laba Rugi:', err);
      setError('Gagal memuat laporan laba rugi.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchReport();
  }, []);

  const handleFilterSubmit = (e) => {
    e.preventDefault();
    fetchReport();
  };

  const handleExportPdf = async () => {
    setExportingPdf(true);
    try {
      await reportService.downloadIncomeStatementPdf({
        start_date: startDate,
        end_date: endDate,
      });
    } catch (err) {
      alert(err.message || 'Gagal mengunduh PDF Laba Rugi.');
    } finally {
      setExportingPdf(false);
    }
  };

  const handleExportExcel = async () => {
    setExportingExcel(true);
    try {
      await reportService.downloadIncomeStatementExcel({
        start_date: startDate,
        end_date: endDate,
      });
    } catch (err) {
      alert(err.message || 'Gagal mengunduh Excel Laba Rugi.');
    } finally {
      setExportingExcel(false);
    }
  };

  const isProfitable = (data?.net_profit ?? 0) >= 0;
  const revenueAccounts = data?.revenues?.accounts || data?.revenues?.items || [];
  const expenseAccounts = data?.expenses?.accounts || data?.expenses?.items || [];

  return (
    <div className="space-y-6 animate-fade-in">
      {/* Page Header */}
      <PageHeader
        title="Laporan Laba Rugi"
        subtitle="Penyajian kinerja laba/rugi toko berdasarkan pendapatan dan beban dari jurnal akuntansi terposting."
      >
        <div className="flex items-center gap-2">
          <button
            onClick={handleExportPdf}
            disabled={exportingPdf || loading}
            className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-[#E5F6FA] text-[#37AFCB] hover:bg-[#D0F1F8] font-bold text-xs transition shadow-xs disabled:opacity-50 cursor-pointer"
          >
            <FileText className="w-3.5 h-3.5" />
            <span>{exportingPdf ? 'Mengunduh...' : 'Ekspor PDF'}</span>
          </button>

          <button
            onClick={handleExportExcel}
            disabled={exportingExcel || loading}
            className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-[#EBF7F2] text-[#48B58B] hover:bg-[#D5EFE3] font-bold text-xs transition shadow-xs disabled:opacity-50 cursor-pointer"
          >
            <FileSpreadsheet className="w-3.5 h-3.5" />
            <span>{exportingExcel ? 'Mengunduh...' : 'Ekspor Excel'}</span>
          </button>
        </div>
      </PageHeader>

      {/* Date Filter Bar */}
      <div className="bg-white border border-[#E7ECEE] rounded-2xl p-5 shadow-[0_2px_10px_rgba(0,0,0,0.02)]">
        <form
          onSubmit={handleFilterSubmit}
          className="flex flex-wrap items-end gap-3"
        >
          <div>
            <label className="block text-[11px] font-semibold text-[#879197] mb-1">
              Dari Tanggal
            </label>
            <input
              type="date"
              value={startDate}
              onChange={(e) => setStartDate(e.target.value)}
              className="bg-[#F8FAFA] border border-[#E7ECEE] rounded-xl px-3 py-2 text-xs text-[#20282D] font-medium focus:outline-none focus:border-[#55BFD8]"
            />
          </div>
          <div>
            <label className="block text-[11px] font-semibold text-[#879197] mb-1">
              Sampai Tanggal
            </label>
            <input
              type="date"
              value={endDate}
              onChange={(e) => setEndDate(e.target.value)}
              className="bg-[#F8FAFA] border border-[#E7ECEE] rounded-xl px-3 py-2 text-xs text-[#20282D] font-medium focus:outline-none focus:border-[#55BFD8]"
            />
          </div>
          <button
            type="submit"
            className="px-5 py-2 rounded-full text-xs font-bold bg-[#20282D] hover:bg-black text-white transition cursor-pointer shadow-xs"
          >
            Tampilkan
          </button>
        </form>
      </div>

      {/* Main Report Document Card */}
      <div className="bg-white border border-[#E7ECEE] rounded-2xl p-6 sm:p-8 shadow-[0_2px_10px_rgba(0,0,0,0.02)]">
        {loading ? (
          <div className="text-center py-16 text-[#879197]">
            <div className="inline-block w-8 h-8 border-2 border-[#55BFD8] border-t-transparent rounded-full animate-spin mb-3"></div>
            <p className="text-xs font-medium">Memuat data Laporan Laba Rugi...</p>
          </div>
        ) : error ? (
          <div className="text-center py-12 text-[#E47777] font-semibold text-xs">{error}</div>
        ) : (
          <div className="space-y-8 max-w-4xl mx-auto">
            {/* Header Document Title */}
            <div className="text-center pb-6 border-b border-[#F1F4F5]">
              <h2 className="text-base font-black text-[#20282D] uppercase tracking-wider">
                FinToko — Laporan Laba Rugi
              </h2>
              <p className="text-xs text-[#879197] mt-1">
                Periode: {data?.period?.start_date} s/d {data?.period?.end_date}
              </p>
            </div>

            {/* Bagian Pendapatan (Revenue) */}
            <div>
              <div className="flex items-center justify-between pb-2 border-b border-[#F1F4F5] mb-3">
                <h3 className="text-xs font-bold uppercase tracking-wider text-[#48B58B]">
                  I. PENDAPATAN OPERASIONAL (REVENUE)
                </h3>
              </div>

              {revenueAccounts.length === 0 ? (
                <p className="text-xs text-[#879197] italic py-2">
                  Tidak ada pendapatan pada periode ini.
                </p>
              ) : (
                <div className="space-y-1">
                  {revenueAccounts.map((acc) => (
                    <div
                      key={acc.account_id}
                      className="flex items-center justify-between text-xs py-2 px-3 rounded-xl hover:bg-[#F8FAFA] transition-colors"
                    >
                      <div className="flex items-center gap-3">
                        <span className="font-mono text-[#879197] font-semibold">{acc.account_code}</span>
                        <span className="text-[#20282D] font-medium">{acc.account_name}</span>
                        {acc.transaction_count > 0 && (
                          <span className="text-[10px] bg-[#F1F4F5] text-[#879197] px-2 py-0.5 rounded-full font-semibold">
                            {acc.transaction_count} transaksi
                          </span>
                        )}
                      </div>
                      <span className="font-mono font-bold text-[#20282D]">
                        {formatRupiah(acc.amount ?? acc.balance ?? 0)}
                      </span>
                    </div>
                  ))}
                </div>
              )}

              <div className="flex items-center justify-between pt-3 mt-2 border-t border-[#F1F4F5] font-bold text-xs">
                <span className="text-[#20282D]">Total Pendapatan</span>
                <span className="font-mono text-[#48B58B] text-base font-extrabold">
                  {formatRupiah(data?.revenues?.total || 0)}
                </span>
              </div>
            </div>

            {/* Bagian Beban Operasional (Expenses) */}
            <div>
              <div className="flex items-center justify-between pb-2 border-b border-[#F1F4F5] mb-3">
                <h3 className="text-xs font-bold uppercase tracking-wider text-[#E47777]">
                  II. BEBAN OPERASIONAL (EXPENSES)
                </h3>
              </div>

              {expenseAccounts.length === 0 ? (
                <p className="text-xs text-[#879197] italic py-2">
                  Tidak ada beban pada periode ini.
                </p>
              ) : (
                <div className="space-y-1">
                  {expenseAccounts.map((acc) => (
                    <div
                      key={acc.account_id}
                      className="flex items-center justify-between text-xs py-2 px-3 rounded-xl hover:bg-[#F8FAFA] transition-colors"
                    >
                      <div className="flex items-center gap-3">
                        <span className="font-mono text-[#879197] font-semibold">{acc.account_code}</span>
                        <span className="text-[#20282D] font-medium">{acc.account_name}</span>
                        {acc.transaction_count > 0 && (
                          <span className="text-[10px] bg-[#F1F4F5] text-[#879197] px-2 py-0.5 rounded-full font-semibold">
                            {acc.transaction_count} transaksi
                          </span>
                        )}
                      </div>
                      <span className="font-mono font-bold text-[#20282D]">
                        {formatRupiah(acc.amount ?? acc.balance ?? 0)}
                      </span>
                    </div>
                  ))}
                </div>
              )}

              <div className="flex items-center justify-between pt-3 mt-2 border-t border-[#F1F4F5] font-bold text-xs">
                <span className="text-[#20282D]">Total Beban</span>
                <span className="font-mono text-[#E47777] text-base font-extrabold">
                  {formatRupiah(data?.expenses?.total || 0)}
                </span>
              </div>
            </div>

            {/* Hasil Akhir: Laba / Rugi Bersih */}
            <div
              className={`p-5 rounded-2xl border ${
                isProfitable
                  ? 'bg-[#EBF7F2] border-[#48B58B]/30'
                  : 'bg-[#FDF3F3] border-[#E47777]/30'
              }`}
            >
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                  <div className="text-[11px] uppercase tracking-wider font-bold text-[#879197]">
                    HASIL OPERASIONAL BERSIH
                  </div>
                  <div
                    className={`text-base font-extrabold mt-0.5 ${
                      isProfitable ? 'text-[#48B58B]' : 'text-[#E47777]'
                    }`}
                  >
                    {data?.profit_label || (isProfitable ? 'LABA BERSIH PERIODE BERJALAN' : 'RUGI BERSIH PERIODE BERJALAN')}
                  </div>
                </div>

                <div
                  className={`text-2xl sm:text-3xl font-black font-mono ${
                    isProfitable ? 'text-[#48B58B]' : 'text-[#E47777]'
                  }`}
                >
                  {formatRupiah(data?.net_profit || 0)}
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
