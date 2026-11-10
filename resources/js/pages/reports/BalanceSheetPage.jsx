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
      alert(err.message || 'Gagal mengunduh PDF Neraca.');
    } finally {
      setExportingPdf(false);
    }
  };

  const handleExportExcel = async () => {
    setExportingExcel(true);
    try {
      await reportService.downloadBalanceSheetExcel({ as_of_date: asOfDate });
    } catch (err) {
      alert(err.message || 'Gagal mengunduh Excel Neraca.');
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
              Laporan Neraca (Balance Sheet)
            </h1>
            <p className="mt-1 text-sm text-[#5A7C85]">
              Menampilkan posisi aset, kewajiban, dan ekuitas toko dengan prinsip keseimbangan akuntansi double-entry.
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
              Per Tanggal (As of Date)
            </label>
            <input
              type="date"
              value={asOfDate}
              onChange={(e) => setAsOfDate(e.target.value)}
              className="bg-[#F3FBFC] border border-[#D7EBEF] rounded-xl px-3 py-1.5 text-xs text-[#16323B] focus:bg-white focus:outline-none focus:border-[#38BFE0] focus:ring-2 focus:ring-[#38BFE0]/20 transition-all"
            />
          </div>
          <button
            type="submit"
            className="px-4 py-2 rounded-xl text-xs font-semibold bg-[#27C5E5] hover:bg-[#38BFE0] text-white transition-all cursor-pointer shadow-md shadow-[#27C5E5]/20"
          >
            Tampilkan Neraca
          </button>
        </form>
      </div>

      {/* Main Report Document Card */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-6 sm:p-8 shadow-xs">
        {loading ? (
          <div className="text-center py-16 text-[#5A7C85]">
            <div className="inline-block w-8 h-8 border-2 border-[#27C5E5] border-t-transparent rounded-full animate-spin mb-3"></div>
            <p className="text-sm">Menghitung posisi neraca dan equilibrium...</p>
          </div>
        ) : error ? (
          <div className="text-center py-12 text-rose-600 font-semibold text-sm">{error}</div>
        ) : (
          <div className="space-y-8 max-w-5xl mx-auto">
            {/* Header Document Title & Equilibrium Status */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-[#D7EBEF]">
              <div>
                <h2 className="text-xl font-black text-[#16323B] uppercase tracking-wider">
                  FinToko — Laporan Neraca
                </h2>
                <p className="text-xs text-[#5A7C85] mt-1 font-mono">
                  Posisi Keuangan per tanggal: {data?.as_of_date}
                </p>
              </div>
            </div>

            {/* Two Column Grid for Assets (Aktiva) and Liabilities + Equity (Pasiva) */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
              {/* SISI KIRI: ASET (AKTIVA) */}
              <div className="space-y-6">
                <div>
                  <h3 className="text-xs font-bold uppercase tracking-wider text-[#27C5E5] pb-2 border-b border-[#D7EBEF]">
                    ASET / AKTIVA
                  </h3>

                  {/* Aset Lancar */}
                  <div className="mt-3">
                    <h4 className="text-xs font-semibold text-[#16323B] mb-2">Aset Lancar</h4>
                    <div className="space-y-1">
                      {data?.assets?.current?.accounts?.map((acc) => (
                        <div
                          key={acc.account_id}
                          className="flex items-center justify-between text-xs py-1.5 px-2.5 rounded-xl hover:bg-[#F3FBFC]/60 transition-colors"
                        >
                          <div className="flex items-center gap-2">
                            <span className="font-mono text-[#5A7C85] text-[11px] font-semibold">{acc.account_code}</span>
                            <span className="text-[#16323B] font-medium">{acc.account_name}</span>
                          </div>
                          <span className="font-mono font-bold text-[#16323B]">{formatRupiah(acc.balance)}</span>
                        </div>
                      ))}
                    </div>
                    <div className="flex items-center justify-between pt-2.5 mt-1 border-t border-[#D7EBEF] text-xs font-semibold">
                      <span className="text-[#5A7C85]">Total Aset Lancar</span>
                      <span className="font-mono font-bold text-[#27C5E5]">
                        {formatRupiah(data?.assets?.current?.total || 0)}
                      </span>
                    </div>
                  </div>

                  {/* Aset Tetap */}
                  <div className="mt-5">
                    <h4 className="text-xs font-semibold text-[#16323B] mb-2">Aset Tetap</h4>
                    <div className="space-y-1">
                      {data?.assets?.fixed?.accounts?.map((acc) => (
                        <div
                          key={acc.account_id}
                          className="flex items-center justify-between text-xs py-1.5 px-2.5 rounded-xl hover:bg-[#F3FBFC]/60 transition-colors"
                        >
                          <div className="flex items-center gap-2">
                            <span className="font-mono text-[#5A7C85] text-[11px] font-semibold">{acc.account_code}</span>
                            <span className="text-[#16323B] font-medium">{acc.account_name}</span>
                          </div>
                          <span className="font-mono font-bold text-[#16323B]">{formatRupiah(acc.balance)}</span>
                        </div>
                      ))}
                    </div>
                    <div className="flex items-center justify-between pt-2.5 mt-1 border-t border-[#D7EBEF] text-xs font-semibold">
                      <span className="text-[#5A7C85]">Total Aset Tetap</span>
                      <span className="font-mono font-bold text-[#27C5E5]">
                        {formatRupiah(data?.assets?.fixed?.total || 0)}
                      </span>
                    </div>
                  </div>
                </div>

                {/* Total Aset Box */}
                <div className="p-4 rounded-xl bg-[#EAF8FA] border border-[#A9DDE7] flex items-center justify-between">
                  <span className="text-xs font-bold text-[#16323B] uppercase">TOTAL ASET</span>
                  <span className="text-lg font-extrabold font-mono text-[#27C5E5]">
                    {formatRupiah(data?.assets?.total || 0)}
                  </span>
                </div>
              </div>

              {/* SISI KANAN: KEWAJIBAN & EKUITAS (PASIVA) */}
              <div className="space-y-6">
                {/* Kewajiban */}
                <div>
                  <h3 className="text-xs font-bold uppercase tracking-wider text-amber-700 pb-2 border-b border-[#D7EBEF]">
                    KEWAJIBAN (HUTANG)
                  </h3>

                  <div className="mt-3 space-y-1">
                    {data?.liabilities?.current?.accounts?.length === 0 ? (
                      <p className="text-xs text-[#5A7C85] italic py-1">Tidak ada kewajiban lancar</p>
                    ) : (
                      data?.liabilities?.current?.accounts?.map((acc) => (
                        <div
                          key={acc.account_id}
                          className="flex items-center justify-between text-xs py-1.5 px-2.5 rounded-xl hover:bg-[#F3FBFC]/60 transition-colors"
                        >
                          <div className="flex items-center gap-2">
                            <span className="font-mono text-[#5A7C85] text-[11px] font-semibold">{acc.account_code}</span>
                            <span className="text-[#16323B] font-medium">{acc.account_name}</span>
                          </div>
                          <span className="font-mono font-bold text-[#16323B]">{formatRupiah(acc.balance)}</span>
                        </div>
                      ))
                    )}
                  </div>
                  <div className="flex items-center justify-between pt-2.5 mt-1 border-t border-[#D7EBEF] text-xs font-semibold">
                    <span className="text-[#5A7C85]">Total Kewajiban</span>
                    <span className="font-mono font-bold text-amber-700">
                      {formatRupiah(data?.liabilities?.total || 0)}
                    </span>
                  </div>
                </div>

                {/* Ekuitas */}
                <div>
                  <h3 className="text-xs font-bold uppercase tracking-wider text-purple-700 pb-2 border-b border-[#D7EBEF]">
                    EKUITAS (MODAL)
                  </h3>

                  <div className="mt-3 space-y-1">
                    {data?.equity?.accounts?.map((acc) => (
                      <div
                        key={acc.account_id}
                        className="flex items-center justify-between text-xs py-1.5 px-2.5 rounded-xl hover:bg-[#F3FBFC]/60 transition-colors"
                      >
                        <div className="flex items-center gap-2">
                          <span className="font-mono text-[#5A7C85] text-[11px] font-semibold">{acc.account_code}</span>
                          <span className="text-[#16323B] font-medium">{acc.account_name}</span>
                        </div>
                        <span className="font-mono font-bold text-[#16323B]">{formatRupiah(acc.balance)}</span>
                      </div>
                    ))}

                    {/* Laba Bersih Kumulatif / Berjalan */}
                    <div className="flex items-center justify-between text-xs py-2 px-2.5 rounded-xl bg-purple-50 border border-purple-100 font-medium">
                      <span className="text-purple-900 font-semibold">Laba Bersih Kumulatif Periode Berjalan</span>
                      <span className="font-mono font-bold text-[#36B878]">
                        {formatRupiah(data?.equity?.current_period_earnings || 0)}
                      </span>
                    </div>
                  </div>

                  <div className="flex items-center justify-between pt-2.5 mt-1 border-t border-[#D7EBEF] text-xs font-semibold">
                    <span className="text-[#5A7C85]">Total Ekuitas</span>
                    <span className="font-mono font-bold text-purple-700">
                      {formatRupiah(data?.equity?.total || 0)}
                    </span>
                  </div>
                </div>

                {/* Total Kewajiban & Ekuitas Box */}
                <div className="p-4 rounded-xl bg-purple-50 border border-purple-200 flex items-center justify-between">
                  <span className="text-xs font-bold text-purple-900 uppercase">
                    TOTAL KEWAJIBAN & EKUITAS
                  </span>
                  <span className="text-lg font-extrabold font-mono text-purple-700">
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
