<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Guest;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class ReportService
{
    /**
     * Menyusun ringkasan analitik kehadiran, distribusi per jam, dan progres kategori (FR-13 & FR-14)
     */
    public function summary(Event $event, string $statusFilter = 'all', string $search = ''): array
    {
        $allGuests = Guest::where('event_id', $event->id)
            ->with('attendance.scanner:id,name')
            ->orderBy('name')
            ->get();

        $totalGuests = $allGuests->count();
        $checkedInGuests = $allGuests->filter(fn (Guest $g) => $g->attendance !== null);
        $checkedInCount = $checkedInGuests->count();
        $pendingCount = max(0, $totalGuests - $checkedInCount);
        $attendanceRate = $totalGuests > 0 ? round(($checkedInCount / $totalGuests) * 100, 1) : 0.0;

        // 1. Progres per Kategori (Mockup Dashboard Admin SDD Hal. 79)
        $categoryStats = [];
        foreach ($allGuests as $guest) {
            $cat = $guest->custom_data['kategori_tamu']
                ?? $guest->custom_data['kategori']
                ?? $guest->custom_data['angkatan']
                ?? ucfirst($guest->source);
            if (!isset($categoryStats[$cat])) {
                $categoryStats[$cat] = ['label' => $cat, 'total' => 0, 'checked_in' => 0];
            }
            $categoryStats[$cat]['total']++;
            if ($guest->attendance) {
                $categoryStats[$cat]['checked_in']++;
            }
        }

        foreach ($categoryStats as &$cStat) {
            $cStat['rate'] = $cStat['total'] > 0
                ? round(($cStat['checked_in'] / $cStat['total']) * 100, 1)
                : 0;
        }
        unset($cStat);

        // 2. Distribusi Waktu Check-in per Jam (Mockup Dashboard & Laporan SDD Hal. 79 & 83)
        $hourlySlots = [
            '07:30' => 0,
            '08:00' => 0,
            '08:30' => 0,
            '09:00' => 0,
            '09:30' => 0,
            '10:00' => 0,
            '10:30' => 0,
            '11:00+' => 0,
        ];

        foreach ($checkedInGuests as $g) {
            $time = $g->attendance->checked_in_at;
            $hm = $time->format('H:i');
            if ($hm < '07:45') {
                $hourlySlots['07:30']++;
            } elseif ($hm < '08:15') {
                $hourlySlots['08:00']++;
            } elseif ($hm < '08:45') {
                $hourlySlots['08:30']++;
            } elseif ($hm < '09:15') {
                $hourlySlots['09:00']++;
            } elseif ($hm < '09:45') {
                $hourlySlots['09:30']++;
            } elseif ($hm < '10:15') {
                $hourlySlots['10:00']++;
            } elseif ($hm < '10:45') {
                $hourlySlots['10:30']++;
            } else {
                $hourlySlots['11:00+']++;
            }
        }

        $peakSlot = '09:00';
        $peakCount = 0;
        foreach ($hourlySlots as $slot => $cnt) {
            if ($cnt >= $peakCount) {
                $peakCount = $cnt;
                $peakSlot = $slot;
            }
        }

        // 3. Penyaringan daftar tamu untuk tabel/kartu Laporan (UC-13 & UC-14 Alternative Flow)
        $filteredGuests = $allGuests->filter(function (Guest $g) use ($statusFilter, $search) {
            if ($statusFilter === 'checked_in' && !$g->attendance) {
                return false;
            }
            if ($statusFilter === 'pending' && $g->attendance) {
                return false;
            }
            if ($search !== '') {
                $q = strtolower($search);
                $matchName = str_contains(strtolower($g->name), $q);
                $matchPhone = str_contains(strtolower((string) $g->phone), $q);
                $matchEmail = str_contains(strtolower((string) $g->email), $q);
                $matchTicket = str_contains(strtolower($g->ticket_code), $q);
                return $matchName || $matchPhone || $matchEmail || $matchTicket;
            }
            return true;
        })->values();

        return [
            'total_guests' => $totalGuests,
            'checked_in_count' => $checkedInCount,
            'pending_count' => $pendingCount,
            'attendance_rate' => $attendanceRate,
            'categories' => array_values($categoryStats),
            'hourly_slots' => $hourlySlots,
            'peak_slot' => $peakSlot,
            'peak_count' => $peakCount,
            'guests' => $filteredGuests,
        ];
    }

    /**
     * Menghasilkan berkas Microsoft Excel (.xlsx) asli secara native (FR-14, UC-14, NFR-17)
     */
    public function exportExcel(Event $event, string $statusFilter = 'all'): StreamedResponse
    {
        $summary = $this->summary($event, $statusFilter);
        $guests = $summary['guests'];
        $customFields = $event->form_fields ?? [];

        $filename = "Laporan_Kehadiran_{$event->registration_slug}_" . date('Ymd_His') . ".xlsx";

        return response()->streamDownload(function () use ($event, $summary, $guests, $customFields) {
            $rows = [];
            $rows[] = ['LAPORAN KEHADIRAN ACARA — SISTEM TAMUIN'];
            $rows[] = ['Nama Event', $event->name];
            $rows[] = ['Tanggal & Waktu', $event->event_date->format('d F Y') . ($event->event_time ? ' (' . $event->event_time . ')' : '')];
            $rows[] = ['Lokasi Venue', $event->location];
            $rows[] = ['Total Terdaftar', (string) $summary['total_guests'], 'Sudah Hadir', (string) $summary['checked_in_count'], 'Tingkat Kehadiran', $summary['attendance_rate'] . '%'];
            $rows[] = [];

            $headers = ['No', 'Kode Tiket', 'Nama Lengkap Tamu', 'Nomor WhatsApp/HP', 'Alamat Email', 'Sumber Registrasi', 'Status Kehadiran', 'Waktu Check-in', 'Metode Scan', 'Stasiun / Kiosk', 'Petugas Check-in'];
            foreach ($customFields as $field) {
                $headers[] = $field['label'] ?? $field['name'];
            }
            $rows[] = $headers;

            foreach ($guests as $idx => $g) {
                $att = $g->attendance;
                $row = [
                    (string) ($idx + 1),
                    $g->ticket_code,
                    $g->name,
                    $g->phone ?: '-',
                    $g->email ?: '-',
                    strtoupper($g->source),
                    $att ? 'SUDAH HADIR' : 'BELUM HADIR',
                    $att ? $att->checked_in_at->format('Y-m-d H:i:s') : '-',
                    $att ? strtoupper($att->scan_method) : '-',
                    $att ? ($att->device_id ?: 'Gate A') : '-',
                    $att && $att->scanner ? $att->scanner->name : '-',
                ];

                foreach ($customFields as $field) {
                    $key = $field['name'] ?? '';
                    $row[] = (string) ($g->custom_data[$key] ?? '-');
                }
                $rows[] = $row;
            }

            $xlsxPath = $this->buildNativeXlsxFile($rows);
            readfile($xlsxPath);
            @unlink($xlsxPath);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Membuat file .xlsx OpenXML valid menggunakan ZipArchive bawaan PHP
     */
    private function buildNativeXlsxFile(array $rows): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'tamuin_xlsx_');
        $zip = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="Laporan Kehadiran" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>');

        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="2">
        <font><sz val="11"/><name val="Calibri"/></font>
        <font><b/><sz val="11"/><name val="Calibri"/></font>
    </fonts>
    <fills count="2">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
    </fills>
    <borders count="1">
        <border><left/><right/><top/><bottom/><diagonal/></border>
    </borders>
    <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
    <cellXfs count="2">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>
    </cellXfs>
</styleSheet>');

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        foreach ($rows as $rIdx => $row) {
            $rowNum = $rIdx + 1;
            $isBoldRow = ($rowNum === 1 || $rowNum === 7);
            $sheetXml .= '<row r="' . $rowNum . '">';
            foreach ($row as $cIdx => $val) {
                $colLetter = $this->colLetter($cIdx);
                $cellRef = $colLetter . $rowNum;
                $escaped = htmlspecialchars((string) $val, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $styleAttr = $isBoldRow ? ' s="1"' : '';
                $sheetXml .= '<c r="' . $cellRef . '" t="inlineStr"' . $styleAttr . '><is><t>' . $escaped . '</t></is></c>';
            }
            $sheetXml .= '</row>';
        }

        $sheetXml .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        return $tmpFile;
    }

    private function colLetter(int $index): string
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr($index % 26 + 65) . $letter;
            $index = intdiv($index, 26) - 1;
        }
        return $letter;
    }
}
