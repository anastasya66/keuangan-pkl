import React, { useEffect, useState } from 'react';
import reportService from '../../services/reportService';
import { formatRupiah } from '../../utils/currency';

export default function BalanceSheetPage() {
  const today = new Date().toISOString().split('T')[0];
  const [asOfDate, setAsOfDate] = useState(today);
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [exportingPdf, setExportingPdf] = useState(false);
  const [exportingExcel, setExportingExcel] = useState(false);
  const [error, setError] = useState(null);

  const fetchReport = async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await reportService.getBalanceSheet({ as_of_date: asOfDate });
      setData(res.data);
    } catch (err) {
      console.error('Gagal mengambil Laporan Neraca:', err);
      setError('Gagal memuat laporan neraca.');
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
      await reportService.downloadBalanceSheetPdf({ as_of_date: asOfDate });
    } catch (err) {
      alert('Gagal mengunduh PDF Neraca.');
    } finally {
      setExportingPdf(false);
    }
  };

  const handleExportExcel = async () => {
    setExportingExcel(true);
    try {
      await reportService.downloadBalanceSheetExcel({ as_of_date: asOfDate });
    } catch (err) {
      alert('Gagal mengunduh Excel Neraca.');
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
            <div className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-cyan-500/20 text-cyan-400 border border-cyan-500/30 mb-2">
              <span className="w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
              Posisi Keuangan Neraca
            </div>
            <h1 className="text-2xl sm:text-3xl font-extrabold text-white">
              Laporan Neraca (Balance Sheet)
            </h1>
            <p className="mt-1 text-sm text-slate-400">
              Menampilkan posisi aset, kewajiban, dan ekuitas toko dengan prinsip keseimbangan akuntansi double-entry.
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
              Per Tanggal (As of Date)
            </label>
            <input
              type="date"
              value={asOfDate}
              onChange={(e) => setAsOfDate(e.target.value)}
              className="bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-cyan-500"
            />
          </div>
          <button
            type="submit"
            className="px-4 py-2 rounded-xl text-xs font-semibold bg-cyan-600 hover:bg-cyan-500 text-white transition-all cursor-pointer"
          >
            Tampilkan Neraca
          </button>
        </form>
      </div>

      {/* Main Report Document Card */}
      <div className="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-6 sm:p-8 backdrop-blur shadow-xl">
        {loading ? (
          <div className="text-center py-16 text-slate-400">
            <span className="text-3xl animate-spin inline-block mb-3">⏳</span>
            <p className="text-sm">Menghitung posisi neraca dan equilibrium...</p>
          </div>
        ) : error ? (
          <div className="text-center py-12 text-rose-400 text-sm">{error}</div>
        ) : (
          <div className="space-y-8 max-w-5xl mx-auto">
            {/* Header Document Title & Equilibrium Status */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-700">
              <div>
                <h2 className="text-xl font-black text-white uppercase tracking-wider">
                  FinToko — Laporan Neraca
                </h2>
                <p className="text-xs text-slate-400 mt-1">
                  Posisi Keuangan per tanggal: {data?.as_of_date}
                </p>
              </div>

              <div>
                <span
                  className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border ${
                    data?.is_balanced
                      ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30'
                      : 'bg-rose-500/20 text-rose-400 border-rose-500/30'
                  }`}
                >
                  <span
                    className={`w-2 h-2 rounded-full ${
                      data?.is_balanced ? 'bg-emerald-400' : 'bg-rose-400'
                    }`}
                  ></span>
                  {data?.is_balanced ? 'Neraca Seimbang (Equilibrium)' : 'Belum Seimbang'}
                </span>
              </div>
            </div>

            {/* Two Column Grid for Assets (Aktiva) and Liabilities + Equity (Pasiva) */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
              {/* SISI KIRI: ASET (AKTIVA) */}
              <div className="space-y-6">
                <div>
                  <h3 className="text-sm font-bold uppercase tracking-wider text-indigo-400 pb-2 border-b border-slate-700">
                    ASET / AKTIVA
                  </h3>

                  {/* Aset Lancar */}
                  <div className="mt-3">
                    <h4 className="text-xs font-semibold text-slate-300 mb-2">Aset Lancar</h4>
                    <div className="space-y-1.5">
                      {data?.assets?.current?.accounts?.map((acc) => (
                        <div
                          key={acc.account_id}
                          className="flex items-center justify-between text-xs py-1 px-2 rounded hover:bg-slate-900/40"
                        >
                          <div className="flex items-center gap-2">
                            <span className="font-mono text-slate-500 text-[11px]">{acc.account_code}</span>
                            <span className="text-slate-200">{acc.account_name}</span>
                          </div>
                          <span className="font-mono text-white">{formatRupiah(acc.balance)}</span>
                        </div>
                      ))}
                    </div>
                    <div className="flex items-center justify-between pt-2 mt-1 border-t border-slate-800 text-xs font-semibold">
                      <span className="text-slate-400">Total Aset Lancar</span>
                      <span className="font-mono text-indigo-300">
                        {formatRupiah(data?.assets?.current?.total || 0)}
                      </span>
                    </div>
                  </div>

                  {/* Aset Tetap */}
                  <div className="mt-4">
                    <h4 className="text-xs font-semibold text-slate-300 mb-2">Aset Tetap</h4>
                    <div className="space-y-1.5">
                      {data?.assets?.fixed?.accounts?.map((acc) => (
                        <div
                          key={acc.account_id}
                          className="flex items-center justify-between text-xs py-1 px-2 rounded hover:bg-slate-900/40"
                        >
                          <div className="flex items-center gap-2">
                            <span className="font-mono text-slate-500 text-[11px]">{acc.account_code}</span>
                            <span className="text-slate-200">{acc.account_name}</span>
                          </div>
                          <span className="font-mono text-white">{formatRupiah(acc.balance)}</span>
                        </div>
                      ))}
                    </div>
                    <div className="flex items-center justify-between pt-2 mt-1 border-t border-slate-800 text-xs font-semibold">
                      <span className="text-slate-400">Total Aset Tetap</span>
                      <span className="font-mono text-indigo-300">
                        {formatRupiah(data?.assets?.fixed?.total || 0)}
                      </span>
                    </div>
                  </div>
                </div>

                {/* Total Aset Box */}
                <div className="p-4 rounded-xl bg-indigo-950/30 border border-indigo-500/30 flex items-center justify-between">
                  <span className="text-xs font-bold text-slate-300 uppercase">TOTAL ASET</span>
                  <span className="text-lg font-black font-mono text-indigo-400">
                    {formatRupiah(data?.assets?.total || 0)}
                  </span>
                </div>
              </div>

              {/* SISI KANAN: KEWAJIBAN & EKUITAS (PASIVA) */}
              <div className="space-y-6">
                {/* Kewajiban */}
                <div>
                  <h3 className="text-sm font-bold uppercase tracking-wider text-amber-400 pb-2 border-b border-slate-700">
                    KEWAJIBAN (HUTANG)
                  </h3>

                  <div className="mt-3 space-y-1.5">
                    {data?.liabilities?.current?.accounts?.length === 0 ? (
                      <p className="text-xs text-slate-500 italic py-1">Tidak ada kewajiban lancar</p>
                    ) : (
                      data?.liabilities?.current?.accounts?.map((acc) => (
                        <div
                          key={acc.account_id}
                          className="flex items-center justify-between text-xs py-1 px-2 rounded hover:bg-slate-900/40"
                        >
                          <div className="flex items-center gap-2">
                            <span className="font-mono text-slate-500 text-[11px]">{acc.account_code}</span>
                            <span className="text-slate-200">{acc.account_name}</span>
                          </div>
                          <span className="font-mono text-white">{formatRupiah(acc.balance)}</span>
                        </div>
                      ))
                    )}
                  </div>
                  <div className="flex items-center justify-between pt-2 mt-1 border-t border-slate-800 text-xs font-semibold">
                    <span className="text-slate-400">Total Kewajiban</span>
                    <span className="font-mono text-amber-300">
                      {formatRupiah(data?.liabilities?.total || 0)}
                    </span>
                  </div>
                </div>

                {/* Ekuitas */}
                <div>
                  <h3 className="text-sm font-bold uppercase tracking-wider text-purple-400 pb-2 border-b border-slate-700">
                    EKUITAS (MODAL)
                  </h3>

                  <div className="mt-3 space-y-1.5">
                    {data?.equity?.accounts?.map((acc) => (
                      <div
                        key={acc.account_id}
                        className="flex items-center justify-between text-xs py-1 px-2 rounded hover:bg-slate-900/40"
                      >
                        <div className="flex items-center gap-2">
                          <span className="font-mono text-slate-500 text-[11px]">{acc.account_code}</span>
                          <span className="text-slate-200">{acc.account_name}</span>
                        </div>
                        <span className="font-mono text-white">{formatRupiah(acc.balance)}</span>
                      </div>
                    ))}

                    {/* Laba Bersih Kumulatif / Berjalan */}
                    <div className="flex items-center justify-between text-xs py-1.5 px-2 rounded bg-slate-900/60 font-medium">
                      <span className="text-purple-300">Laba Bersih Kumulatif Periode Berjalan</span>
                      <span className="font-mono text-emerald-400">
                        {formatRupiah(data?.equity?.current_period_earnings || 0)}
                      </span>
                    </div>
                  </div>

                  <div className="flex items-center justify-between pt-2 mt-1 border-t border-slate-800 text-xs font-semibold">
                    <span className="text-slate-400">Total Ekuitas</span>
                    <span className="font-mono text-purple-300">
                      {formatRupiah(data?.equity?.total || 0)}
                    </span>
                  </div>
                </div>

                {/* Total Kewajiban & Ekuitas Box */}
                <div className="p-4 rounded-xl bg-purple-950/30 border border-purple-500/30 flex items-center justify-between">
                  <span className="text-xs font-bold text-slate-300 uppercase">
                    TOTAL KEWAJIBAN & EKUITAS
                  </span>
                  <span className="text-lg font-black font-mono text-purple-400">
                    {formatRupiah(data?.total_liabilities_and_equity || 0)}
                  </span>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
