/**
 * Utilitas shell UI:
 *  - Toggle sidebar off-canvas (mobile)
 *  - Jam WIB live di topbar (#live-clock)
 *  - Toast dari session flash (container #toast-root, ter-render Blade)
 *  - Konfirmasi aksi destruktif (elemen dengan [data-confirm])
 */

// ── Sidebar off-canvas (mobile) ────────────────────────────────────────────
const sidebar = document.getElementById('sidebar');
const sidebarOpenBtn = document.getElementById('sidebar-open');
const sidebarCloseBtn = document.getElementById('sidebar-close');
const sidebarBackdrop = document.getElementById('sidebar-backdrop');

function openSidebar() {
    sidebar?.classList.remove('-translate-x-full');
    sidebarBackdrop?.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closeSidebar() {
    sidebar?.classList.add('-translate-x-full');
    sidebarBackdrop?.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

sidebarOpenBtn?.addEventListener('click', openSidebar);
sidebarCloseBtn?.addEventListener('click', closeSidebar);
sidebarBackdrop?.addEventListener('click', closeSidebar);

// ── Jam live (format HH:MM:SS WIB — timezone aplikasi sudah Asia/Jakarta) ───
const clockEl = document.getElementById('live-clock');

function tickClock() {
    if (!clockEl) return;
    const now = new Date();
    clockEl.textContent =
        String(now.getHours()).padStart(2, '0') + ':' +
        String(now.getMinutes()).padStart(2, '0') + ':' +
        String(now.getSeconds()).padStart(2, '0') + ' WIB';
}

tickClock();
setInterval(tickClock, 1000);

// ── Toast (flash message dari session) ─────────────────────────────────────
function showToast() {
    document.querySelectorAll('#toast-root [data-toast]').forEach((toast) => {
        toast.classList.remove('hidden');
        toast.classList.add('animate-[toast-in_0.25s_ease-out]');

        const close = () => {
            toast.classList.add('opacity-0', 'transition-opacity', 'duration-200');
            setTimeout(() => toast.remove(), 220);
        };

        toast.querySelector('[data-toast-close]')?.addEventListener('click', close);
        setTimeout(close, 4500);
    });
}

showToast();

// ── Konfirmasi aksi destruktif ─────────────────────────────────────────────
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm || 'Apakah Anda yakin?')) {
            event.preventDefault();
        }
    });
});

// ── Auto-submit form (filter tanpa tombol, [data-auto-submit]) ─────────────
document.querySelectorAll('select[data-auto-submit], input[data-auto-submit]').forEach((el) => {
    const form = el.closest('form');
    el.addEventListener('change', () => form?.requestSubmit());
});

// ── Segmented control radio (Masuk/Libur per hari, pengaturan jam) ─────────
// Menyegarkan gaya terpilih & menggelapkan kartu hari saat status = libur.
document.querySelectorAll('[data-segmen]').forEach((grup) => {
    const perbarui = () => {
        grup.querySelectorAll('label').forEach((lbl) => {
            const aktif = lbl.querySelector('input')?.checked;
            lbl.classList.toggle('bg-primary-container', aktif);
            lbl.classList.toggle('text-on-primary-container', aktif);
            lbl.classList.toggle('text-on-surface-variant', !aktif);
        });
        const kartu = grup.closest('[data-hari-card]');
        kartu?.classList.toggle('opacity-60', grup.querySelector('input[value="libur"]:checked') !== null);
    };
    grup.addEventListener('change', perbarui);
    perbarui();
});

// ── Toast animation keyframes (injected sekali) ────────────────────────────
const style = document.createElement('style');
style.textContent =
    '@keyframes toast-in{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}';
document.head.appendChild(style);
