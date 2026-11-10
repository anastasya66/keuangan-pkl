import React, { useEffect, useState } from 'react';
import { FileText, FileSpreadsheet, Scale, RefreshCw } from 'lucide-react';
import reportService from '../../services/reportService';
import { formatRupiah } from '../../utils/currency';
import PageHeader from '../../components/ui/PageHeader';
import StatusBadge from '../../components/ui/StatusBadge';

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

  const isBalanced = data?.is_balanced;

  return (
    <div className="space-y-6 animate-fade-in">
      {/* Header */}
      <PageHeader
        title="Laporan Neraca (Balance Sheet)"
        subtitle="Menampilkan posisi aset, kewajiban, dan ekuitas toko dengan prinsip keseimbangan akuntansi double-entry."
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
              Per Tanggal (As of Date)
            </label>
            <input
              type="date"
              value={asOfDate}
              onChange={(e) => setAsOfDate(e.target.value)}
              className="bg-[#F8FAFA] border border-[#E7ECEE] rounded-xl px-3 py-2 text-xs text-[#20282D] font-medium focus:outline-none focus:border-[#55BFD8]"
            />
          </div>
          <button
            type="submit"
            className="px-5 py-2 rounded-full text-xs font-bold bg-[#20282D] hover:bg-black text-white transition cursor-pointer shadow-xs"
          >
            Tampilkan Neraca
          </button>
        </form>
      </div>

      {/* Main Report Document Card */}
      <div className="bg-white border border-[#E7ECEE] rounded-2xl p-6 sm:p-8 shadow-[0_2px_10px_rgba(0,0,0,0.02)]">
        {loading ? (
          <div className="text-center py-16 text-[#879197]">
            <div className="inline-block w-8 h-8 border-2 border-[#55BFD8] border-t-transparent rounded-full animate-spin mb-3"></div>
            <p className="text-xs font-medium">Menghitung posisi neraca dan equilibrium...</p>
          </div>
        ) : error ? (
          <div className="text-center py-12 text-[#E47777] font-semibold text-xs">{error}</div>
        ) : (
          <div className="space-y-8 max-w-5xl mx-auto">
            {/* Header Document Title & Equilibrium Status */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-[#F1F4F5]">
              <div>
                <h2 className="text-base font-black text-[#20282D] uppercase tracking-wider">
                  FinToko — Laporan Neraca
                </h2>
                <p className="text-xs text-[#879197] mt-1 font-mono">
                  Posisi Keuangan per tanggal: {data?.as_of_date}
                </p>
              </div>

              <div className="flex items-center gap-2">
                <StatusBadge
                  status={isBalanced ? 'completed' : 'draft'}
                  label={isBalanced ? '✓ Neraca Seimbang (Equilibrium)' : '⚠️ Neraca Belum Seimbang'}
                />
              </div>
            </div>

            {/* Two Column Grid for Assets (Aktiva) and Liabilities + Equity (Pasiva) */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
              {/* SISI KIRI: ASET (AKTIVA) */}
              <div className="space-y-6">
                <div>
                  <h3 className="text-xs font-bold uppercase tracking-wider text-[#37AFCB] pb-2 border-b border-[#F1F4F5]">
                    ASET / AKTIVA
                  </h3>

                  {/* Aset Lancar */}
                  <div className="mt-3">
                    <h4 className="text-xs font-semibold text-[#20282D] mb-2">Aset Lancar</h4>
                    <div className="space-y-1">
                      {data?.assets?.current?.accounts?.map((acc) => (
                        <div
                          key={acc.account_id}
                          className="flex items-center justify-between text-xs py-1.5 px-2.5 rounded-xl hover:bg-[#F8FAFA] transition-colors"
                        >
                          <div className="flex items-center gap-2">
                            <span className="font-mono text-[#879197] text-[11px] font-semibold">{acc.account_code}</span>
                            <span className="text-[#20282D] font-medium">{acc.account_name}</span>
                          </div>
                          <span className="font-mono font-bold text-[#20282D]">{formatRupiah(acc.balance)}</span>
                        </div>
                      ))}
                    </div>
                    <div className="flex items-center justify-between pt-2.5 mt-1 border-t border-[#F1F4F5] text-xs font-semibold">
                      <span className="text-[#879197]">Total Aset Lancar</span>
                      <span className="font-mono font-bold text-[#37AFCB]">
                        {formatRupiah(data?.assets?.current?.total || 0)}
                      </span>
                    </div>
                  </div>

                  {/* Aset Tetap */}
                  <div className="mt-5">
                    <h4 className="text-xs font-semibold text-[#20282D] mb-2">Aset Tetap</h4>
                    <div className="space-y-1">
                      {data?.assets?.fixed?.accounts?.map((acc) => (
                        <div
                          key={acc.account_id}
                          className="flex items-center justify-between text-xs py-1.5 px-2.5 rounded-xl hover:bg-[#F8FAFA] transition-colors"
                        >
                          <div className="flex items-center gap-2">
                            <span className="font-mono text-[#879197] text-[11px] font-semibold">{acc.account_code}</span>
                            <span className="text-[#20282D] font-medium">{acc.account_name}</span>
                          </div>
                          <span className="font-mono font-bold text-[#20282D]">{formatRupiah(acc.balance)}</span>
                        </div>
                      ))}
                    </div>
                    <div className="flex items-center justify-between pt-2.5 mt-1 border-t border-[#F1F4F5] text-xs font-semibold">
                      <span className="text-[#879197]">Total Aset Tetap</span>
                      <span className="font-mono font-bold text-[#37AFCB]">
                        {formatRupiah(data?.assets?.fixed?.total || 0)}
                      </span>
                    </div>
                  </div>
                </div>

                {/* Total Aset */}
                <div className="p-4 rounded-2xl bg-[#E5F6FA] border border-[#55BFD8]/30 flex items-center justify-between font-bold text-xs">
                  <span className="text-[#20282D]">TOTAL ASET (AKTIVA)</span>
                  <span className="font-mono text-base font-black text-[#20282D]">
                    {formatRupiah(data?.assets?.total || 0)}
                  </span>
                </div>
              </div>

              {/* SISI KANAN: KEWAJIBAN & EKUITAS (PASIVA) */}
              <div className="space-y-6">
                <div>
                  <h3 className="text-xs font-bold uppercase tracking-wider text-[#E9B45D] pb-2 border-b border-[#F1F4F5]">
                    KEWAJIBAN &amp; EKUITAS / PASIVA
                  </h3>

                  {/* Kewajiban Lancar */}
                  <div className="mt-3">
                    <h4 className="text-xs font-semibold text-[#20282D] mb-2">Kewajiban / Hutang</h4>
                    <div className="space-y-1">
                      {data?.liabilities?.current?.accounts?.map((acc) => (
                        <div
                          key={acc.account_id}
                          className="flex items-center justify-between text-xs py-1.5 px-2.5 rounded-xl hover:bg-[#F8FAFA] transition-colors"
                        >
                          <div className="flex items-center gap-2">
                            <span className="font-mono text-[#879197] text-[11px] font-semibold">{acc.account_code}</span>
                            <span className="text-[#20282D] font-medium">{acc.account_name}</span>
                          </div>
                          <span className="font-mono font-bold text-[#20282D]">{formatRupiah(acc.balance)}</span>
                        </div>
                      ))}
                    </div>
                    <div className="flex items-center justify-between pt-2.5 mt-1 border-t border-[#F1F4F5] text-xs font-semibold">
                      <span className="text-[#879197]">Total Kewajiban</span>
                      <span className="font-mono font-bold text-[#E9B45D]">
                        {formatRupiah(data?.liabilities?.total || 0)}
                      </span>
                    </div>
                  </div>

                  {/* Ekuitas / Modal */}
                  <div className="mt-5">
                    <h4 className="text-xs font-semibold text-[#20282D] mb-2">Modal &amp; Ekuitas</h4>
                    <div className="space-y-1">
                      {data?.equity?.accounts?.map((acc) => (
                        <div
                          key={acc.account_id}
                          className="flex items-center justify-between text-xs py-1.5 px-2.5 rounded-xl hover:bg-[#F8FAFA] transition-colors"
                        >
                          <div className="flex items-center gap-2">
                            <span className="font-mono text-[#879197] text-[11px] font-semibold">{acc.account_code}</span>
                            <span className="text-[#20282D] font-medium">{acc.account_name}</span>
                          </div>
                          <span className="font-mono font-bold text-[#20282D]">{formatRupiah(acc.balance)}</span>
                        </div>
                      ))}
                      {/* Laba Bersih Periode Berjalan */}
                      <div className="flex items-center justify-between text-xs py-1.5 px-2.5 rounded-xl bg-[#F8FAFA]">
                        <span className="text-[#20282D] font-medium italic">Laba Bersih Berjalan</span>
                        <span className="font-mono font-bold text-[#48B58B]">
                          {formatRupiah(data?.equity?.current_earnings || 0)}
                        </span>
                      </div>
                    </div>
                    <div className="flex items-center justify-between pt-2.5 mt-1 border-t border-[#F1F4F5] text-xs font-semibold">
                      <span className="text-[#879197]">Total Ekuitas</span>
                      <span className="font-mono font-bold text-[#37AFCB]">
                        {formatRupiah(data?.equity?.total || 0)}
                      </span>
                    </div>
                  </div>
                </div>

                {/* Total Pasiva */}
                <div className="p-4 rounded-2xl bg-[#FEF7EB] border border-[#E9B45D]/30 flex items-center justify-between font-bold text-xs">
                  <span className="text-[#20282D]">TOTAL PASIVA (HUTANG + EKUITAS)</span>
                  <span className="font-mono text-base font-black text-[#20282D]">
                    {formatRupiah(data?.liabilities_and_equity_total || 0)}
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
