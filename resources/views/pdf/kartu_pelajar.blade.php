<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Kartu Pelajar</title>
    <style>
        /* Layout memakai blok/inline-block + tabel — dompdf tidak mendukung flexbox */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9pt;
            color: #1c1b1f;
            margin: 0;
        }

        .kartu {
            display: inline-block;
            vertical-align: top;
            width: 88mm;
            height: 56mm;
            border: 0.6pt solid #c7c7cc;
            border-radius: 3mm;
            margin: 0 2.5mm 3mm 0;
            overflow: hidden;
            page-break-inside: avoid;
        }

        .kop {
            background: #1d4ed8;
            color: #ffffff;
            padding: 2mm 3mm;
        }

        .kop .nama-sekolah {
            font-size: 8.5pt;
            font-weight: bold;
            letter-spacing: 0.4pt;
        }

        .kop .judul {
            font-size: 6.5pt;
            text-transform: uppercase;
        }

        .isi {
            width: 100%;
            border-collapse: collapse;
            padding: 0;
        }

        .isi td {
            vertical-align: top;
            padding: 2.5mm 0 0 3mm;
        }

        .isi td.gambar {
            width: 23mm;
            padding-left: 3mm;
        }

        .qr {
            width: 19mm;
            height: 19mm;
        }

        .nama {
            font-size: 10pt;
            font-weight: bold;
            margin: 0 0 1mm 0;
        }

        .rincian {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
        }

        .rincian td {
            padding: 0.4mm 0;
            vertical-align: top;
        }

        .rincian td.label {
            width: 13mm;
            color: #49454f;
        }

        .kode {
            font-family: Courier, monospace;
            font-size: 7pt;
            color: #49454f;
            margin: 1mm 0 0 0;
        }

        .footer {
            border-top: 0.4pt solid #e2e2e8;
            padding: 1mm 3mm;
            font-size: 6pt;
            color: #49454f;
        }
    </style>
</head>

<body>
    @foreach ($kartu as $item)
        @php($siswa = $item['siswa'])
        <div class="kartu">
            <div class="kop">
                <div class="nama-sekolah">{{ strtoupper($sekolah) }}</div>
                <div class="judul">Kartu Pelajar &middot; Sistem Presensi Elektronik</div>
            </div>
            <table class="isi">
                <tr>
                    <td class="gambar">
                        <img class="qr" src="{{ $item['qr'] }}" alt="QR {{ $siswa->nisn }}">
                    </td>
                    <td>
                        <p class="nama">{{ $siswa->nama }}</p>
                        <table class="rincian">
                            <tr>
                                <td class="label">NISN</td>
                                <td>{{ $siswa->nisn }}</td>
                            </tr>
                            <tr>
                                <td class="label">NIS</td>
                                <td>{{ $siswa->nis }}</td>
                            </tr>
                            <tr>
                                <td class="label">Kelas</td>
                                <td>{{ $siswa->kelasAktif?->nama_kelas ?? '—' }}</td>
                            </tr>
                        </table>
                        <p class="kode">{{ $siswa->qr_code }}</p>
                    </td>
                </tr>
            </table>
            <div class="footer">
                Kartu ini sah sebagai identitas presensi siswa. Dilarang memindahtangankan.
            </div>
        </div>
    @endforeach
</body>

</html>
