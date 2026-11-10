import React, { useEffect, useState } from 'react';
import reportService from '../../services/reportService';
import { formatRupiah } from '../../utils/currency';

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

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-6 shadow-xs">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h1 className="text-2xl sm:text-3xl font-extrabold text-[#16323B]">
              Laporan Laba Rugi (Income Statement)
            </h1>
            <p className="mt-1 text-sm text-[#5A7C85]">
              Penyajian kinerja laba/rugi toko berdasarkan pendapatan dan beban dari jurnal akuntansi yang telah diposting.
            </p>
          </div>

          {/* Export Buttons */}
          <div className="flex items-center gap-2">
            <button
              onClick={handleExportPdf}
              disabled={exportingPdf || loading}
              className="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-semibold text-xs transition-all shadow-xs disabled:opacity-50 cursor-pointer"
            >
              <span>📄</span>
              {exportingPdf ? 'Mengunduh...' : 'Ekspor PDF'}
            </button>

            <button
              onClick={handleExportExcel}
              disabled={exportingExcel || loading}
              className="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 font-semibold text-xs transition-all shadow-xs disabled:opacity-50 cursor-pointer"
            >
              <span>📊</span>
              {exportingExcel ? 'Mengunduh...' : 'Ekspor Excel'}
            </button>
          </div>
        </div>

        {/* Date Filter Bar */}
        <form
          onSubmit={handleFilterSubmit}
          className="mt-6 pt-4 border-t border-[#D7EBEF] flex flex-wrap items-end gap-3"
        >
          <div>
            <label className="block text-xs font-semibold text-[#16323B] mb-1">
              Dari Tanggal
            </label>
            <input
              type="date"
              value={startDate}
              onChange={(e) => setStartDate(e.target.value)}
              className="bg-[#F3FBFC] border border-[#D7EBEF] rounded-xl px-3 py-1.5 text-xs text-[#16323B] focus:bg-white focus:outline-none focus:border-[#38BFE0] focus:ring-2 focus:ring-[#38BFE0]/20 transition-all"
            />
          </div>
          <div>
            <label className="block text-xs font-semibold text-[#16323B] mb-1">
              Sampai Tanggal
            </label>
            <input
              type="date"
              value={endDate}
              onChange={(e) => setEndDate(e.target.value)}
              className="bg-[#F3FBFC] border border-[#D7EBEF] rounded-xl px-3 py-1.5 text-xs text-[#16323B] focus:bg-white focus:outline-none focus:border-[#38BFE0] focus:ring-2 focus:ring-[#38BFE0]/20 transition-all"
            />
          </div>
          <button
            type="submit"
            className="px-4 py-2 rounded-xl text-xs font-semibold bg-[#27C5E5] hover:bg-[#38BFE0] text-white transition-all cursor-pointer shadow-md shadow-[#27C5E5]/20"
          >
            Tampilkan
          </button>
        </form>
      </div>

      {/* Main Report Document Card */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-6 sm:p-8 shadow-xs">
        {loading ? (
          <div className="text-center py-16 text-[#5A7C85]">
            <div className="inline-block w-8 h-8 border-2 border-[#27C5E5] border-t-transparent rounded-full animate-spin mb-3"></div>
            <p className="text-sm">Memuat data Laporan Laba Rugi...</p>
          </div>
        ) : error ? (
          <div className="text-center py-12 text-rose-600 font-semibold text-sm">{error}</div>
        ) : (
          <div className="space-y-8 max-w-4xl mx-auto">
            {/* Header Document Title */}
            <div className="text-center pb-6 border-b border-[#D7EBEF]">
              <h2 className="text-xl font-black text-[#16323B] uppercase tracking-wider">
                FinToko — Laporan Laba Rugi
              </h2>
              <p className="text-xs text-[#5A7C85] mt-1 font-mono">
                Periode: {data?.period?.start_date} s/d {data?.period?.end_date}
              </p>
            </div>

            {/* Bagian Pendapatan (Revenue) */}
            <div>
              <div className="flex items-center justify-between pb-2 border-b border-[#D7EBEF] mb-3">
                <h3 className="text-xs font-bold uppercase tracking-wider text-[#36B878]">
                  I. PENDAPATAN OPERASIONAL (REVENUE)
                </h3>
              </div>

              {data?.revenues?.accounts?.length === 0 ? (
                <p className="text-xs text-[#5A7C85] italic py-2">
                  Tidak ada pendapatan pada periode ini.
                </p>
              ) : (
                <div className="space-y-1">
                  {data?.revenues?.accounts?.map((acc) => (
                    <div
                      key={acc.account_id}
                      className="flex items-center justify-between text-xs py-2 px-3 rounded-xl hover:bg-[#F3FBFC]/60 transition-colors"
                    >
                      <div className="flex items-center gap-3">
                        <span className="font-mono text-[#5A7C85] font-semibold">{acc.account_code}</span>
                        <span className="text-[#16323B] font-medium">{acc.account_name}</span>
                      </div>
                      <span className="font-mono font-bold text-[#16323B]">
                        {formatRupiah(acc.amount)}
                      </span>
                    </div>
                  ))}
                </div>
              )}

              <div className="flex items-center justify-between pt-3 mt-2 border-t border-[#D7EBEF] font-bold text-xs">
                <span className="text-[#16323B]">Total Pendapatan</span>
                <span className="font-mono text-[#36B878] text-base font-extrabold">
                  {formatRupiah(data?.revenues?.total || 0)}
                </span>
              </div>
            </div>

            {/* Bagian Beban Operasional (Expenses) */}
            <div>
              <div className="flex items-center justify-between pb-2 border-b border-[#D7EBEF] mb-3">
                <h3 className="text-xs font-bold uppercase tracking-wider text-[#E66D79]">
                  II. BEBAN OPERASIONAL (EXPENSES)
                </h3>
              </div>

              {data?.expenses?.accounts?.length === 0 ? (
                <p className="text-xs text-[#5A7C85] italic py-2">
                  Tidak ada beban pada periode ini.
                </p>
              ) : (
                <div className="space-y-1">
                  {data?.expenses?.accounts?.map((acc) => (
                    <div
                      key={acc.account_id}
                      className="flex items-center justify-between text-xs py-2 px-3 rounded-xl hover:bg-[#F3FBFC]/60 transition-colors"
                    >
                      <div className="flex items-center gap-3">
                        <span className="font-mono text-[#5A7C85] font-semibold">{acc.account_code}</span>
                        <span className="text-[#16323B] font-medium">{acc.account_name}</span>
                      </div>
                      <span className="font-mono font-bold text-[#16323B]">
                        {formatRupiah(acc.amount)}
                      </span>
                    </div>
                  ))}
                </div>
              )}

              <div className="flex items-center justify-between pt-3 mt-2 border-t border-[#D7EBEF] font-bold text-xs">
                <span className="text-[#16323B]">Total Beban</span>
                <span className="font-mono text-[#E66D79] text-base font-extrabold">
                  {formatRupiah(data?.expenses?.total || 0)}
                </span>
              </div>
            </div>

            {/* Hasil Akhir: Laba / Rugi Bersih */}
            <div
              className={`p-5 rounded-2xl border ${
                (data?.net_profit || 0) >= 0
                  ? 'bg-emerald-50 border-emerald-200 text-emerald-950'
                  : 'bg-rose-50 border-rose-200 text-rose-950'
              }`}
            >
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                  <div className="text-xs uppercase tracking-wider font-bold text-[#5A7C85]">
                    HASIL OPERASIONAL BERSIH
                  </div>
                  <div
                    className={`text-lg font-extrabold mt-0.5 ${
                      (data?.net_profit || 0) >= 0 ? 'text-[#36B878]' : 'text-[#E66D79]'
                    }`}
                  >
                    {data?.is_profitable ? 'LABA BERSIH PERIODE BERJALAN' : 'RUGI BERSIH PERIODE BERJALAN'}
                  </div>
                </div>

                <div
                  className={`text-2xl sm:text-3xl font-black font-mono ${
                    (data?.net_profit || 0) >= 0 ? 'text-[#36B878]' : 'text-[#E66D79]'
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
