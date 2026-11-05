<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class GuestImportService
{
    public function __construct(
        protected QrCodeService $qrCodeService,
        protected RegistrationService $registrationService
    ) {}

    /**
     * Baca dan validasi berkas CSV atau Excel (.xlsx) untuk preview impor massal (FR-06, UC-06, NFR-07).
     * Mengembalikan daftar baris valid dan baris bermasalah beserta keterangannya.
     */
    public function parseAndValidate(Event $event, UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['csv', 'txt', 'xlsx'], true)) {
            throw new \InvalidArgumentException('Format berkas tidak dikenali. Gunakan berkas .csv atau .xlsx.');
        }

        $rawRows = $extension === 'xlsx'
            ? $this->readXlsxRows($file->getRealPath())
            : $this->readCsvRows($file->getRealPath());

        if (count($rawRows) < 2) {
            throw new \InvalidArgumentException('Berkas impor kosong atau tidak memiliki baris data.');
        }

        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $rawRows[0]);
        $dataRows = array_slice($rawRows, 1);

        // Ambil kontak yang sudah terdaftar di Event ini untuk deteksi duplikasi cepat (O(1) lookup)
        $existingPhones = Guest::where('event_id', $event->id)
            ->whereNotNull('phone')
            ->pluck('name', 'phone')
            ->toArray();

        $existingEmails = Guest::where('event_id', $event->id)
            ->whereNotNull('email')
            ->pluck('name', 'email')
            ->toArray();

        $seenPhonesInFile = [];
        $seenEmailsInFile = [];
        $customFields = $event->form_fields ?? [];

        $validRows = [];
        $invalidRows = [];

        foreach ($dataRows as $idx => $rowValues) {
            $rowNumber = $idx + 2; // Baris ke-1 adalah header
            $assoc = [];
            foreach ($headers as $colIdx => $headerName) {
                if ($headerName !== '') {
                    $assoc[$headerName] = isset($rowValues[$colIdx]) ? trim((string) $rowValues[$colIdx]) : '';
                }
            }

            // Lewati baris yang benar-benar kosong
            if (empty(array_filter($assoc, fn ($v) => $v !== ''))) {
                continue;
            }

            $name = $assoc['nama'] ?? $assoc['name'] ?? $assoc['nama_lengkap'] ?? '';
            $rawPhone = $assoc['no_hp'] ?? $assoc['phone'] ?? $assoc['whatsapp'] ?? $assoc['nomor_whatsapp'] ?? '';
            $rawEmail = strtolower($assoc['email'] ?? $assoc['alamat_email'] ?? '');

            $errors = [];

            if ($name === '') {
                $errors[] = 'Nama tamu kosong';
            }

            // Validasi format nomor HP/WA (Mockup SDD hal. 82: karakter non-angka ditandai Format HP Salah)
            $normalizedPhone = '';
            if ($rawPhone !== '') {
                if (!preg_match('/^[0-9+\-\s()]{8,20}$/', $rawPhone) || preg_match('/[a-zA-Z]/', $rawPhone)) {
                    $errors[] = 'Format HP/WA salah (mengandung karakter non-angka atau panjang tidak valid)';
                } else {
                    $normalizedPhone = $this->registrationService->normalizePhone($rawPhone);
                }
            }

            if ($rawEmail !== '' && !filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Format email tidak valid';
            }

            // Cek duplikasi dengan database maupun sesama baris di dalam file
            if ($normalizedPhone !== '') {
                if (isset($existingPhones[$normalizedPhone])) {
                    $errors[] = "Duplikasi No. WA (sudah terdaftar a.n. {$existingPhones[$normalizedPhone]})";
                } elseif (isset($seenPhonesInFile[$normalizedPhone])) {
                    $errors[] = "Duplikasi No. WA di baris #{$seenPhonesInFile[$normalizedPhone]}";
                }
            }

            if ($rawEmail !== '') {
                if (isset($existingEmails[$rawEmail])) {
                    $errors[] = "Duplikasi Email (sudah terdaftar a.n. {$existingEmails[$rawEmail]})";
                } elseif (isset($seenEmailsInFile[$rawEmail])) {
                    $errors[] = "Duplikasi Email di baris #{$seenEmailsInFile[$rawEmail]}";
                }
            }

            // Ambil data field kustom Event
            $customData = [];
            foreach ($customFields as $field) {
                $fKey = strtolower($field['name'] ?? '');
                $fLabel = strtolower($field['label'] ?? '');
                $val = $assoc[$fKey] ?? $assoc[$fLabel] ?? '';

                if (!empty($field['is_required']) && $val === '') {
                    $errors[] = "Kolom wajib '{$field['label']}' belum diisi";
                }
                $customData[$field['name']] = $val;
            }

            $item = [
                'row_number' => $rowNumber,
                'name' => $name,
                'phone' => $normalizedPhone ?: $rawPhone,
                'email' => $rawEmail ?: null,
                'custom_data' => $customData,
                'errors' => $errors,
                'is_valid' => empty($errors),
            ];

            if (empty($errors)) {
                if ($normalizedPhone !== '') {
                    $seenPhonesInFile[$normalizedPhone] = $rowNumber;
                }
                if ($rawEmail !== '') {
                    $seenEmailsInFile[$rawEmail] = $rowNumber;
                }
                $validRows[] = $item;
            } else {
                $invalidRows[] = $item;
            }
        }

        return [
            'total_rows' => count($validRows) + count($invalidRows),
            'valid_count' => count($validRows),
            'invalid_count' => count($invalidRows),
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
        ];
    }

    /**
     * Simpan seluruh baris valid secara massal dan buat QR Code unik per tamu (NFR-07: 1.000 baris < 30 detik)
     */
    public function importValidRows(Event $event, array $validRows): int
    {
        $importedCount = 0;

        DB::transaction(function () use ($event, $validRows, &$importedCount) {
            foreach ($validRows as $row) {
                $ticketCode = $this->qrCodeService->generateUniqueTicketCode($event);

                $guest = Guest::create([
                    'event_id' => $event->id,
                    'name' => $row['name'],
                    'phone' => !empty($row['phone']) ? $this->registrationService->normalizePhone($row['phone']) : null,
                    'email' => !empty($row['email']) ? strtolower($row['email']) : null,
                    'custom_data' => $row['custom_data'] ?? [],
                    'ticket_code' => $ticketCode,
                    'qr_code' => null,
                    'source' => 'impor',
                    'registered_at' => now(),
                ]);

                $guest->update([
                    'qr_code' => $this->qrCodeService->generate($guest),
                ]);

                $importedCount++;
            }
        });

        return $importedCount;
    }

    /**
     * Baca berkas CSV dengan deteksi delimiter otomatis (koma atau titik koma dari Excel Indonesia)
     */
    private function readCsvRows(string $filePath): array
    {
        $rows = [];
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return [];
        }

        $firstLine = fgets($handle);
        rewind($handle);

        $delimiter = (substr_count((string) $firstLine, ';') > substr_count((string) $firstLine, ',')) ? ';' : ',';

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (isset($data[0])) {
                // Bersihkan UTF-8 BOM jika ada
                $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0]);
            }
            $rows[] = $data;
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Baca berkas .xlsx secara native menggunakan ZipArchive + SimpleXML bawaan PHP
     */
    private function readXlsxRows(string $filePath): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new \RuntimeException('Ekstensi PHP ZipArchive belum aktif. Gunakan format .csv atau aktifkan extension=zip di php.ini.');
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \InvalidArgumentException('Berkas .xlsx tidak dapat dibuka atau rusak.');
        }

        $sharedStrings = [];
        if (($sharedXml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $xml = simplexml_load_string($sharedXml);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $run) {
                            $text .= (string) $run->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            return [];
        }

        $xml = simplexml_load_string($sheetXml);
        if (!$xml || !isset($xml->sheetData->row)) {
            return [];
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $rowCells = [];
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                $colLetters = preg_replace('/[0-9]/', '', $ref);
                $colIndex = $this->columnIndexFromString($colLetters);

                $type = (string) $c['t'];
                $value = (string) $c->v;

                if ($type === 's' && isset($sharedStrings[(int) $value])) {
                    $value = $sharedStrings[(int) $value];
                } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                    $value = (string) $c->is->t;
                }

                $rowCells[$colIndex] = $value;
            }

            if (!empty($rowCells)) {
                $maxCol = max(array_keys($rowCells));
                $normalizedRow = [];
                for ($i = 0; $i <= $maxCol; $i++) {
                    $normalizedRow[$i] = $rowCells[$i] ?? '';
                }
                $rows[] = $normalizedRow;
            }
        }

        return $rows;
    }

    private function columnIndexFromString(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
        }
        return max(0, $index - 1);
    }
}
