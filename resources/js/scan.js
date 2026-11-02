/**
 * Task 11/13 — Scanner gerbang & pulang (html5-qrcode + input manual petugas).
 * Halaman menentukan perilaku via data-mode="gerbang"|"pulang".
 * Feedback: kartu hasil kontras tinggi, beep WebAudio, getar, KPI & log roll.
 */
import { Html5Qrcode } from 'html5-qrcode';

document.addEventListener('DOMContentLoaded', () => {
    const akar = document.getElementById('scan-gerbang');
    if (!akar) return;

    const mode = akar.dataset.mode || 'gerbang';
    const url = akar.dataset.url;
    const csrf = akar.dataset.csrf;
    const boleh = akar.dataset.boleh === '1';
    // gerbang: batas tepat waktu → tutup · pulang: mulai → akhir jendela
    const targetAwal = akar.dataset.batas || akar.dataset.mulai || '';
    const targetAkhir = akar.dataset.tutup || akar.dataset.akhir || '';
    const detikServer = detikHariIni(waktuServer(akar.dataset.server));

    /* ── Suara beep ─────────────────────────────────────────── */
    let suaraAktif = localStorage.getItem('absensi-suara') !== '0';
    const tombolSuara = document.getElementById('btn-suara');
    const ikonSuara = tombolSuara?.querySelector('[data-ikon-suara]');

    const paintSuara = () => {
        if (ikonSuara) ikonSuara.textContent = suaraAktif ? 'volume_up' : 'volume_off';
    };
    paintSuara();

    tombolSuara?.addEventListener('click', () => {
        suaraAktif = !suaraAktif;
        localStorage.setItem('absensi-suara', suaraAktif ? '1' : '0');
        paintSuara();
    });

    function bunyi(naik) {
        if (!suaraAktif) return;
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const [f0, f1] = naik ? [660, 990] : [320, 240];
            [f0, f1].forEach((freq, i) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = freq;
                gain.gain.setValueAtTime(0.12, ctx.currentTime + i * 0.12);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + i * 0.12 + 0.15);
                osc.connect(gain).connect(ctx.destination);
                osc.start(ctx.currentTime + i * 0.12);
                osc.stop(ctx.currentTime + i * 0.12 + 0.16);
            });
        } catch { /* audio tidak tersedia — abaikan */ }
    }

    const getar = (polab) => { try { navigator.vibrate?.(polab); } catch { /* abaikan */ } };

    /* ── Kamera ─────────────────────────────────────────────── */
    const mount = document.getElementById('scanner-gerbang');
    const statusKamera = document.querySelector('[data-status-kamera]');
    let scanner = null;
    let cooldown = false;

    const jeda = (ms) => new Promise((r) => setTimeout(r, ms));

    if (boleh) {
        scanner = new Html5Qrcode('scanner-gerbang', { verbose: false });
        scanner
            .start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 200, height: 200 } }, onTerdeteksi, () => {})
            .then(() => {
                mount?.querySelector('[data-ikon-mounter]')?.remove();
                if (statusKamera) statusKamera.textContent = 'Kamera aktif · mode QR';
            })
            .catch(() => {
                if (statusKamera) statusKamera.textContent = 'Kamera tidak tersedia — pakai Input Manual';
            });
    }

    async function onTerdeteksi(teks) {
        if (cooldown) return;
        cooldown = true;
        try { await scanner?.pause(true); } catch { /* belum jalan */ }
        await proses(teks, false, null, false);
        await jeda(3000);
        try { await scanner?.resume(); } catch { /* abaikan */ }
        cooldown = false;
    }

    /* ── Kirim ke server ────────────────────────────────────── */
    async function proses(kode, manual, keterangan, pulangCepat) {
        let data;
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ kode, manual: manual ? 1 : 0, pulang_cepat: pulangCepat ? 1 : 0, keterangan }),
            });
            data = await res.json();
        } catch {
            data = { jenis: 'tolak', pesan: 'Server tidak merespons — coba lagi.' };
        }

        renderKartu(data);
        bunyi(data.jenis === 'sukses');
        getar(data.jenis === 'sukses' ? 120 : [60, 80, 60]);

        if (data.jenis === 'sukses') {
            tambahLog(data);
            naikkanKpi(data.status);
        }
    }

    /* ── Kartu hasil (semua via textContent — anti-XSS) ─────── */
    const isiKartu = document.querySelector('[data-isi-kartu]');

    function el(tag, kelas, teks) {
        const node = document.createElement(tag);
        if (kelas) node.className = kelas;
        if (teks !== undefined) node.textContent = teks;
        return node;
    }

    function renderKartu(data) {
        if (!isiKartu) return;

        const gaya = {
            sukses: ['bg-tertiary-container', 'text-on-tertiary-container', 'verified'],
            info: ['bg-secondary-container', 'text-on-secondary-container', 'info'],
            tolak: ['bg-error-container', 'text-on-error-container', 'block'],
        }[data.jenis] || ['bg-surface-container', 'text-on-surface-variant', 'help'];

        isiKartu.className = 'flex flex-col gap-space-sm';
        isiKartu.replaceChildren();

        // Baris atas: pill status + jam
        const atas = el('div', 'flex items-center justify-between gap-space-sm');
        const teksPill = data.jenis === 'sukses'
            ? data.status_label || 'Terverifikasi'
            : (data.pesan || data.status_label || data.jenis);
        const pill = el('span', `inline-flex items-center gap-1.5 rounded-full px-3 py-1 font-label-md font-bold ${gaya[0]} ${gaya[1]}`);
        pill.append(
            Object.assign(el('span', 'material-symbols-outlined text-[16px]'), { textContent: gaya[2] }),
            document.createTextNode(' ' + teksPill.toUpperCase()),
        );
        atas.append(pill);
        if (data.waktu) atas.append(el('span', 'shrink-0 font-label-sm text-on-surface-variant', `${data.waktu} WIB`));
        isiKartu.append(atas);

        if (!data.siswa) return; // tolak tanpa siswa dikenal — pesan saja cukup

        // Baris siswa: avatar + metadata
        const bio = el('div', 'flex items-center gap-space-md');
        const inisial = data.siswa.nama.split(/\s+/).slice(0, 2).map((k) => k[0]?.toUpperCase() ?? '').join('');
        const av = el('div', 'relative shrink-0');
        av.append(el('span', 'inline-flex h-16 w-16 items-center justify-center rounded-xl bg-secondary-fixed font-title-md text-on-secondary-fixed', inisial));
        bio.append(av);

        const meta = el('div', 'flex min-w-0 flex-1 flex-col');
        meta.append(el('h2', 'truncate font-headline-sm text-title-md font-bold text-on-surface', data.siswa.nama));
        meta.append(el('p', 'font-label-md font-semibold text-primary', `${data.siswa.kelas}${data.siswa.jurusan ? ' • ' + data.siswa.jurusan : ''}`));
        meta.append(el('p', 'mt-0.5 font-body-sm font-mono text-on-surface-variant', `NISN: ${data.siswa.nisn}`));
        bio.append(meta);
        isiKartu.append(bio);

        // Baris karcis pulang cepat (Task 13) — tombol cetak
        if (data.karcis) {
            const karcis = el('div', 'flex items-center justify-between rounded-xl bg-secondary-fixed p-2.5');
            const info = el('div', 'flex min-w-0 items-center gap-2');
            info.append(Object.assign(el('span', 'material-symbols-outlined shrink-0 text-[20px] text-on-secondary-fixed'), { textContent: 'confirmation_number' }));
            info.append(el('span', 'truncate font-label-sm font-bold text-on-secondary-fixed', `Karcis ${data.karcis.kode}`));
            karcis.append(info);
            const tombol = el('a', 'inline-flex shrink-0 items-center gap-1 rounded-lg bg-primary-container px-space-md py-space-xs font-label-md font-bold text-on-primary-container');
            tombol.href = data.karcis.url;
            tombol.target = '_blank';
            tombol.rel = 'noopener';
            tombol.append(
                Object.assign(el('span', 'material-symbols-outlined text-[16px]'), { textContent: 'print' }),
                document.createTextNode('Cetak'),
            );
            karcis.append(tombol);
            isiKartu.append(karcis);
        }

        // Baris notifikasi ortu (gateway diantrekan — Task 14+)
        const notif = el('div', 'flex items-center justify-between rounded-xl bg-surface-container-low p-2.5');
        const kiri = el('div', 'flex min-w-0 items-center gap-2');
        kiri.append(Object.assign(el('span', 'material-symbols-outlined shrink-0 text-[20px] text-tertiary-container'), { textContent: 'mark_chat_read' }));
        kiri.append(el('span', 'truncate font-label-sm font-semibold', (data.jumlah_orang_tua ?? 0) > 0
            ? `Notifikasi ortu diantrekan (${data.jumlah_orang_tua} wali terdaftar)`
            : 'Belum ada wali terdaftar untuk notifikasi'));
        notif.append(kiri);
        isiKartu.append(notif);
    }

    /* ── Log & KPI ──────────────────────────────────────────── */
    const log = document.getElementById('log-scan');

    function tambahLog(data) {
        if (!log || !data.siswa || !data.waktu) return;
        log.querySelector('[data-log-kosong]')?.remove();

        const li = el('li', 'flex items-center gap-space-sm py-space-sm animate-pulse');
        li.append(el('span', 'inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-secondary-fixed font-label-md text-on-secondary-fixed',
            data.siswa.nama.split(/\s+/).slice(0, 2).map((k) => k[0]?.toUpperCase() ?? '').join('')));

        const meta = el('div', 'min-w-0 flex-1');
        meta.append(el('p', 'truncate font-label-md font-bold text-on-surface', data.siswa.nama));
        meta.append(el('p', 'truncate font-body-sm text-body-sm text-on-surface-variant', data.siswa.kelas));
        li.append(meta);

        li.append(el('span', 'shrink-0 font-label-sm text-on-surface-variant', data.waktu.slice(0, 5)));
        li.append(el('span', 'inline-flex shrink-0 items-center gap-1 rounded-full bg-surface-container px-2 py-0.5 font-label-sm uppercase',
            data.status_label || ''));

        log.prepend(li);
        while (log.children.length > 10) log.lastElementChild.remove();
        requestAnimationFrame(() => li.classList.remove('animate-pulse'));
    }

    // data-kpi-tepat/terlambat/total dipakai lintas mode:
    // gerbang = Tepat/Terlambat/Total Hadir · pulang = Sudah Pulang/Pulang Cepat/Belum Absen
    function naikkanKpi(status) {
        const ambil = (nama) => document.querySelector(`[data-kpi-${nama}]`);
        const ubah = (node, delta) => {
            if (!node) return;
            node.textContent = String(Math.max(0, (parseInt(node.textContent, 10) || 0) + delta));
        };
        const tept = ambil('tepat');
        const telat = ambil('terlambat');
        const total = ambil('total');

        if (mode === 'pulang') {
            if (status === 'pulang') { ubah(tept, 1); ubah(total, -1); }
            else if (status === 'pulang_cepat') { ubah(tept, 1); ubah(telat, 1); ubah(total, -1); }
        } else if (status === 'hadir_tepat_waktu') { ubah(tept, 1); ubah(total, 1); }
        else if (status === 'terlambat') { ubah(telat, 1); ubah(total, 1); }
    }

    /* ── Input manual & karcis pulang cepat ─────────────────── */
    function pasangTombol(idTombol, idForm, idTutup) {
        const tombol = document.getElementById(idTombol);
        const form = document.getElementById(idForm);
        if (!tombol || !form) return;

        tombol.addEventListener('click', () => {
            const buka = form.hasAttribute('hidden');
            form.toggleAttribute('hidden', !buka);
            tombol.dataset.expanded = buka ? '1' : '0';
            if (buka) form.querySelector('[name="nisn"]')?.focus();
        });
        document.getElementById(idTutup)?.addEventListener('click', () => form.setAttribute('hidden', ''));
    }

    pasangTombol('btn-manual', 'form-manual', 'btn-tutup-manual');
    pasangTombol('btn-karcis', 'form-karcis', 'btn-tutup-karcis');

    document.getElementById('form-manual')?.addEventListener('submit', (e) => {
        e.preventDefault();
        const form = e.currentTarget;
        const nisn = form.querySelector('[name="nisn"]').value.trim();
        if (!nisn) return;
        proses(nisn, true, null, false);
        form.reset();
    });

    document.getElementById('form-karcis')?.addEventListener('submit', (e) => {
        e.preventDefault();
        const form = e.currentTarget;
        const nisn = form.querySelector('[name="nisn"]').value.trim();
        const keterangan = form.querySelector('[name="keterangan"]').value.trim();
        if (!nisn) return;
        proses(nisn, true, keterangan || null, true);
        form.reset();
    });

    /* ── Countdown jendela (gerbang: batas→tutup · pulang: buka→tutup) ── */
    const teksCountdown = document.querySelector('[data-countdown-teks]');

    function waktuServer(teks) {
        const [h = 0, m = 0, s = 0] = (teks || '0:0:0').split(':').map(Number);
        return { h, m, s };
    }

    function detikHariIni({ h, m, s }) { return h * 3600 + m * 60 + s; }
    function keDetik(jam) { const [h, m] = jam.split(':').map(Number); return h * 3600 + m * 60; }
    function durasi(total) {
        const menit = Math.max(0, Math.round(total / 60));
        if (menit >= 60) return `${Math.floor(menit / 60)} jam ${menit % 60} mnt`;
        return `${menit} mnt`;
    }

    const offset = detikServer - detikHariIni(waktuServer(new Date().toTimeString().slice(0, 8)));

    function tik() {
        if (!teksCountdown) return;
        const kini = (detikHariIni(waktuServer(new Date().toTimeString().slice(0, 8))) + offset + 86400) % 86400;

        if (targetAwal && kini < keDetik(targetAwal)) {
            teksCountdown.textContent = mode === 'pulang'
                ? `Jendela buka dalam ${durasi(keDetik(targetAwal) - kini)}`
                : `Sisa ${durasi(keDetik(targetAwal) - kini)} tepat waktu`;
        } else if (targetAkhir && kini <= keDetik(targetAkhir)) {
            teksCountdown.textContent = `Menuju penutupan ${durasi(keDetik(targetAkhir) - kini)}`;
        } else {
            teksCountdown.textContent = mode === 'pulang' ? 'Jendela pulang ditutup' : 'Gerbang ditutup';
        }
    }

    if (boleh && teksCountdown) {
        tik();
        setInterval(tik, 15000);
    }
});
