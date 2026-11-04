/**
 * app.js - dipakai di semua halaman dashboard.
 * Berisi: modal, sidebar mobile drawer, konfirmasi hapus, alert otomatis hilang.
 */

function openModal(id) {
    var modal = document.getElementById(id || 'modal');
    if (modal) {
        modal.classList.add('show');
        var first = modal.querySelector('input:not([type=hidden]), select, textarea');
        if (first) setTimeout(function () { first.focus(); }, 120);
    }
}

function closeModal(id) {
    var modal = document.getElementById(id || 'modal');
    if (modal) modal.classList.remove('show');
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CUSTOM POPUP KONFIRMASI HAPUS & TOAST NOTIFICATION (FINTOKO THEME)
 * ═══════════════════════════════════════════════════════════════════════════
 */
window.fintokoDeleteConfirm = function (options) {
    var title     = options.title || 'Konfirmasi Hapus Data';
    var text      = options.text  || 'Apakah Anda yakin ingin menghapus data ini? Data yang dihapus tidak dapat dipulihkan kembali.';
    var itemName  = options.itemName ? '<div style="margin-top:10px;padding:9px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;font-weight:700;color:#0f172a;font-size:13px;word-break:break-all;">' + options.itemName + '</div>' : '';
    var onConfirm = options.onConfirm;

    if (window.Swal) {
        Swal.fire({
            title: title,
            html: '<div style="font-size:14px;color:#475569;line-height:1.5;">' + text + '</div>' + itemName,
            icon: 'warning',
            iconColor: '#ef4444',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: '<i class="fa-solid fa-trash-can" style="margin-right:6px"></i> Ya, Hapus',
            cancelButtonText: '<i class="fa-solid fa-xmark" style="margin-right:6px"></i> Batal',
            reverseButtons: true,
            backdrop: 'rgba(15, 23, 42, 0.45)',
            customClass: {
                popup: 'fintoko-swal-popup',
                title: 'fintoko-swal-title',
                htmlContainer: 'fintoko-swal-text',
                confirmButton: 'fintoko-swal-confirm',
                cancelButton: 'fintoko-swal-cancel',
            }
        }).then(function (result) {
            if (result.isConfirmed && typeof onConfirm === 'function') {
                onConfirm();
            }
        });
    } else {
        if (confirm(text.replace(/<[^>]*>/g, ''))) {
            if (typeof onConfirm === 'function') onConfirm();
        }
    }
};

window.fintokoToast = function (msg, type) {
    type = type || 'success';
    if (window.Swal) {
        var Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: function (toast) {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        Toast.fire({
            icon: type,
            title: msg
        });
    }
};

document.addEventListener('DOMContentLoaded', function () {

    // Tutup modal saat klik area gelap atau tekan Escape
    document.querySelectorAll('.modal').forEach(function (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) modal.classList.remove('show');
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.show').forEach(function (m) { m.classList.remove('show'); });
        }
    });

    // ── Dropdown Profil Pengguna (Header Topbar) ──
    var userProfileBtn   = document.getElementById('userProfileBtn');
    var userDropdownMenu = document.getElementById('userDropdownMenu');

    if (userProfileBtn && userDropdownMenu) {
        var toggleUserDropdown = function (e) {
            e.stopPropagation();
            var isOpen = userDropdownMenu.classList.contains('show');
            if (isOpen) {
                userDropdownMenu.classList.remove('show');
                userProfileBtn.classList.remove('active');
                userProfileBtn.setAttribute('aria-expanded', 'false');
            } else {
                userDropdownMenu.classList.add('show');
                userProfileBtn.classList.add('active');
                userProfileBtn.setAttribute('aria-expanded', 'true');
            }
        };

        var closeUserDropdown = function () {
            if (userDropdownMenu.classList.contains('show')) {
                userDropdownMenu.classList.remove('show');
                userProfileBtn.classList.remove('active');
                userProfileBtn.setAttribute('aria-expanded', 'false');
            }
        };

        userProfileBtn.addEventListener('click', toggleUserDropdown);

        // Tutup dropdown saat klik di luar area profil
        document.addEventListener('click', function (e) {
            if (!userDropdownMenu.contains(e.target) && !userProfileBtn.contains(e.target)) {
                closeUserDropdown();
            }
        });

        // Tutup dropdown saat menekan tombol Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeUserDropdown();
            }
        });
    }

    // ── Sidebar Mobile Drawer Toggle & Overlay ──
    var burger   = document.getElementById('burgerBtn');
    var sidebar  = document.getElementById('sidebar');
    var overlay  = document.getElementById('sidebarOverlay');
    var closeBtn = document.getElementById('sidebarCloseBtn');

    if (sidebar && overlay) {
        var openSidebar = function () {
            sidebar.classList.add('show');
            overlay.classList.add('show');
            document.body.classList.add('sidebar-open');
        };

        var closeSidebar = function () {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        };

        if (burger) {
            burger.addEventListener('click', function (e) {
                e.stopPropagation();
                if (sidebar.classList.contains('show')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });
        }

        overlay.addEventListener('click', closeSidebar);
        if (closeBtn) {
            closeBtn.addEventListener('click', closeSidebar);
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sidebar.classList.contains('show')) {
                closeSidebar();
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 992 && sidebar.classList.contains('show')) {
                closeSidebar();
            }
        });
    }

    // Konfirmasi sebelum submit form yang punya data-confirm (hapus data)
    // + cegah klik ganda pada tombol simpan
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var pesan = form.getAttribute('data-confirm');
            if (pesan && !window.confirm(pesan)) {
                e.preventDefault();
                return;
            }
            var simpan = form.querySelector('.btn-save');
            if (simpan) {
                setTimeout(function () { simpan.disabled = true; }, 0);
            }
        });
    });

    // Alert hilang otomatis setelah beberapa detik
    document.querySelectorAll('.alert').forEach(function (alertEl) {
        setTimeout(function () {
            alertEl.classList.add('hide');
            setTimeout(function () { alertEl.remove(); }, 450);
        }, 5000);
    });

    // Inisialisasi Real-Time AJAX Filter & Search Engine
    if (window.FintokoAjax) {
        window.FintokoAjax.init();
    }
});

// Halaman dipulihkan lewat tombol Back: pastikan tombol simpan aktif lagi
window.addEventListener('pageshow', function (e) {
    if (e.persisted) {
        document.querySelectorAll('.btn-save').forEach(function (b) { b.disabled = false; });
    }
});

/**
 * =====================================================================
 * FINTOKO REAL-TIME AJAX FILTER & SEARCH ENGINE (ZERO-PAGE-RELOAD)
 * =====================================================================
 * Arsitektur modern tanpa jQuery, zero-dependency berbasis Fetch API
 * dan DOMParser untuk pertukaran data tabel, metrik, dan filter secara
 * instan tanpa perlu memuat ulang seluruh halaman (no full page reload).
 * =====================================================================
 */
window.FintokoAjax = (function () {
    var abortCtrl = null;
    var searchTimer = null;
    var loaderEl = null;

    // Pastikan indikator loading top-bar tersedia di DOM
    function ensureLoader() {
        if (!loaderEl) {
            loaderEl = document.getElementById('fintokoAjaxLoader');
            if (!loaderEl) {
                loaderEl = document.createElement('div');
                loaderEl.id = 'fintokoAjaxLoader';
                document.body.appendChild(loaderEl);
            }
        }
        return loaderEl;
    }

    function showLoader() {
        var bar = ensureLoader();
        bar.classList.add('active');
        bar.style.width = '30%';
        bar.style.opacity = '1';
        setTimeout(function () {
            if (bar.classList.contains('active')) {
                bar.style.width = '75%';
            }
        }, 80);

        // Beri efek redup transparan pada area tabel utama
        getPanelsToUpdate().forEach(function (panel) {
            panel.classList.add('ajax-loading-panel');
        });
    }

    function hideLoader() {
        var bar = ensureLoader();
        bar.style.width = '100%';
        setTimeout(function () {
            bar.classList.remove('active');
            bar.style.opacity = '0';
            setTimeout(function () {
                bar.style.width = '0%';
            }, 250);
        }, 120);

        getPanelsToUpdate().forEach(function (panel) {
            panel.classList.remove('ajax-loading-panel');
        });
    }

    // Daftar selektor elemen tabel/panel konten utama
    var panelSelectors = [
        '#stkPanel',
        '#mtsPanel',
        '#printArea',
        '#jrnSheet',
        '#bkbSheet',
        '.panel-kas',
        '.stk-panel',
        '.mts-panel',
        '.jrn-sheet-container',
        '.bkb-sheet-container'
    ];

    // Daftar selektor metric cards
    var metricSelectors = [
        '#stkMetrics',
        '#mtsMetrics',
        '.kas-metrics',
        '.pm-metrics',
        '.pg-metrics',
        '.bkb-metrics',
        '.jrn-metrics',
        '.stk-metrics',
        '.mts-metrics'
    ];

    // Daftar selektor badge counter & ringkasan hasil
    var countSelectors = [
        '.stk-total-info',
        '.stk-panel__count',
        '.mts-toolbar__count',
        '.mts-panel__count',
        '.record-badge',
        '.filter-summary',
        '.filter-active-badge'
    ];

    // Daftar selektor wadah filter pills
    var pillContainerSelectors = [
        '.stk-categories',
        '.mts-quick-pills',
        '.periode-btns',
        '.jrn-quick-nav',
        '.bkb-quick-nav'
    ];

    function getPanelsToUpdate() {
        var list = [];
        panelSelectors.forEach(function (sel) {
            var el = document.querySelector(sel);
            if (el) list.push(el);
        });
        return list;
    }

    /**
     * Memuat URL target secara asinkron lalu menukar fragmen DOM
     */
    function load(targetUrl, options) {
        options = options || {};
        var skipPushState = !!options.skipPushState;

        // Batalkan request sebelumnya jika user mengetik cepat
        if (abortCtrl) {
            abortCtrl.abort();
        }
        abortCtrl = new AbortController();

        showLoader();

        fetch(targetUrl, {
            signal: abortCtrl.signal,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function (response) {
            if (!response.ok) throw new Error('Network error: ' + response.status);
            return response.text();
        })
        .then(function (htmlText) {
            var parser = new DOMParser();
            var doc = parser.parseFromString(htmlText, 'text/html');

            // 1. Swap Panel Tabel / Konten Data
            panelSelectors.forEach(function (sel) {
                var oldEl = document.querySelector(sel);
                var newEl = doc.querySelector(sel);
                if (oldEl && newEl) {
                    oldEl.innerHTML = newEl.innerHTML;
                    // Salin atribut jika ada perubahan
                    if (newEl.className) oldEl.className = newEl.className;
                    oldEl.classList.add('ajax-fade-in');
                    setTimeout(function () {
                        oldEl.classList.remove('ajax-fade-in');
                    }, 400);
                }
            });

            // 2. Swap Metric Cards
            metricSelectors.forEach(function (sel) {
                var oldM = document.querySelector(sel);
                var newM = doc.querySelector(sel);
                if (oldM && newM) {
                    oldM.innerHTML = newM.innerHTML;
                    oldM.classList.add('ajax-fade-in');
                    setTimeout(function () {
                        oldM.classList.remove('ajax-fade-in');
                    }, 400);
                }
            });

            // 3. Swap Badges & Counter Info
            countSelectors.forEach(function (sel) {
                var oldEl = document.querySelector(sel);
                var newEl = doc.querySelector(sel);
                if (oldEl && newEl) {
                    oldEl.innerHTML = newEl.innerHTML;
                } else if (!oldEl && newEl) {
                    // Jika sebelumnya tersembunyi/tidak ada
                    var parentHead = document.querySelector('.panel-title, .panel-head > div, .stk-toolbar__top-left');
                    if (parentHead) {
                        var temp = newEl.cloneNode(true);
                        parentHead.appendChild(temp);
                    }
                } else if (oldEl && !newEl) {
                    oldEl.remove();
                }
            });

            // 4. Swap Filter Pills (Kategori / Periode / Navigasi Bulan)
            pillContainerSelectors.forEach(function (sel) {
                var oldPills = document.querySelector(sel);
                var newPills = doc.querySelector(sel);
                if (oldPills && newPills) {
                    oldPills.innerHTML = newPills.innerHTML;
                }
            });

            // 5. Update Tombol Reset & Clear Search
            ['.sb-clear', '.btn-filter-reset', '.stk-btn-reset', '.mts-btn-reset'].forEach(function (sel) {
                var oldReset = document.querySelector(sel);
                var newReset = doc.querySelector(sel);
                if (oldReset && newReset) {
                    oldReset.outerHTML = newReset.outerHTML;
                } else if (oldReset && !newReset) {
                    oldReset.remove();
                } else if (!oldReset && newReset) {
                    var container = document.querySelector('.search-box, .filter-right-group, .stk-toolbar__top-right, .mts-toolbar__top');
                    if (container) {
                        var node = newReset.cloneNode(true);
                        container.appendChild(node);
                    }
                }
            });

            // 6. Sinkronkan Nilai Input Form (kecuali yang sedang aktif diketik)
            var activeEl = document.activeElement;
            doc.querySelectorAll('form').forEach(function (newForm) {
                var currentForm = document.getElementById(newForm.id);
                if (currentForm) {
                    newForm.querySelectorAll('input, select').forEach(function (newInput) {
                        if (!newInput.name) return;
                        var curInput = currentForm.querySelector('[name="' + newInput.name + '"]');
                        if (curInput && curInput !== activeEl) {
                            curInput.value = newInput.value;
                        }
                    });
                }
            });

            // 7. Update Link Export Excel / CSV agar sesuai filter yang aktif
            doc.querySelectorAll('a[href*="export="]').forEach(function (newExp) {
                var oldExp = document.querySelector('a[href*="export="]');
                if (oldExp && newExp) {
                    oldExp.href = newExp.href;
                }
            });

            // 8. Sinkronisasi History URL Browser
            if (!skipPushState && window.history && window.history.replaceState) {
                window.history.replaceState({}, '', targetUrl);
            }

            hideLoader();
        })
        .catch(function (err) {
            if (err.name !== 'AbortError') {
                console.error('[FintokoAjax Error]', err);
                hideLoader();
            }
        });
    }

    /**
     * Submit form filter via AJAX
     */
    function submitFilterForm(form) {
        if (!form) return;
        var formData = new FormData(form);
        var params = new URLSearchParams();

        for (var pair of formData.entries()) {
            var key = pair[0];
            var val = pair[1];
            // Abaikan input kosong dan token csrf untuk form filter GET
            if (val !== '' && val !== null && key !== 'csrf') {
                params.append(key, val);
            }
        }

        var actionUrl = form.getAttribute('action') || window.location.pathname;
        var fullUrl = actionUrl + (params.toString() ? ('?' + params.toString()) : '');
        load(fullUrl);
    }

    /**
     * Inisialisasi event listener global
     */
    function init() {
        // Deteksi form filter
        var filterFormSelectors = [
            '#filterForm',
            '#filterFormStok',
            '#filterFormMutasi',
            '.jrn-filter-form',
            '.bkb-filter-form',
            '.stk-toolbar__form',
            '.mts-toolbar__form',
            'form.filter-form'
        ];

        // 1. Intercept Submit pada Form Filter
        document.addEventListener('submit', function (e) {
            var form = e.target.closest(filterFormSelectors.join(','));
            if (form && form.method.toUpperCase() === 'GET') {
                e.preventDefault();
                submitFilterForm(form);
            }
        });

        // 2. Live Search dengan Debounce 250ms saat user mengetik
        var searchSelectors = [
            '#searchInput',
            '#searchInputStok',
            '#searchInputMutasi',
            '.sb-input',
            '.stk-search__input',
            '.mts-search__input',
            'input[name="q"]',
            'input[name="cari"]'
        ];

        document.addEventListener('input', function (e) {
            var input = e.target.closest(searchSelectors.join(','));
            if (!input) return;

            var form = input.closest('form') || document.querySelector(filterFormSelectors.join(','));
            if (!form) return;

            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                submitFilterForm(form);
            }, 250);
        });

        // 3. Intercept Perubahan Dropdown & Date Picker
        document.addEventListener('change', function (e) {
            var target = e.target;
            var form = target.closest(filterFormSelectors.join(','));
            if (!form) return;

            // Jika elemen adalah select atau date input di dalam form filter
            if (target.matches('select, input[type="date"], input[type="month"]')) {
                e.preventDefault();
                submitFilterForm(form);
            }
        });

        // 4. Intercept Klik Tombol Filter Pills, Navigasi Bulan, Reset, dan Pagination
        var clickSelectors = [
            '.stk-pill',
            '.mts-pill',
            '.prd-btn',
            '.jrn-btn-nav',
            '.bkb-btn-nav',
            '.sb-clear',
            '.btn-filter-reset',
            '.stk-btn-reset',
            '.mts-btn-reset',
            '.pagination a',
            '.stk-pagination a',
            '.mts-pagination a'
        ];

        document.addEventListener('click', function (e) {
            var btn = e.target.closest(clickSelectors.join(','));
            if (!btn) return;

            var href = btn.getAttribute('href');
            if (href && href !== '#' && !href.startsWith('javascript:')) {
                // Jangan cegah jika klik tombol export/download
                if (href.indexOf('export=') !== -1) return;

                e.preventDefault();
                load(href);
            }
        });

        // 5. Tangani Tombol Back / Forward Browser
        window.addEventListener('popstate', function () {
            load(window.location.href, { skipPushState: true });
        });
    }

    return {
        init: init,
        load: load,
        submit: submitFilterForm
    };
})();
