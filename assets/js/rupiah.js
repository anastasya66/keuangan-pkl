/**
 * rupiah.js - Format Input Nominal Rupiah (Real-time)
 * ─────────────────────────────────────────────────────
 * Cara pakai:
 *   Tambahkan class="rupiah-input" pada <input> nominal.
 *   Script ini otomatis:
 *     1. Mengubah tampilan menjadi angka berformat titik ribuan (20.000)
 *     2. Membuat hidden <input> dengan name yang sama untuk submit ke server
 *        sehingga PHP tetap menerima angka murni (20000)
 *
 * Tidak ada dependency (vanilla JS). Cukup load 1x di layout/template.
 */

(function () {
    'use strict';

    /* ── Utilitas ──────────────────────────────────────────────────────── */

    /**
     * Ubah string (mungkin berformat) → angka bulat murni (integer).
     * Contoh: "365.000" → 365000 | "Rp 1.200.000" → 1200000 | "" → 0
     */
    function toRaw(str) {
        if (!str && str !== 0) return 0;
        // Hapus semua karakter bukan digit
        var digits = String(str).replace(/\D/g, '');
        return digits === '' ? 0 : parseInt(digits, 10);
    }

    /**
     * Format angka bulat menjadi string dengan pemisah ribuan titik (.).
     * Contoh: 365000 → "365.000" | 0 → ""
     */
    function toFormatted(num) {
        if (!num || isNaN(num) || num === 0) return '';
        return num.toLocaleString('id-ID');   // id-ID → pemisah ribuan: titik
    }

    /* ── Inti: Inisialisasi satu input ─────────────────────────────────── */

    function initRupiahInput(visibleInput) {
        // Hindari inisialisasi ganda
        if (visibleInput.dataset.rupiahInit === '1') return;
        visibleInput.dataset.rupiahInit = '1';

        var originalName = visibleInput.name;
        var originalId   = visibleInput.id;

        // ── Buat hidden input sebagai pembawa nilai bersih ke server ──
        var hiddenInput = document.createElement('input');
        hiddenInput.type  = 'hidden';
        hiddenInput.name  = originalName;          // name asli → dikirim ke PHP
        hiddenInput.value = toRaw(visibleInput.value);

        // Hapus name dari input tampilan supaya tidak ikut ter-submit
        visibleInput.removeAttribute('name');
        // Ubah type ke text agar tidak ada spinner & bisa terima titik
        visibleInput.type = 'text';
        // Atribut inputmode=numeric memunculkan keyboard angka di mobile
        visibleInput.setAttribute('inputmode', 'numeric');
        visibleInput.autocomplete = 'off';

        // Sisipkan hidden input setelah visible input
        visibleInput.insertAdjacentElement('afterend', hiddenInput);

        // Tampilkan nilai awal jika sudah ada (misal: form edit)
        var initialRaw = toRaw(visibleInput.value);
        if (initialRaw > 0) {
            visibleInput.value = toFormatted(initialRaw);
            hiddenInput.value  = initialRaw;
        }

        /* ── Event: saat mengetik ──────────────────────────────────── */
        visibleInput.addEventListener('input', function () {
            var raw       = toRaw(this.value);
            hiddenInput.value = raw > 0 ? raw : '';

            // Simpan posisi kursor agar tidak loncat ke akhir
            var start = this.selectionStart;
            var end   = this.selectionEnd;
            var oldLen = this.value.length;

            this.value = raw > 0 ? toFormatted(raw) : '';

            // Sesuaikan posisi kursor setelah format (titik bisa bergeser)
            var newLen = this.value.length;
            var diff   = newLen - oldLen;
            try {
                this.setSelectionRange(start + diff, end + diff);
            } catch (e) { /* input type mungkin tidak mendukung */ }
        });

        /* ── Event: saat focus (bersihkan placeholder 0) ──────────── */
        visibleInput.addEventListener('focus', function () {
            if (this.value === '0') this.value = '';
        });

        /* ── Event: saat blur (validasi & pembersihan akhir) ────────── */
        visibleInput.addEventListener('blur', function () {
            var raw = toRaw(this.value);
            if (raw > 0) {
                this.value        = toFormatted(raw);
                hiddenInput.value = raw;
            } else {
                this.value        = '';
                hiddenInput.value = '';
            }
        });

        /* ── Intercept submit: pastikan hidden selalu terupdate ──────── */
        var form = visibleInput.closest('form');
        if (form && !form.dataset.rupiahSubmitBound) {
            form.dataset.rupiahSubmitBound = '1';
            form.addEventListener('submit', function () {
                // Sinkronkan semua rupiah-input dalam form ini
                form.querySelectorAll('.rupiah-input').forEach(function (inp) {
                    var hid = inp.nextElementSibling;
                    if (hid && hid.type === 'hidden') {
                        hid.value = toRaw(inp.value) || '';
                    }
                });
            });
        }
    }

    /* ── Inisialisasi semua .rupiah-input yang sudah ada di DOM ───────── */

    function initAll() {
        document.querySelectorAll('input.rupiah-input').forEach(initRupiahInput);
    }

    /* ── MutationObserver: tangani input yang dibuat dinamis (modal dll) ─ */

    function watchDynamic() {
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                m.addedNodes.forEach(function (node) {
                    if (node.nodeType !== 1) return;           // bukan element
                    // Node itu sendiri
                    if (node.matches && node.matches('input.rupiah-input')) {
                        initRupiahInput(node);
                    }
                    // Turunan node
                    if (node.querySelectorAll) {
                        node.querySelectorAll('input.rupiah-input').forEach(initRupiahInput);
                    }
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    /* ── Ekspos ke global (opsional, untuk inisialisasi manual) ─────────── */

    window.RupiahInput = {
        init    : initAll,
        initOne : initRupiahInput,
        toRaw   : toRaw,
        fmt     : toFormatted
    };

    /* ── Jalankan ────────────────────────────────────────────────────── */

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initAll();
            watchDynamic();
        });
    } else {
        initAll();
        watchDynamic();
    }

})();

