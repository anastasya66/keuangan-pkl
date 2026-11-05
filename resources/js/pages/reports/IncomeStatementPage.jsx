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
      alert('Gagal mengunduh PDF Laba Rugi.');
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
      alert('Gagal mengunduh Excel Laba Rugi.');
    } finally {
      setExportingExcel(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-6 backdrop-blur shadow-xl">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 mb-2">
              <span className="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
              Laporan Keuangan Resmi
            </div>
            <h1 className="text-2xl sm:text-3xl font-extrabold text-white">
              Laporan Laba Rugi (Income Statement)
            </h1>
            <p className="mt-1 text-sm text-slate-400">
              Penyajian kinerja laba/rugi toko berdasarkan pendapatan dan beban dari jurnal akuntansi yang telah diposting.
            </p>
          </div>

          {/* Export Buttons */}
          <div className="flex items-center gap-2">
            <button
              onClick={handleExportPdf}
              disabled={exportingPdf || loading}
              className="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-rose-600/90 hover:bg-rose-600 text-white font-semibold text-xs transition-all shadow-lg shadow-rose-900/30 disabled:opacity-50 cursor-pointer"
            >
              <span>📄</span>
              {exportingPdf ? 'Mengunduh...' : 'Ekspor PDF'}
            </button>

            <button
              onClick={handleExportExcel}
              disabled={exportingExcel || loading}
              className="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-600/90 hover:bg-emerald-600 text-white font-semibold text-xs transition-all shadow-lg shadow-emerald-900/30 disabled:opacity-50 cursor-pointer"
            >
              <span>📊</span>
              {exportingExcel ? 'Mengunduh...' : 'Ekspor Excel'}
            </button>
          </div>
        </div>

        {/* Date Filter Bar */}
        <form
          onSubmit={handleFilterSubmit}
          className="mt-6 pt-4 border-t border-slate-700/80 flex flex-wrap items-end gap-3"
        >
          <div>
            <label className="block text-xs font-medium text-slate-400 mb-1">
              Dari Tanggal
            </label>
            <input
              type="date"
              value={startDate}
              onChange={(e) => setStartDate(e.target.value)}
              className="bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-emerald-500"
            />
          </div>
          <div>
            <label className="block text-xs font-medium text-slate-400 mb-1">
              Sampai Tanggal
            </label>
            <input
              type="date"
              value={endDate}
              onChange={(e) => setEndDate(e.target.value)}
              className="bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-emerald-500"
            />
          </div>
          <button
            type="submit"
            className="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white transition-all cursor-pointer"
          >
            Tampilkan
          </button>
        </form>
      </div>

      {/* Main Report Document Card */}
      <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-6 sm:p-8 backdrop-blur shadow-xl">
        {loading ? (
          <div className="text-center py-16 text-slate-400">
            <span className="text-3xl animate-spin inline-block mb-3">⏳</span>
            <p className="text-sm">Memuat data Laporan Laba Rugi...</p>
          </div>
        ) : error ? (
          <div className="text-center py-12 text-rose-400 text-sm">{error}</div>
        ) : (
          <div className="space-y-8 max-w-4xl mx-auto">
            {/* Header Document Title */}
            <div className="text-center pb-6 border-b border-slate-700">
              <h2 className="text-xl font-black text-white uppercase tracking-wider">
                FinToko — Laporan Laba Rugi
              </h2>
              <p className="text-xs text-slate-400 mt-1">
                Periode: {data?.period?.start_date} s/d {data?.period?.end_date}
              </p>
            </div>

            {/* Bagian Pendapatan (Revenue) */}
            <div>
              <div className="flex items-center justify-between pb-2 border-b border-slate-700 mb-3">
                <h3 className="text-sm font-bold uppercase tracking-wider text-emerald-400">
                  I. PENDAPATAN OPERASIONAL (REVENUE)
                </h3>
              </div>

              {data?.revenues?.accounts?.length === 0 ? (
                <p className="text-xs text-slate-500 italic py-2">
                  Tidak ada pendapatan pada periode ini.
                </p>
              ) : (
                <div className="space-y-2">
                  {data?.revenues?.accounts?.map((acc) => (
                    <div
                      key={acc.account_id}
                      className="flex items-center justify-between text-xs py-1.5 px-3 rounded-lg hover:bg-slate-900/40 transition-colors"
                    >
                      <div className="flex items-center gap-3">
                        <span className="font-mono text-slate-400">{acc.account_code}</span>
                        <span className="text-white">{acc.account_name}</span>
                      </div>
                      <span className="font-mono font-medium text-slate-200">
                        {formatRupiah(acc.amount)}
                      </span>
                    </div>
                  ))}
                </div>
              )}

              <div className="flex items-center justify-between pt-3 mt-2 border-t border-slate-700/80 font-bold text-xs">
                <span className="text-slate-300">Total Pendapatan</span>
                <span className="font-mono text-emerald-400 text-sm">
                  {formatRupiah(data?.revenues?.total || 0)}
                </span>
              </div>
            </div>

            {/* Bagian Beban Operasional (Expenses) */}
            <div>
              <div className="flex items-center justify-between pb-2 border-b border-slate-700 mb-3">
                <h3 className="text-sm font-bold uppercase tracking-wider text-rose-400">
                  II. BEBAN OPERASIONAL (EXPENSES)
                </h3>
              </div>

              {data?.expenses?.accounts?.length === 0 ? (
                <p className="text-xs text-slate-500 italic py-2">
                  Tidak ada beban pada periode ini.
                </p>
              ) : (
                <div className="space-y-2">
                  {data?.expenses?.accounts?.map((acc) => (
                    <div
                      key={acc.account_id}
                      className="flex items-center justify-between text-xs py-1.5 px-3 rounded-lg hover:bg-slate-900/40 transition-colors"
                    >
                      <div className="flex items-center gap-3">
                        <span className="font-mono text-slate-400">{acc.account_code}</span>
                        <span className="text-white">{acc.account_name}</span>
                      </div>
                      <span className="font-mono font-medium text-slate-200">
                        {formatRupiah(acc.amount)}
                      </span>
                    </div>
                  ))}
                </div>
              )}

              <div className="flex items-center justify-between pt-3 mt-2 border-t border-slate-700/80 font-bold text-xs">
                <span className="text-slate-300">Total Beban</span>
                <span className="font-mono text-rose-400 text-sm">
                  {formatRupiah(data?.expenses?.total || 0)}
                </span>
              </div>
            </div>

            {/* Hasil Akhir: Laba / Rugi Bersih */}
            <div
              className={`p-5 rounded-2xl border ${
                (data?.net_profit || 0) >= 0
                  ? 'bg-emerald-950/30 border-emerald-500/40'
                  : 'bg-rose-950/30 border-rose-500/40'
              }`}
            >
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                  <div className="text-xs uppercase tracking-wider font-bold text-slate-400">
                    HASIL OPERASIONAL BERSIH
                  </div>
                  <div
                    className={`text-lg font-black ${
                      (data?.net_profit || 0) >= 0 ? 'text-emerald-400' : 'text-rose-400'
                    }`}
                  >
                    {data?.is_profitable ? 'LABA BERSIH PERIODE BERJALAN' : 'RUGI BERSIH PERIODE BERJALAN'}
                  </div>
                </div>

                <div
                  className={`text-2xl sm:text-3xl font-black font-mono ${
                    (data?.net_profit || 0) >= 0 ? 'text-emerald-300' : 'text-rose-400'
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
