@extends('layouts.app')

@section('title', 'Konfigurasi Formulir — ' . $event->name)

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-5">
        <a href="{{ route('events.index') }}" class="text-xs font-semibold text-blue-700 hover:underline">Kembali ke Manajemen Acara</a>
        <h1 class="text-xl font-bold text-slate-900 mt-1">Konfigurasi Formulir Pendaftaran</h1>
        <p class="text-xs text-slate-500">Acara: <strong>{{ $event->name }}</strong> (Maksimal {{ $maxFields }} kolom tambahan)</p>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-5 text-xs text-blue-900 leading-relaxed">
        Kolom <strong>Nama Lengkap</strong>, <strong>Nomor Telepon/WhatsApp</strong>, dan <strong>Alamat Email</strong> sudah tersedia secara bawaan. Anda dapat menambahkan kolom tambahan sesuai kebutuhan acara di bawah ini.
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6">
        <form action="{{ route('events.form-fields.update', $event) }}" method="POST" id="formBuilder">
            @csrf
            @method('PUT')

            <div id="fieldsContainer" class="space-y-3.5">
                @forelse($fields as $idx => $field)
                    <div class="field-item rounded-xl border border-slate-200 p-4 bg-slate-50/60">
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-start">
                            <div class="sm:col-span-5">
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nama Kolom / Pertanyaan *</label>
                                <input type="text" name="fields[{{ $idx }}][label]" value="{{ $field['label'] }}" required
                                       placeholder="Contoh: Asal Instansi"
                                       class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs">
                            </div>

                            <div class="sm:col-span-3">
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Tipe Input</label>
                                <select name="fields[{{ $idx }}][type]" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs">
                                    <option value="text" {{ ($field['type'] ?? '') === 'text' ? 'selected' : '' }}>Teks Singkat</option>
                                    <option value="number" {{ ($field['type'] ?? '') === 'number' ? 'selected' : '' }}>Angka</option>
                                    <option value="select" {{ ($field['type'] ?? '') === 'select' ? 'selected' : '' }}>Pilihan Dropdown</option>
                                    <option value="textarea" {{ ($field['type'] ?? '') === 'textarea' ? 'selected' : '' }}>Teks Panjang</option>
                                </select>
                            </div>

                            <div class="sm:col-span-3">
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Opsi Dropdown (Pisah Koma)</label>
                                <input type="text" name="fields[{{ $idx }}][options_raw]"
                                       value="{{ implode(' | ', $field['options'] ?? []) }}"
                                       placeholder="Opsi 1 | Opsi 2"
                                       class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs">
                            </div>

                            <div class="sm:col-span-1 flex sm:flex-col items-center justify-between sm:justify-center gap-2 pt-1">
                                <label class="inline-flex items-center gap-1 text-[11px] text-slate-600 cursor-pointer">
                                    <input type="checkbox" name="fields[{{ $idx }}][is_required]" value="1"
                                           {{ !empty($field['is_required']) ? 'checked' : '' }}
                                           class="rounded border-slate-300 text-blue-600">
                                    <span>Wajib</span>
                                </label>
                                <button type="button" onclick="removeField(this)" class="text-rose-600 hover:text-rose-800 text-xs font-semibold">
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                @endforelse
            </div>

            <div class="mt-4 flex items-center justify-between">
                <button type="button" id="btnAddField" onclick="addField()"
                        class="px-4 py-2 rounded-xl border border-dashed border-blue-400 text-blue-700 bg-blue-50/50 hover:bg-blue-50 text-xs font-semibold">
                    Tambah Kolom Baru
                </button>
                <span id="fieldCounter" class="text-xs text-slate-500"></span>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-200 flex items-center justify-end gap-2.5">
                <a href="{{ route('events.index') }}" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">
                    Kembali
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-xs font-semibold">
                    Simpan Konfigurasi
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const MAX_FIELDS = {{ $maxFields }};
    let fieldIndex = {{ count($fields) }};

    function updateCounter() {
        const currentCount = document.querySelectorAll('.field-item').length;
        document.getElementById('fieldCounter').textContent = `${currentCount} dari ${MAX_FIELDS} kolom digunakan`;
        document.getElementById('btnAddField').disabled = currentCount >= MAX_FIELDS;
        document.getElementById('btnAddField').classList.toggle('opacity-50', currentCount >= MAX_FIELDS);
    }

    function addField() {
        const container = document.getElementById('fieldsContainer');
        if (container.querySelectorAll('.field-item').length >= MAX_FIELDS) {
            alert(`Maksimal ${MAX_FIELDS} kolom tambahan.`);
            return;
        }
        const idx = fieldIndex++;
        const wrapper = document.createElement('div');
        wrapper.className = 'field-item rounded-xl border border-slate-200 p-4 bg-slate-50/60';
        wrapper.innerHTML = `
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-start">
                <div class="sm:col-span-5">
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nama Kolom / Pertanyaan *</label>
                    <input type="text" name="fields[${idx}][label]" required placeholder="Contoh: Asal Instansi"
                           class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs">
                </div>
                <div class="sm:col-span-3">
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Tipe Input</label>
                    <select name="fields[${idx}][type]" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs">
                        <option value="text">Teks Singkat</option>
                        <option value="number">Angka</option>
                        <option value="select">Pilihan Dropdown</option>
                        <option value="textarea">Teks Panjang</option>
                    </select>
                </div>
                <div class="sm:col-span-3">
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Opsi Dropdown</label>
                    <input type="text" name="fields[${idx}][options_raw]" placeholder="Opsi 1, Opsi 2"
                           class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs">
                </div>
                <div class="sm:col-span-1 flex sm:flex-col items-center justify-between sm:justify-center gap-2 pt-1">
                    <label class="inline-flex items-center gap-1 text-[11px] text-slate-600 cursor-pointer">
                        <input type="checkbox" name="fields[${idx}][is_required]" value="1" checked class="rounded border-slate-300 text-blue-600">
                        <span>Wajib</span>
                    </label>
                    <button type="button" onclick="removeField(this)" class="text-rose-600 hover:text-rose-800 text-xs font-semibold">Hapus</button>
                </div>
            </div>
        `;
        container.appendChild(wrapper);
        updateCounter();
    }

    function removeField(btn) {
        btn.closest('.field-item').remove();
        updateCounter();
    }

    updateCounter();
</script>
@endsection
