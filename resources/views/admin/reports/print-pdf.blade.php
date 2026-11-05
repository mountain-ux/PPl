<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Kehadiran — {{ $event->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #0f172a; margin: 28px; font-size: 12px; }
        .header { border-bottom: 2px solid #1e293b; padding-bottom: 12px; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: flex-start; }
        .title { font-size: 18px; font-weight: bold; margin: 0; }
        .subtitle { font-size: 12px; color: #475569; margin-top: 4px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 18px; }
        .stat-box { border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px; background: #f8fafc; }
        .stat-label { font-size: 10px; text-transform: uppercase; color: #64748b; font-weight: bold; }
        .stat-value { font-size: 18px; font-weight: bold; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 7px 9px; text-align: left; font-size: 11px; }
        th { background: #f1f5f9; font-weight: bold; text-transform: uppercase; font-size: 10px; }
        .badge-hadir { color: #047857; font-weight: bold; }
        .badge-belum { color: #b45309; }
        .no-print { margin-bottom: 16px; }
        @media print {
            .no-print { display: none; }
            body { margin: 10mm; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print">
        <button onclick="window.print()" style="background:#2563eb;color:#fff;border:none;padding:8px 16px;border-radius:6px;font-weight:bold;cursor:pointer;">
            Cetak / Simpan sebagai PDF
        </button>
    </div>

    <div class="header">
        <div>
            <div class="title">LAPORAN AKHIR KEHADIRAN TAMU — TAMUIN</div>
            <div class="subtitle"><strong>{{ $event->name }}</strong></div>
            <div class="subtitle">Tanggal: {{ $event->event_date->format('d F Y') }} {{ $event->event_time ? '• ' . $event->event_time : '' }} | Lokasi: {{ $event->location }}</div>
        </div>
        <div style="text-align:right;font-size:11px;color:#64748b;">
            Dicetak: {{ now()->format('d/m/Y H:i') }} WITA<br>
            Filter: {{ strtoupper($statusFilter) }}
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-label">Total Tamu Terdaftar</div>
            <div class="stat-value">{{ $report['total_guests'] }} Orang</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Total Hadir (Check-in)</div>
            <div class="stat-value" style="color:#059669;">{{ $report['checked_in_count'] }} Orang</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Belum Hadir</div>
            <div class="stat-value" style="color:#d97706;">{{ $report['pending_count'] }} Orang</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Persentase Kehadiran</div>
            <div class="stat-value" style="color:#2563eb;">{{ $report['attendance_rate'] }}%</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Tiket</th>
                <th>Nama Lengkap Tamu</th>
                <th>Kontak (WA / Email)</th>
                <th>Kategori / Info</th>
                <th>Status</th>
                <th>Waktu Check-in</th>
                <th>Stasiun / Petugas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['guests'] as $idx => $g)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td style="font-family:monospace;font-weight:bold;">#{{ $g->ticket_code }}</td>
                    <td><strong>{{ $g->name }}</strong></td>
                    <td>{{ $g->phone ?: '-' }}<br><span style="color:#64748b;">{{ $g->email ?: '' }}</span></td>
                    <td>
                        @foreach($g->custom_data ?? [] as $k => $v)
                            @if($v)
                                <div><strong>{{ ucfirst(str_replace('_', ' ', $k)) }}:</strong> {{ $v }}</div>
                            @endif
                        @endforeach
                    </td>
                    <td>
                        @if($g->attendance)
                            <span class="badge-hadir">SUDAH HADIR</span>
                        @else
                            <span class="badge-belum">BELUM HADIR</span>
                        @endif
                    </td>
                    <td>{{ $g->attendance ? $g->attendance->checked_in_at->format('d/m/Y H:i:s') : '-' }}</td>
                    <td>{{ $g->attendance ? ($g->attendance->device_id ?: 'Gate A') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:20px;">Tidak ada data tamu.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
