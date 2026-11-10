import React, { useState, useEffect, useCallback } from 'react';
import settingsService from '../../services/settingsService';
import { formatRupiah } from '../../utils/currency';
import {
  IconSettings,
  IconShield,
  IconDatabase,
  IconCreditCard,
  IconPos,
  IconBox,
  IconJournal,
  IconFileText,
  IconHistory,
} from '../../components/common/Icons';

export function SettingsPage() {
  const [activeTab, setActiveTab] = useState('store');
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [toast, setToast] = useState(null);

  // Settings state
  const [settings, setSettings] = useState({});

  // Roles & Permissions state
  const [roles, setRoles] = useState([]);
  const [permissionsByGroup, setPermissionsByGroup] = useState({});
  const [selectedRoleId, setSelectedRoleId] = useState(null);
  const [selectedPermissions, setSelectedPermissions] = useState([]);

  // Document Sequences state
  const [sequences, setSequences] = useState([]);
  const [editingSequence, setEditingSequence] = useState(null);

  // Accounting Periods state
  const [periods, setPeriods] = useState([]);

  // Payment Methods state
  const [paymentMethods, setPaymentMethods] = useState([]);
  const [editingPm, setEditingPm] = useState(null);

  // Backups state
  const [backups, setBackups] = useState([]);
  const [backupNotes, setBackupNotes] = useState('');
  const [restoreModal, setRestoreModal] = useState({ open: false, backup: null, password: '', isRestoring: false });

  // Activity Logs state
  const [activityLogs, setActivityLogs] = useState([]);
  const [logPagination, setLogPagination] = useState(null);
  const [logFilters, setLogFilters] = useState({ search: '', module: '', action: '' });
  const [logsLoading, setLogsLoading] = useState(false);

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 4000);
  };

  // Load Settings
  const loadAllSettings = useCallback(async () => {
    setIsLoading(true);
    try {
      const res = await settingsService.getSettings();
      if (res.success) {
        setSettings(res.data.flattened || {});
      }
    } catch (err) {
      showToast(err.response?.data?.message || 'Gagal memuat pengaturan', 'error');
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Load Roles & Permissions
  const loadRoles = useCallback(async () => {
    try {
      const res = await settingsService.getRoles();
      if (res.success) {
        setRoles(res.data.roles || []);
        setPermissionsByGroup(res.data.permissions_by_group || {});
        // Default ke role staff jika ada
        const staff = (res.data.roles || []).find((r) => r.name === 'staff');
        if (staff) {
          setSelectedRoleId(staff.id);
          setSelectedPermissions((staff.permissions || []).map((p) => p.name));
        }
      }
    } catch (err) {
      console.error(err);
    }
  }, []);

  // Load Sequences
  const loadSequences = useCallback(async () => {
    try {
      const res = await settingsService.getDocumentSequences();
      if (res.success) setSequences(res.data);
    } catch (err) {
      console.error(err);
    }
  }, []);

  // Load Periods
  const loadPeriods = useCallback(async () => {
    try {
      const res = await settingsService.getAccountingPeriods();
      if (res.success) setPeriods(res.data);
    } catch (err) {
      console.error(err);
    }
  }, []);

  // Load Payment Methods
  const loadPaymentMethods = useCallback(async () => {
    try {
      const res = await settingsService.getPaymentMethods();
      if (res.success) setPaymentMethods(res.data);
    } catch (err) {
      console.error(err);
    }
  }, []);

  // Load Backups
  const loadBackups = useCallback(async () => {
    try {
      const res = await settingsService.getBackups();
      if (res.success) setBackups(res.data);
    } catch (err) {
      console.error(err);
    }
  }, []);

  // Load Activity Logs
  const loadActivityLogs = useCallback(async (params = {}) => {
    setLogsLoading(true);
    try {
      const res = await settingsService.getActivityLogs(params);
      if (res.success) {
        setActivityLogs(res.data);
        setLogPagination(res.pagination);
      }
    } catch (err) {
      console.error(err);
    } finally {
      setLogsLoading(false);
    }
  }, []);

  useEffect(() => {
    loadAllSettings();
  }, [loadAllSettings]);

  useEffect(() => {
    if (activeTab === 'roles') loadRoles();
    if (activeTab === 'sequences') loadSequences();
    if (activeTab === 'accounting') loadPeriods();
    if (activeTab === 'payment') loadPaymentMethods();
    if (activeTab === 'backup') loadBackups();
    if (activeTab === 'logs') loadActivityLogs(logFilters);
  }, [activeTab, loadRoles, loadSequences, loadPeriods, loadPaymentMethods, loadBackups, loadActivityLogs, logFilters]);

  // Handle Setting Change
  const handleSettingChange = (key, value) => {
    setSettings((prev) => ({ ...prev, [key]: value }));
  };

  // Submit Settings Batch
  const handleSaveSettings = async (keys) => {
    setIsSaving(true);
    try {
      const payload = {};
      keys.forEach((k) => {
        if (settings[k] !== undefined) payload[k] = settings[k];
      });

      const res = await settingsService.updateSettings(payload);
      if (res.success) {
        setSettings(res.data);
        showToast('Pengaturan berhasil disimpan dan diperbarui.');
      }
    } catch (err) {
      showToast(err.response?.data?.message || 'Gagal menyimpan pengaturan', 'error');
    } finally {
      setIsSaving(false);
    }
  };

  // Handle Role Change
  const handleRoleSelect = (roleId) => {
    setSelectedRoleId(roleId);
    const role = roles.find((r) => r.id === roleId);
    if (role) {
      setSelectedPermissions((role.permissions || []).map((p) => p.name));
    }
  };

  // Toggle Permission
  const handleTogglePermission = (permName) => {
    setSelectedPermissions((prev) =>
      prev.includes(permName) ? prev.filter((p) => p !== permName) : [...prev, permName]
    );
  };

  // Save Permissions
  const handleSavePermissions = async () => {
    if (!selectedRoleId) return;
    setIsSaving(true);
    try {
      const res = await settingsService.updateRolePermissions(selectedRoleId, selectedPermissions);
      if (res.success) {
        showToast(`Hak akses role berhasil diperbarui.`);
        loadRoles();
      }
    } catch (err) {
      showToast(err.response?.data?.message || 'Gagal menyimpan hak akses', 'error');
    } finally {
      setIsSaving(false);
    }
  };

  // Toggle Period Lock
  const handleTogglePeriodLock = async (id) => {
    try {
      const res = await settingsService.togglePeriodLock(id);
      if (res.success) {
        showToast(res.message);
        loadPeriods();
      }
    } catch (err) {
      showToast(err.response?.data?.message || 'Gagal mengubah status periode', 'error');
    }
  };

  // Toggle Payment Method Status
  const handleTogglePmStatus = async (id) => {
    try {
      const res = await settingsService.togglePaymentMethodStatus(id);
      if (res.success) {
        showToast(res.message);
        loadPaymentMethods();
      }
    } catch (err) {
      showToast(err.response?.data?.message || 'Gagal mengubah status metode pembayaran', 'error');
    }
  };

  // Create Backup
  const handleCreateBackup = async () => {
    setIsSaving(true);
    try {
      const res = await settingsService.createBackup(backupNotes);
      if (res.success) {
        showToast(res.message);
        setBackupNotes('');
        loadBackups();
      }
    } catch (err) {
      showToast(err.response?.data?.message || 'Gagal membuat cadangan database', 'error');
    } finally {
      setIsSaving(false);
    }
  };

  // Restore Backup
  const handleConfirmRestore = async () => {
    if (!restoreModal.password) {
      showToast('Password admin wajib diisi untuk konfirmasi pemulihan', 'error');
      return;
    }

    setRestoreModal((prev) => ({ ...prev, isRestoring: true }));
    try {
      const res = await settingsService.restoreBackup(restoreModal.backup.id, restoreModal.password);
      if (res.success) {
        showToast(res.message);
        setRestoreModal({ open: false, backup: null, password: '', isRestoring: false });
        loadAllSettings();
        loadBackups();
      }
    } catch (err) {
      showToast(err.response?.data?.message || err.response?.data?.errors?.password?.[0] || 'Pemulihan gagal', 'error');
      setRestoreModal((prev) => ({ ...prev, isRestoring: false }));
    }
  };

  // Update Sequence
  const handleSaveSequence = async (e) => {
    e.preventDefault();
    if (!editingSequence) return;

    try {
      const res = await settingsService.updateDocumentSequence(editingSequence.id, editingSequence);
      if (res.success) {
        showToast(res.message);
        setEditingSequence(null);
        loadSequences();
      }
    } catch (err) {
      showToast(err.response?.data?.message || 'Gagal menyimpan penomoran dokumen', 'error');
    }
  };

  const tabs = [
    { id: 'store', label: 'Profil Toko & Struk', icon: IconSettings },
    { id: 'pos', label: 'Transaksi & POS', icon: IconPos },
    { id: 'payment', label: 'Metode Pembayaran', icon: IconCreditCard },
    { id: 'sequences', label: 'Penomoran Dokumen', icon: IconFileText },
    { id: 'inventory', label: 'Persediaan & Stok', icon: IconBox },
    { id: 'accounting', label: 'Keuangan & Akuntansi', icon: IconJournal },
    { id: 'roles', label: 'Hak Akses & Role', icon: IconShield },
    { id: 'backup', label: 'Cadangan & Database', icon: IconDatabase },
    { id: 'logs', label: 'Audit Trail (Log Aktivitas)', icon: IconHistory },
  ];

  return (
    <div className="space-y-6">
      {/* Toast Notification */}
      {toast && (
        <div
          className={`fixed top-4 right-4 z-50 px-4 py-3 rounded-xl shadow-lg border text-xs font-semibold flex items-center gap-2 ${
            toast.type === 'error'
              ? 'bg-rose-50 border-rose-200 text-rose-700'
              : 'bg-emerald-50 border-emerald-200 text-emerald-700'
          }`}
        >
          <span>{toast.type === 'error' ? '⚠️' : '✓'}</span>
          <span>{toast.message}</span>
        </div>
      )}

      {/* Header Bar */}
      <div>
        <h1 className="text-xl font-bold text-[#16323B] flex items-center gap-2.5">
          <span className="p-2 bg-[#167A93] text-white rounded-xl shadow-xs">
            <IconSettings className="w-5 h-5" />
          </span>
          Pusat Pengaturan Sistem (Settings)
        </h1>
        <p className="text-xs text-[#577782] mt-1">
          Pusat kendali konfigurasi toko, hak akses granular, kasir POS, akuntansi, dan pemeliharaan database
        </p>
      </div>

      {/* Settings Navigation Tabs */}
      <div className="bg-white border border-[#D7EBEF] rounded-2xl p-1.5 shadow-xs overflow-x-auto no-scrollbar">
        <div className="flex items-center gap-1 min-w-max">
          {tabs.map((tab) => {
            const Icon = tab.icon;
            const isActive = activeTab === tab.id;
            return (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer whitespace-nowrap ${
                  isActive
                    ? 'bg-[#167A93] text-white shadow-xs'
                    : 'text-[#577782] hover:text-[#16323B] hover:bg-[#F3FBFC]'
                }`}
              >
                <Icon className="w-4 h-4" />
                <span>{tab.label}</span>
              </button>
            );
          })}
        </div>
      </div>

      {/* Tab Contents */}
      {isLoading ? (
        <div className="bg-white border border-[#D7EBEF] rounded-2xl p-12 text-center text-[#577782] text-xs">
          Memuat data pengaturan sistem...
        </div>
      ) : (
        <div key={activeTab} className="animate-page-enter">
          {/* TAB 1: PROFIL TOKO & STRUK */}
          {activeTab === 'store' && (
            <div className="bg-white border border-[#D7EBEF] rounded-2xl p-6 shadow-xs space-y-6">
              <div className="border-b border-[#EAF4F6] pb-4">
                <h3 className="text-base font-bold text-[#16323B]">Identitas & Informasi Toko</h3>
                <p className="text-xs text-[#577782] mt-0.5">
                  Informasi ini dicetak pada struk kasir thermal, invoice pembelian toko, dan laporan resmi
                </p>
              </div>

              {/* Logo Preview Card */}
              <div className="flex items-center gap-4 p-4 bg-[#F8FDFE] border border-[#EAF4F6] rounded-2xl">
                <div className="w-16 h-16 rounded-xl bg-white border border-[#D7EBEF] flex items-center justify-center p-2 shadow-xs shrink-0">
                  <img
                    src="/images/fintoko-mark.png"
                    alt="Logo FinToko"
                    className="w-full h-full object-contain"
                  />
                </div>
                <div>
                  <div className="font-bold text-[#16323B] text-sm">Logo Resmi FinToko</div>
                  <div className="text-[11px] text-[#577782] mt-0.5">
                    Logo aktif yang digunakan pada navbar, sidebar, struk kasir, faktur, dan tab browser.
                  </div>
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div>
                  <label className="block font-semibold text-[#16323B] mb-1">Nama Toko / Minimarket</label>
                  <input
                    type="text"
                    value={settings.store_name || ''}
                    onChange={(e) => handleSettingChange('store_name', e.target.value)}
                    className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B]"
                  />
                </div>

                <div>
                  <label className="block font-semibold text-[#16323B] mb-1">Nama Pemilik / Penanggung Jawab</label>
                  <input
                    type="text"
                    value={settings.store_owner || ''}
                    onChange={(e) => handleSettingChange('store_owner', e.target.value)}
                    className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B]"
                  />
                </div>

                <div>
                  <label className="block font-semibold text-[#16323B] mb-1">Nomor Telepon / WhatsApp</label>
                  <input
                    type="text"
                    value={settings.store_phone || ''}
                    onChange={(e) => handleSettingChange('store_phone', e.target.value)}
                    className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B]"
                  />
                </div>

                <div>
                  <label className="block font-semibold text-[#16323B] mb-1">Email Toko</label>
                  <input
                    type="email"
                    value={settings.store_email || ''}
                    onChange={(e) => handleSettingChange('store_email', e.target.value)}
                    className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B]"
                  />
                </div>

                <div>
                  <label className="block font-semibold text-[#16323B] mb-1">NPWP Toko (Opsional)</label>
                  <input
                    type="text"
                    value={settings.store_npwp || ''}
                    onChange={(e) => handleSettingChange('store_npwp', e.target.value)}
                    className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B]"
                  />
                </div>

                <div>
                  <label className="block font-semibold text-[#16323B] mb-1">Deskripsi Singkat Toko</label>
                  <input
                    type="text"
                    value={settings.store_description || ''}
                    onChange={(e) => handleSettingChange('store_description', e.target.value)}
                    className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B]"
                  />
                </div>

                <div className="md:col-span-2">
                  <label className="block font-semibold text-[#16323B] mb-1">Alamat Lengkap Toko</label>
                  <textarea
                    rows="2"
                    value={settings.store_address || ''}
                    onChange={(e) => handleSettingChange('store_address', e.target.value)}
                    className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs text-[#16323B]"
                  ></textarea>
                </div>
              </div>

              <div className="border-t border-[#EAF4F6] pt-4">
                <h3 className="text-sm font-bold text-[#16323B] mb-3">Teks Header & Footer Struk Kasir</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                  <div>
                    <label className="block font-semibold text-[#16323B] mb-1">Pesan Header Struk (Atas)</label>
                    <textarea
                      rows="3"
                      value={settings.receipt_header || ''}
                      onChange={(e) => handleSettingChange('receipt_header', e.target.value)}
                      placeholder="Selamat datang di Toko..."
                      className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs font-mono"
                    ></textarea>
                  </div>
                  <div>
                    <label className="block font-semibold text-[#16323B] mb-1">Pesan Footer Struk (Bawah)</label>
                    <textarea
                      rows="3"
                      value={settings.receipt_footer || ''}
                      onChange={(e) => handleSettingChange('receipt_footer', e.target.value)}
                      placeholder="Terima kasih atas kunjungan Anda..."
                      className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs font-mono"
                    ></textarea>
                  </div>
                </div>
              </div>

              <div className="flex justify-end pt-3">
                <button
                  type="button"
                  disabled={isSaving}
                  onClick={() =>
                    handleSaveSettings([
                      'store_name',
                      'store_owner',
                      'store_phone',
                      'store_email',
                      'store_npwp',
                      'store_description',
                      'store_address',
                      'receipt_header',
                      'receipt_footer',
                    ])
                  }
                  className="px-5 py-2 bg-[#167A93] hover:bg-[#105668] text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer"
                >
                  {isSaving ? 'Menyimpan...' : 'Simpan Profil Toko'}
                </button>
              </div>
            </div>
          )}

          {/* TAB 2: TRANSAKSI & POS */}
          {activeTab === 'pos' && (
            <div className="bg-white border border-[#D7EBEF] rounded-2xl p-6 shadow-xs space-y-6">
              <div className="border-b border-[#EAF4F6] pb-4">
                <h3 className="text-base font-bold text-[#16323B]">Pengaturan Kasir POS & Transaksi</h3>
                <p className="text-xs text-[#577782] mt-0.5">
                  Aturan operasional mesin kasir minimarket, scanner barcode, dan pencetakan thermal
                </p>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-5 text-xs">
                <div>
                  <label className="block font-semibold text-[#16323B] mb-1">Lebar Printer Thermal Struk</label>
                  <select
                    value={settings.pos_receipt_printer_width || '58mm'}
                    onChange={(e) => handleSettingChange('pos_receipt_printer_width', e.target.value)}
                    className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs"
                  >
                    <option value="58mm">58 mm (Printer Thermal Standar Kecil)</option>
                    <option value="80mm">80 mm (Printer Thermal Lebar / POS Kasir Besar)</option>
                  </select>
                </div>

                <div>
                  <label className="block font-semibold text-[#16323B] mb-1">Batas Maksimal Diskon Kasir (%)</label>
                  <input
                    type="number"
                    min="0"
                    max="100"
                    value={settings.pos_max_discount_percent ?? 50}
                    onChange={(e) => handleSettingChange('pos_max_discount_percent', Number(e.target.value))}
                    className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs font-mono"
                  />
                </div>

                <div>
                  <label className="block font-semibold text-[#16323B] mb-1">Simbol Mata Uang</label>
                  <input
                    type="text"
                    value={settings.currency_symbol || 'Rp'}
                    onChange={(e) => handleSettingChange('currency_symbol', e.target.value)}
                    className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs font-mono"
                  />
                </div>

                <div>
                  <label className="block font-semibold text-[#16323B] mb-1">Format Tampilan Tanggal</label>
                  <select
                    value={settings.date_format || 'd/m/Y'}
                    onChange={(e) => handleSettingChange('date_format', e.target.value)}
                    className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs"
                  >
                    <option value="d/m/Y">d/m/Y (Contoh: 07/11/2026)</option>
                    <option value="Y-m-d">Y-m-d (Contoh: 2026-11-07)</option>
                    <option value="d M Y">d M Y (Contoh: 07 Nov 2026)</option>
                  </select>
                </div>

                <div className="flex items-center justify-between p-3.5 bg-[#F8FDFE] border border-[#EAF4F6] rounded-xl">
                  <div>
                    <div className="font-bold text-[#16323B]">Auto-Submit Scan Barcode</div>
                    <div className="text-[11px] text-[#577782]">
                      Otomatis masukkan produk ke keranjang kasir saat barcode di-scan
                    </div>
                  </div>
                  <input
                    type="checkbox"
                    checked={Boolean(settings.pos_barcode_auto_submit)}
                    onChange={(e) => handleSettingChange('pos_barcode_auto_submit', e.target.checked)}
                    className="w-4 h-4 accent-[#167A93] cursor-pointer"
                  />
                </div>

                <div className="flex items-center justify-between p-3.5 bg-[#F8FDFE] border border-[#EAF4F6] rounded-xl">
                  <div>
                    <div className="font-bold text-[#16323B]">Izinkan Pembatalan Transaksi</div>
                    <div className="text-[11px] text-[#577782]">
                      Izinkan pembatalan penjualan atau pembelian toko dengan reversal otomatis
                    </div>
                  </div>
                  <input
                    type="checkbox"
                    checked={Boolean(settings.allow_transaction_cancellation)}
                    onChange={(e) => handleSettingChange('allow_transaction_cancellation', e.target.checked)}
                    className="w-4 h-4 accent-[#167A93] cursor-pointer"
                  />
                </div>
              </div>

              <div className="flex justify-end pt-3">
                <button
                  type="button"
                  disabled={isSaving}
                  onClick={() =>
                    handleSaveSettings([
                      'pos_receipt_printer_width',
                      'pos_max_discount_percent',
                      'currency_symbol',
                      'date_format',
                      'pos_barcode_auto_submit',
                      'allow_transaction_cancellation',
                    ])
                  }
                  className="px-5 py-2 bg-[#167A93] hover:bg-[#105668] text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer"
                >
                  {isSaving ? 'Menyimpan...' : 'Simpan Pengaturan Kasir'}
                </button>
              </div>
            </div>
          )}

          {/* TAB 3: METODE PEMBAYARAN */}
          {activeTab === 'payment' && (
            <div className="bg-white border border-[#D7EBEF] rounded-2xl p-6 shadow-xs space-y-6">
              <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-[#EAF4F6] pb-4">
                <div>
                  <h3 className="text-base font-bold text-[#16323B]">Metode Pembayaran Kasir & Transaksi</h3>
                  <p className="text-xs text-[#577782] mt-0.5">
                    Daftar cara pembayaran yang dapat dipilih di kasir POS dan pembelian toko
                  </p>
                </div>
                <button
                  type="button"
                  onClick={() =>
                    setEditingPm({
                      id: null,
                      code: '',
                      name: '',
                      type: 'digital',
                      is_active: true,
                      requires_reference: true,
                      instructions: '',
                      sort_order: paymentMethods.length + 1,
                    })
                  }
                  className="px-4 py-2 bg-[#167A93] hover:bg-[#105668] text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer self-start"
                >
                  + Tambah Metode
                </button>
              </div>

              <div className="overflow-x-auto">
                <table className="w-full text-xs text-left">
                  <thead>
                    <tr className="border-b border-[#D7EBEF] text-[#577782] font-semibold bg-[#F8FDFE]">
                      <th className="py-2.5 px-3">Kode</th>
                      <th className="py-2.5 px-3">Nama Metode</th>
                      <th className="py-2.5 px-3">Tipe</th>
                      <th className="py-2.5 px-3">Perlu No Ref</th>
                      <th className="py-2.5 px-3 text-center">Status</th>
                      <th className="py-2.5 px-3 text-right">Aksi</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-[#EAF4F6]">
                    {paymentMethods.map((pm) => (
                      <tr key={pm.id} className="hover:bg-[#F8FDFE]">
                        <td className="py-3 px-3 font-mono font-bold text-[#167A93]">{pm.code}</td>
                        <td className="py-3 px-3 font-semibold text-[#16323B]">{pm.name}</td>
                        <td className="py-3 px-3 uppercase text-[10px] text-[#577782] font-bold">{pm.type}</td>
                        <td className="py-3 px-3">{pm.requires_reference ? 'Ya (Wajib)' : 'Tidak'}</td>
                        <td className="py-3 px-3 text-center">
                          <span
                            className={`px-2.5 py-0.5 rounded-full text-[10px] font-bold ${
                              pm.is_active ? 'bg-emerald-600 text-white' : 'bg-slate-500 text-white'
                            }`}
                          >
                            {pm.is_active ? 'Aktif' : 'Nonaktif'}
                          </span>
                        </td>
                        <td className="py-3 px-3 text-right">
                          <button
                            type="button"
                            onClick={() => handleTogglePmStatus(pm.id)}
                            className="text-xs text-[#167A93] hover:underline cursor-pointer font-semibold"
                          >
                            {pm.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* TAB 4: PENOMORAN DOKUMEN */}
          {activeTab === 'sequences' && (
            <div className="bg-white border border-[#D7EBEF] rounded-2xl p-6 shadow-xs space-y-6">
              <div className="border-b border-[#EAF4F6] pb-4">
                <h3 className="text-base font-bold text-[#16323B]">Penomoran Dokumen Otomatis Terpadu</h3>
                <p className="text-xs text-[#577782] mt-0.5">
                  Aturan format nomor transaksi kasir, invoice pembelian toko, jurnal, dan kas untuk mencegah nomor duplikat
                </p>
              </div>

              <div className="overflow-x-auto">
                <table className="w-full text-xs text-left">
                  <thead>
                    <tr className="border-b border-[#D7EBEF] text-[#577782] font-semibold bg-[#F8FDFE]">
                      <th className="py-2.5 px-3">Tipe Dokumen</th>
                      <th className="py-2.5 px-3">Prefix</th>
                      <th className="py-2.5 px-3">Format Pola</th>
                      <th className="py-2.5 px-3">Padding Digit</th>
                      <th className="py-2.5 px-3">Reset Urutan</th>
                      <th className="py-2.5 px-3">Urutan Terakhir</th>
                      <th className="py-2.5 px-3 text-right">Aksi</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-[#EAF4F6]">
                    {sequences.map((seq) => (
                      <tr key={seq.id} className="hover:bg-[#F8FDFE]">
                        <td className="py-3 px-3 font-semibold text-[#16323B]">{seq.name}</td>
                        <td className="py-3 px-3 font-mono font-bold text-[#167A93]">{seq.prefix}</td>
                        <td className="py-3 px-3 font-mono text-[11px] text-[#577782]">{seq.format}</td>
                        <td className="py-3 px-3 font-mono">{seq.padding} digit</td>
                        <td className="py-3 px-3 capitalize">{seq.reset_frequency}</td>
                        <td className="py-3 px-3 font-mono font-bold text-[#16323B]">#{seq.current_sequence}</td>
                        <td className="py-3 px-3 text-right">
                          <button
                            type="button"
                            onClick={() => setEditingSequence(seq)}
                            className="px-2.5 py-1 bg-white border border-[#D7EBEF] rounded-lg text-xs font-semibold text-[#16323B] hover:bg-[#F3FBFC] cursor-pointer"
                          >
                            Ubah Format
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              {/* Edit Sequence Modal */}
              {editingSequence && (
                <div className="fixed inset-0 bg-[#10262E]/40 backdrop-blur-xs z-50 flex items-center justify-center p-4 animate-backdrop">
                  <div className="bg-white rounded-2xl border border-[#D7EBEF] max-w-md w-full p-6 shadow-xl space-y-4 animate-modal-pop">
                    <h4 className="font-bold text-[#16323B] text-sm">
                      Ubah Format: {editingSequence.name}
                    </h4>

                    <form onSubmit={handleSaveSequence} className="space-y-3 text-xs">
                      <div>
                        <label className="block font-semibold mb-1">Prefix Dokumen</label>
                        <input
                          type="text"
                          value={editingSequence.prefix}
                          onChange={(e) =>
                            setEditingSequence({ ...editingSequence, prefix: e.target.value.toUpperCase() })
                          }
                          className="w-full px-3 py-2 border border-[#D7EBEF] rounded-xl font-mono uppercase"
                          required
                        />
                      </div>

                      <div>
                        <label className="block font-semibold mb-1">Pola Format</label>
                        <input
                          type="text"
                          value={editingSequence.format}
                          onChange={(e) => setEditingSequence({ ...editingSequence, format: e.target.value })}
                          className="w-full px-3 py-2 border border-[#D7EBEF] rounded-xl font-mono"
                          required
                        />
                        <span className="text-[10px] text-[#577782] block mt-1">
                          Tag tersedia: {'{PREFIX}'}, {'{YYYY}'}, {'{MM}'}, {'{DD}'}, {'{SEQ}'}
                        </span>
                      </div>

                      <div>
                        <label className="block font-semibold mb-1">Padding Digit Angka Urut</label>
                        <input
                          type="number"
                          min="2"
                          max="8"
                          value={editingSequence.padding}
                          onChange={(e) =>
                            setEditingSequence({ ...editingSequence, padding: Number(e.target.value) })
                          }
                          className="w-full px-3 py-2 border border-[#D7EBEF] rounded-xl font-mono"
                          required
                        />
                      </div>

                      <div>
                        <label className="block font-semibold mb-1">Frekuensi Reset Urutan</label>
                        <select
                          value={editingSequence.reset_frequency}
                          onChange={(e) =>
                            setEditingSequence({ ...editingSequence, reset_frequency: e.target.value })
                          }
                          className="w-full px-3 py-2 border border-[#D7EBEF] rounded-xl"
                        >
                          <option value="daily">Harian (Reset setiap pergantian hari)</option>
                          <option value="monthly">Bulanan (Reset setiap tanggal 1 bulan baru)</option>
                          <option value="yearly">Tahunan (Reset setiap awal tahun)</option>
                          <option value="never">Tidak Pernah (Urutan terus bertambah)</option>
                        </select>
                      </div>

                      <div className="flex justify-end gap-2 pt-3 border-t border-[#EAF4F6]">
                        <button
                          type="button"
                          onClick={() => setEditingSequence(null)}
                          className="px-4 py-2 border border-[#D7EBEF] rounded-xl text-xs font-semibold cursor-pointer"
                        >
                          Batal
                        </button>
                        <button
                          type="submit"
                          className="px-4 py-2 bg-[#167A93] hover:bg-[#105668] text-white rounded-xl text-xs font-bold cursor-pointer"
                        >
                          Simpan Format
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
              )}
            </div>
          )}

          {/* TAB 5: PERSEDIAAN & STOK */}
          {activeTab === 'inventory' && (
            <div className="bg-white border border-[#D7EBEF] rounded-2xl p-6 shadow-xs space-y-6">
              <div className="border-b border-[#EAF4F6] pb-4">
                <h3 className="text-base font-bold text-[#16323B]">Pengaturan Persediaan & Kartu Stok</h3>
                <p className="text-xs text-[#577782] mt-0.5">
                  Kebijakan penilaian aset, pencegahan stok minus, dan batas notifikasi barang menipis
                </p>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-5 text-xs">
                <div>
                  <label className="block font-semibold text-[#16323B] mb-1">Metode Penilaian Persediaan</label>
                  <input
                    type="text"
                    disabled
                    value="Moving Weighted Average (MWA) - Rata-rata Tertimbang Bergerak"
                    className="w-full px-3 py-2 bg-slate-100 border border-[#D7EBEF] rounded-xl text-xs text-slate-600 font-semibold cursor-not-allowed"
                  />
                  <span className="text-[11px] text-[#577782] mt-1 block">
                    Metode standar akuntansi toko untuk menghitung HPP secara real-time saat pembelian toko masuk
                  </span>
                </div>

                <div>
                  <label className="block font-semibold text-[#16323B] mb-1">Batas Default Stok Minimum (Peringatan Menipis)</label>
                  <input
                    type="number"
                    min="1"
                    value={settings.inventory_default_min_stock ?? 5}
                    onChange={(e) => handleSettingChange('inventory_default_min_stock', Number(e.target.value))}
                    className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs font-mono"
                  />
                </div>

                <div className="flex items-center justify-between p-3.5 bg-[#F8FDFE] border border-[#EAF4F6] rounded-xl">
                  <div>
                    <div className="font-bold text-[#16323B]">Izinkan Stok Bernilai Minus / Negatif</div>
                    <div className="text-[11px] text-[#577782]">
                      Jika dinonaktifkan, kasir tidak dapat menjual produk jika stok fisik tidak mencukupi
                    </div>
                  </div>
                  <input
                    type="checkbox"
                    checked={Boolean(settings.inventory_allow_negative_stock)}
                    onChange={(e) => handleSettingChange('inventory_allow_negative_stock', e.target.checked)}
                    className="w-4 h-4 accent-[#167A93] cursor-pointer"
                  />
                </div>

                <div className="flex items-center justify-between p-3.5 bg-[#F8FDFE] border border-[#EAF4F6] rounded-xl">
                  <div>
                    <div className="font-bold text-[#16323B]">Notifikasi Stok Menipis & Habis</div>
                    <div className="text-[11px] text-[#577782]">
                      Tampilkan peringatan pada dashboard kasir dan admin ketika sisa stok mendekati batas minimum
                    </div>
                  </div>
                  <input
                    type="checkbox"
                    checked={Boolean(settings.inventory_low_stock_notification)}
                    onChange={(e) => handleSettingChange('inventory_low_stock_notification', e.target.checked)}
                    className="w-4 h-4 accent-[#167A93] cursor-pointer"
                  />
                </div>
              </div>

              <div className="flex justify-end pt-3">
                <button
                  type="button"
                  disabled={isSaving}
                  onClick={() =>
                    handleSaveSettings([
                      'inventory_default_min_stock',
                      'inventory_allow_negative_stock',
                      'inventory_low_stock_notification',
                    ])
                  }
                  className="px-5 py-2 bg-[#167A93] hover:bg-[#105668] text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer"
                >
                  {isSaving ? 'Menyimpan...' : 'Simpan Kebijakan Stok'}
                </button>
              </div>
            </div>
          )}

          {/* TAB 6: KEUANGAN & AKUNTANSI */}
          {activeTab === 'accounting' && (
            <div className="bg-white border border-[#D7EBEF] rounded-2xl p-6 shadow-xs space-y-6">
              <div className="border-b border-[#EAF4F6] pb-4">
                <h3 className="text-base font-bold text-[#16323B]">Tahun Buku & Penguncian Periode Akuntansi</h3>
                <p className="text-xs text-[#577782] mt-0.5">
                  Periode yang terkunci (Locked) mencegah penambahan atau pembalikan transaksi pada tanggal tersebut
                </p>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <div>
                  <label className="block font-semibold text-[#16323B] mb-1">Tahun Buku Berjalan</label>
                  <input
                    type="number"
                    value={settings.accounting_fiscal_year || 2026}
                    onChange={(e) => handleSettingChange('accounting_fiscal_year', Number(e.target.value))}
                    className="w-full px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs font-mono font-bold text-[#167A93]"
                  />
                </div>

                <div className="flex items-center justify-between p-3.5 bg-[#F8FDFE] border border-[#EAF4F6] rounded-xl md:col-span-2">
                  <div>
                    <div className="font-bold text-[#16323B]">Otomasi Jurnal Akuntansi</div>
                    <div className="text-[11px] text-[#577782]">
                      Otomatis catat jurnal debit/kredit saat penjualan kasir dan penerimaan pembelian barang toko
                    </div>
                  </div>
                  <input
                    type="checkbox"
                    checked={Boolean(settings.accounting_auto_journal)}
                    onChange={(e) => handleSettingChange('accounting_auto_journal', e.target.checked)}
                    className="w-4 h-4 accent-[#167A93] cursor-pointer"
                  />
                </div>
              </div>

              <div className="border-t border-[#EAF4F6] pt-4">
                <h4 className="text-xs font-bold text-[#16323B] mb-3 uppercase tracking-wider">
                  Daftar Periode Akuntansi Bulanan
                </h4>
                <div className="overflow-x-auto">
                  <table className="w-full text-xs text-left">
                    <thead>
                      <tr className="border-b border-[#D7EBEF] text-[#577782] font-semibold bg-[#F8FDFE]">
                        <th className="py-2.5 px-3">Nama Periode</th>
                        <th className="py-2.5 px-3">Rentang Tanggal</th>
                        <th className="py-2.5 px-3 text-center">Status Tutup Buku</th>
                        <th className="py-2.5 px-3 text-center">Kunci Jurnal (Lock)</th>
                        <th className="py-2.5 px-3 text-right">Aksi</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-[#EAF4F6]">
                      {periods.map((p) => (
                        <tr key={p.id} className="hover:bg-[#F8FDFE]">
                          <td className="py-2.5 px-3 font-semibold text-[#16323B]">{p.period_name}</td>
                          <td className="py-2.5 px-3 font-mono text-[11px] text-[#577782]">
                            {p.start_date} s/d {p.end_date}
                          </td>
                          <td className="py-2.5 px-3 text-center">
                            <span
                              className={`px-2 py-0.5 rounded text-[10px] font-bold ${
                                p.is_closed ? 'bg-purple-600 text-white' : 'bg-slate-200 text-slate-700'
                              }`}
                            >
                              {p.is_closed ? 'Tutup Buku' : 'Terbuka'}
                            </span>
                          </td>
                          <td className="py-2.5 px-3 text-center">
                            <span
                              className={`px-2.5 py-0.5 rounded-full text-[10px] font-bold ${
                                p.is_locked ? 'bg-rose-600 text-white' : 'bg-emerald-600 text-white'
                              }`}
                            >
                              {p.is_locked ? '🔒 Terkunci' : '🔓 Terbuka'}
                            </span>
                          </td>
                          <td className="py-2.5 px-3 text-right">
                            <button
                              type="button"
                              onClick={() => handleTogglePeriodLock(p.id)}
                              className={`px-2.5 py-1 rounded-lg text-xs font-semibold cursor-pointer ${
                                p.is_locked
                                  ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100'
                                  : 'bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100'
                              }`}
                            >
                              {p.is_locked ? 'Buka Kunci' : 'Kunci Periode'}
                            </button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}

          {/* TAB 7: HAK AKSES & ROLE */}
          {activeTab === 'roles' && (
            <div className="bg-white border border-[#D7EBEF] rounded-2xl p-6 shadow-xs space-y-6">
              <div className="border-b border-[#EAF4F6] pb-4">
                <h3 className="text-base font-bold text-[#16323B]">Pengaturan Role & Hak Akses Granular</h3>
                <p className="text-xs text-[#577782] mt-0.5">
                  Tentukan tindakan apa saja yang dapat dilakukan oleh Staff tanpa memberikan akses admin penuh
                </p>
              </div>

              {/* Role Picker Buttons */}
              <div className="flex items-center gap-3">
                {roles.map((r) => (
                  <button
                    key={r.id}
                    type="button"
                    onClick={() => handleRoleSelect(r.id)}
                    className={`px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                      selectedRoleId === r.id
                        ? 'bg-[#167A93] text-white shadow-xs'
                        : 'bg-[#F8FDFE] border border-[#D7EBEF] text-[#16323B] hover:bg-white'
                    }`}
                  >
                    {r.display_name} {r.name === 'admin' ? '(Akses Penuh)' : ''}
                  </button>
                ))}
              </div>

              {/* Permissions Checklist per Group */}
              <div className="space-y-5">
                {Object.entries(permissionsByGroup).map(([groupName, groupPerms]) => (
                  <div key={groupName} className="border border-[#EAF4F6] rounded-xl p-4 bg-[#FBFDFE]">
                    <h4 className="text-xs font-bold uppercase tracking-wider text-[#167A93] mb-3">
                      Modul: {groupName}
                    </h4>
                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                      {groupPerms.map((perm) => {
                        const isChecked = selectedPermissions.includes(perm.name);
                        const isAdminRole = roles.find((r) => r.id === selectedRoleId)?.name === 'admin';

                        return (
                          <label
                            key={perm.id}
                            className={`flex items-start gap-2.5 p-2.5 rounded-xl border transition cursor-pointer select-none ${
                              isChecked
                                ? 'bg-white border-[#27C5E5] shadow-xs'
                                : 'bg-[#F8FDFE] border-[#EAF4F6] opacity-75'
                            }`}
                          >
                            <input
                              type="checkbox"
                              disabled={isAdminRole}
                              checked={isChecked}
                              onChange={() => handleTogglePermission(perm.name)}
                              className="mt-0.5 w-4 h-4 accent-[#167A93] cursor-pointer disabled:cursor-not-allowed"
                            />
                            <div>
                              <div className="font-bold text-xs text-[#16323B]">{perm.display_name}</div>
                              <div className="text-[10px] text-[#577782] line-clamp-1">{perm.description}</div>
                            </div>
                          </label>
                        );
                      })}
                    </div>
                  </div>
                ))}
              </div>

              <div className="flex justify-end pt-3">
                <button
                  type="button"
                  disabled={isSaving || roles.find((r) => r.id === selectedRoleId)?.name === 'admin'}
                  onClick={handleSavePermissions}
                  className="px-5 py-2 bg-[#167A93] hover:bg-[#105668] text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50"
                >
                  {isSaving ? 'Menyimpan...' : 'Simpan Hak Akses Role'}
                </button>
              </div>
            </div>
          )}

          {/* TAB 8: CADANGAN & DATABASE */}
          {activeTab === 'backup' && (
            <div className="bg-white border border-[#D7EBEF] rounded-2xl p-6 shadow-xs space-y-6">
              <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-[#EAF4F6] pb-4">
                <div>
                  <h3 className="text-base font-bold text-[#16323B]">Cadangan Database & Pemulihan (Backup & Restore)</h3>
                  <p className="text-xs text-[#577782] mt-0.5">
                    Cadangkan seluruh tabel transaksi, stok, jurnal, dan konfigurasi toko secara aman
                  </p>
                </div>

                <div className="flex items-center gap-2">
                  <input
                    type="text"
                    value={backupNotes}
                    onChange={(e) => setBackupNotes(e.target.value)}
                    placeholder="Catatan backup (opsional)..."
                    className="px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs w-56"
                  />
                  <button
                    type="button"
                    disabled={isSaving}
                    onClick={handleCreateBackup}
                    className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer whitespace-nowrap"
                  >
                    {isSaving ? 'Mencadangkan...' : '💾 Buat Backup Sekarang'}
                  </button>
                </div>
              </div>

              {/* Backup History Table */}
              <div className="overflow-x-auto">
                <table className="w-full text-xs text-left">
                  <thead>
                    <tr className="border-b border-[#D7EBEF] text-[#577782] font-semibold bg-[#F8FDFE]">
                      <th className="py-2.5 px-3">Nama File</th>
                      <th className="py-2.5 px-3">Ukuran</th>
                      <th className="py-2.5 px-3">Tanggal Dibuat</th>
                      <th className="py-2.5 px-3">Checksum SHA256</th>
                      <th className="py-2.5 px-3">Catatan</th>
                      <th className="py-2.5 px-3 text-right">Aksi</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-[#EAF4F6]">
                    {backups.length === 0 ? (
                      <tr>
                        <td colSpan="6" className="py-8 text-center text-[#577782]">
                          Belum ada file cadangan yang dibuat.
                        </td>
                      </tr>
                    ) : (
                      backups.map((b) => (
                        <tr key={b.id} className="hover:bg-[#F8FDFE]">
                          <td className="py-3 px-3 font-mono font-bold text-[#167A93]">{b.filename}</td>
                          <td className="py-3 px-3 font-mono">{(b.file_size / 1024).toFixed(1)} KB</td>
                          <td className="py-3 px-3">{b.created_at?.slice(0, 19).replace('T', ' ')}</td>
                          <td className="py-3 px-3 font-mono text-[10px] text-[#577782] truncate max-w-xs" title={b.checksum}>
                            {b.checksum?.slice(0, 16)}...
                          </td>
                          <td className="py-3 px-3 text-[#577782]">{b.notes || '-'}</td>
                          <td className="py-3 px-3 text-right whitespace-nowrap">
                            <div className="flex items-center justify-end gap-2">
                              <a
                                href={settingsService.getBackupDownloadUrl(b.id)}
                                target="_blank"
                                rel="noreferrer"
                                className="px-2.5 py-1 bg-white border border-[#D7EBEF] rounded-lg text-xs font-semibold text-[#167A93] hover:bg-[#F3FBFC]"
                              >
                                Unduh
                              </a>
                              <button
                                type="button"
                                onClick={() => setRestoreModal({ open: true, backup: b, password: '', isRestoring: false })}
                                className="px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 rounded-lg text-xs font-semibold cursor-pointer"
                              >
                                Pulihkan
                              </button>
                            </div>
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>

              {/* Restore Confirmation Modal with Admin Password */}
              {restoreModal.open && (
                <div className="fixed inset-0 bg-[#10262E]/50 backdrop-blur-xs z-50 flex items-center justify-center p-4 animate-backdrop">
                  <div className="bg-white rounded-2xl border border-rose-200 max-w-md w-full p-6 shadow-2xl space-y-4 animate-modal-pop">
                    <div className="flex items-center gap-3 text-rose-600 font-bold text-base">
                      <span className="p-2 bg-rose-100 rounded-xl">⚠️</span>
                      <span>Konfirmasi Pemulihan Database</span>
                    </div>

                    <p className="text-xs text-[#577782]">
                      Tindakan ini akan menggantikan seluruh data database saat ini dengan data dari file cadangan{' '}
                      <strong className="text-[#16323B] font-mono">{restoreModal.backup?.filename}</strong>.
                    </p>

                    <div>
                      <label className="block text-xs font-bold text-[#16323B] mb-1">
                        Masukkan Password Admin Anda untuk Konfirmasi:
                      </label>
                      <input
                        type="password"
                        value={restoreModal.password}
                        onChange={(e) => setRestoreModal({ ...restoreModal, password: e.target.value })}
                        placeholder="Password administrator saat ini"
                        className="w-full px-3 py-2 border border-[#D7EBEF] rounded-xl text-xs"
                      />
                    </div>

                    <div className="flex justify-end gap-2 pt-3 border-t border-[#EAF4F6]">
                      <button
                        type="button"
                        disabled={restoreModal.isRestoring}
                        onClick={() => setRestoreModal({ open: false, backup: null, password: '', isRestoring: false })}
                        className="px-4 py-2 border border-[#D7EBEF] rounded-xl text-xs font-semibold cursor-pointer"
                      >
                        Batal
                      </button>
                      <button
                        type="button"
                        disabled={restoreModal.isRestoring || !restoreModal.password}
                        onClick={handleConfirmRestore}
                        className="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold cursor-pointer disabled:opacity-50"
                      >
                        {restoreModal.isRestoring ? 'Memulihkan Data...' : 'Konfirmasi Pulihkan Data'}
                      </button>
                    </div>
                  </div>
                </div>
              )}
            </div>
          )}

          {/* TAB 9: AUDIT TRAIL / LOG AKTIVITAS */}
          {activeTab === 'logs' && (
            <div className="bg-white border border-[#D7EBEF] rounded-2xl p-6 shadow-xs space-y-6">
              <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-[#EAF4F6] pb-4">
                <div>
                  <h3 className="text-base font-bold text-[#16323B]">Audit Trail & Log Aktivitas Sistem</h3>
                  <p className="text-xs text-[#577782] mt-0.5">
                    Catatan append-only yang merekam seluruh aksi penting pengguna (login, transaksi, pembelian toko, pengaturan)
                  </p>
                </div>

                <div className="flex items-center gap-2">
                  <input
                    type="text"
                    value={logFilters.search}
                    onChange={(e) => {
                      setLogFilters({ ...logFilters, search: e.target.value });
                      loadActivityLogs({ ...logFilters, search: e.target.value });
                    }}
                    placeholder="Cari deskripsi atau pengguna..."
                    className="px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs w-56"
                  />
                  <select
                    value={logFilters.module}
                    onChange={(e) => {
                      setLogFilters({ ...logFilters, module: e.target.value });
                      loadActivityLogs({ ...logFilters, module: e.target.value });
                    }}
                    className="px-3 py-2 bg-[#F8FDFE] border border-[#D7EBEF] rounded-xl text-xs"
                  >
                    <option value="">Semua Modul</option>
                    <option value="auth">Autentikasi</option>
                    <option value="pos">POS Kasir</option>
                    <option value="purchases">Pembelian Toko</option>
                    <option value="inventory">Persediaan</option>
                    <option value="accounting">Akuntansi</option>
                    <option value="settings">Pengaturan</option>
                    <option value="backup">Cadangan</option>
                  </select>
                </div>
              </div>

              {/* Logs Table */}
              <div className="overflow-x-auto">
                <table className="w-full text-xs text-left">
                  <thead>
                    <tr className="border-b border-[#D7EBEF] text-[#577782] font-semibold bg-[#F8FDFE]">
                      <th className="py-2.5 px-3">Waktu</th>
                      <th className="py-2.5 px-3">Pengguna</th>
                      <th className="py-2.5 px-3">Modul</th>
                      <th className="py-2.5 px-3">Tindakan</th>
                      <th className="py-2.5 px-3">Deskripsi Aktivitas</th>
                      <th className="py-2.5 px-3">IP Address</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-[#EAF4F6]">
                    {logsLoading ? (
                      <tr>
                        <td colSpan="6" className="py-8 text-center text-[#577782]">
                          Memuat log aktivitas...
                        </td>
                      </tr>
                    ) : activityLogs.length === 0 ? (
                      <tr>
                        <td colSpan="6" className="py-8 text-center text-[#577782]">
                          Belum ada log aktivitas yang tercatat.
                        </td>
                      </tr>
                    ) : (
                      activityLogs.map((log) => (
                        <tr key={log.id} className="hover:bg-[#F8FDFE]">
                          <td className="py-2.5 px-3 whitespace-nowrap text-[#577782] font-mono text-[11px]">
                            {log.created_at?.slice(0, 19).replace('T', ' ')}
                          </td>
                          <td className="py-2.5 px-3 font-semibold text-[#16323B] whitespace-nowrap">
                            {log.user_name}
                            <span className="ml-1 text-[10px] text-[#577782]">({log.role})</span>
                          </td>
                          <td className="py-2.5 px-3 uppercase text-[10px] font-bold text-[#167A93] whitespace-nowrap">
                            {log.module}
                          </td>
                          <td className="py-2.5 px-3 whitespace-nowrap">
                            <span
                              className={`px-2 py-0.5 rounded text-[10px] font-bold ${
                                log.action === 'login'
                                  ? 'bg-blue-600 text-white'
                                  : log.action === 'logout'
                                  ? 'bg-slate-600 text-white'
                                  : log.action === 'create'
                                  ? 'bg-emerald-600 text-white'
                                  : log.action === 'cancel'
                                  ? 'bg-rose-600 text-white'
                                  : 'bg-amber-600 text-white'
                              }`}
                            >
                              {log.action}
                            </span>
                          </td>
                          <td className="py-2.5 px-3 text-[#16323B]">{log.description}</td>
                          <td className="py-2.5 px-3 font-mono text-[11px] text-[#577782] whitespace-nowrap">
                            {log.ip_address}
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

export default SettingsPage;
