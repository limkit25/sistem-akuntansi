@extends('layouts.admin')

@section('title', 'Edit Jurnal Umum')

{{-- CSS for Select2 --}}
@push('css')
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <form action="{{ route('jurnals.update', $jurnal->id) }}" method="POST" id="form-jurnal">
            @csrf
            @method('PUT') {{-- Method PUT for Update --}}

            <div class="card card-warning"> {{-- Changed card color --}}
                <div class="card-header">
                    <h3 class="card-title">Formulir Edit Jurnal</h3>
                </div>
                <div class="card-body">
                   {{-- Baris Header Jurnal (Induk) --}}
                    <div class="row">
                        {{-- Kolom Tanggal (Lebar dinamis) --}}
                        <div class="{{ Auth::user()->hasRole('Superadmin') ? 'col-md-3' : 'col-md-4' }}">
                            <div class="form-group">
                                <label for="tanggal_transaksi">Tanggal Transaksi <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('tanggal_transaksi') is-invalid @enderror" id="tanggal_transaksi" name="tanggal_transaksi" value="{{ old('tanggal_transaksi', $jurnal->tanggal_transaksi) }}" required>
                                @error('tanggal_transaksi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Kolom Klinik (Hanya Superadmin) --}}
                        @role('Superadmin')
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="klinik_id">Klinik <span class="text-danger">*</span></label>
                                <select class="form-control select2bs4 @error('klinik_id') is-invalid @enderror" id="klinik_id" name="klinik_id" required>
                                    <option value="">-- Pilih Klinik --</option>
                                    @foreach ($kliniks as $klinik)
                                        <option value="{{ $klinik->id }}"
                                            {{ old('klinik_id', $jurnal->klinik_id) == $klinik->id ? 'selected' : '' }}>
                                            {{ $klinik->nama_klinik }}
                                        </option>
                                    @endforeach
                                </select>
                                 @error('klinik_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        @endrole

                        {{-- Kolom Nomor Bukti (Lebar dinamis) --}}
                        <div class="{{ Auth::user()->hasRole('Superadmin') ? 'col-md-3' : 'col-md-4' }}">
                            <div class="form-group">
                                <label for="nomor_bukti">Nomor Bukti (Opsional)</label>
                                <input type="text" class="form-control @error('nomor_bukti') is-invalid @enderror" id="nomor_bukti" name="nomor_bukti" placeholder="Contoh: INV/10/2025/001" value="{{ old('nomor_bukti', $jurnal->nomor_bukti) }}">
                                @error('nomor_bukti') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        {{-- Kolom Deskripsi (Lebar dinamis) --}}
                        <div class="{{ Auth::user()->hasRole('Superadmin') ? 'col-md-3' : 'col-md-4' }}">
                            <div class="form-group">
                                <label for="deskripsi">Deskripsi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('deskripsi') is-invalid @enderror" id="deskripsi" name="deskripsi" placeholder="Contoh: Pembelian perlengkapan" value="{{ old('deskripsi', $jurnal->deskripsi) }}" required>
                                @error('deskripsi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Input Hidden Klinik ID untuk Non-Superadmin --}}
                        @unlessrole('Superadmin')
                            @if($jurnal->klinik_id)
                                <input type="hidden" name="klinik_id" value="{{ $jurnal->klinik_id }}">
                            @else
                                {{-- Fallback jika jurnal somehow tidak punya klinik_id --}}
                                <input type="hidden" name="klinik_id" value="{{ Auth::user()->klinik_id }}">
                            @endif
                        @endunlessrole
                    </div>

                    <hr>

                    {{-- Baris Detail Jurnal (Anak) --}}
                    <h5>Detail Transaksi</h5>
                    <table class="table table-bordered table-hover">
                         <thead>
                            <tr>
                                <th style="width: 40%;">Akun</th>
                                <th style="width: 25%;">Debit</th>
                                <th style="width: 25%;">Kredit</th>
                                <th style="width: 10%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="journal-details-body">
                            {{-- Rows loaded by JS --}}
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4">
                                    <button type="button" class="btn btn-sm btn-success" id="add-row">
                                        <i class="fas fa-plus"></i> Tambah Baris
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <th>Total</th>
                                <th><span id="total-debit">0</span></th>
                                <th><span id="total-kredit">0</span></th>
                                <td></td>
                            </tr>
                            <tr>
                                <th>Selisih</th>
                                <th colspan="2"><span id="total-selisih" class="text-danger">0</span></th>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>

                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning">Update Jurnal</button> {{-- Changed button text --}}
                    {{-- Go back to the correct monthly view --}}
                    <a href="{{ route('jurnals.showMonthly', ['klinik' => $jurnal->klinik_id, 'period' => \Carbon\Carbon::parse($jurnal->tanggal_transaksi)->format('Y-m')]) }}" class="btn btn-secondary">Batal</a>
                </div>
            </div>
            </form>
    </div>
</div>
@endsection

{{-- JS for Select2 and dynamic rows --}}
@push('js')
<script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
<script>
    // Variabel global di luar $(function)
    const initialAccounts = @json($accountsForView); // Akun awal (global + klinik jurnal)
    const existingDetails = @json($jurnal->details); // Detail jurnal yg ada
    let dynamicAccounts = initialAccounts; // Akun yg berlaku (bisa diubah AJAX)
    let rowIndex = 0;

    // Fungsi inisialisasi Select2 Akun
    function initializeSelect2(element, accountsData = initialAccounts) {
        $(element).empty();
        $(element).append(new Option('-- Pilih Akun --', '', true, true)).trigger('change');
        $(element).select2({
            theme: 'bootstrap4',
            placeholder: '-- Pilih Akun --',
            data: accountsData.map(function(account) {
                let prefix = account.klinik_id === null ? '[G] ' : '';
                return { id: account.id, text: `${prefix}${account.kode_akun} - ${account.nama_akun}` };
            })
        }).val(null).trigger('change');
    }

    // Fungsi hitung total
    function calculateTotals() { /* ... sama seperti create ... */ }

     // Fungsi tambah baris
     function addRow(detail = null) {
         let currentAccounts = dynamicAccounts; // Gunakan akun dinamis saat menambah baris
         let newRow = `
             <tr data-row-id="${rowIndex}">
                 <td><select class="form-control select2-akun" name="details[${rowIndex}][chart_of_account_id]" required></select></td>
                 {{-- Gunakan toFixed(2) untuk format --}}
                 <td><input type="number" class="form-control input-debit" name="details[${rowIndex}][debit]" value="${detail ? parseFloat(detail.debit).toFixed(2) : '0.00'}" min="0" step="0.01" required></td>
                 <td><input type="number" class="form-control input-kredit" name="details[${rowIndex}][kredit]" value="${detail ? parseFloat(detail.kredit).toFixed(2) : '0.00'}" min="0" step="0.01" required></td>
                 <td><button type="button" class="btn btn-sm btn-danger btn-remove-row"><i class="fas fa-trash"></i></button></td>
             </tr>
         `;
         $('#journal-details-body').append(newRow);
         let selectEl = $(`tr[data-row-id="${rowIndex}"] .select2-akun`);
         initializeSelect2(selectEl, currentAccounts); // Kirim akun saat ini
         if (detail) {
             // Set value setelah Select2 diinisialisasi
             selectEl.val(detail.chart_of_account_id).trigger('change');
         }
         rowIndex++;
     }

    // --- Kode yang berjalan saat dokumen siap ---
    $(function() {
        // Init Select2 for Klinik dropdown (jika ada)
        $('#klinik_id.select2bs4').select2({ theme: 'bootstrap4' });

        // Event Listeners (sama seperti create)
        $('#add-row').on('click', function() { addRow(); });
        $('#journal-details-body').on('click', '.btn-remove-row', function() { /* ... */ calculateTotals(); });
        $('#journal-details-body').on('input', '.input-debit, .input-kredit', function() { /* ... auto set 0 ... */ calculateTotals(); });
        $('#form-jurnal').on('submit', function(e) { /* ... validasi balance ... */ });

        // Load existing rows
        if (existingDetails && existingDetails.length > 0) {
            existingDetails.forEach(function(detail) {
                addRow(detail); // Panggil addRow WITH data
            });
        } else {
            // Tambah baris kosong jika tidak ada detail
            addRow();
            addRow();
        }

        // Calculate initial totals after loading
        calculateTotals();

        // --- AJAX UNTUK SUPERADMIN ---
        @role('Superadmin')
        $('#klinik_id').on('change', function() {
            let selectedKlinikId = $(this).val();
            let url = "{{ route('ajax.getAccountsByKlinik') }}";
            if (selectedKlinikId) { url = url + '/' + selectedKlinikId; }

            $('.select2-akun').prop('disabled', true).empty().append(new Option('Memuat Akun...', '', true, true)).trigger('change');

            $.ajax({
                url: url,
                method: 'GET',
                success: function(data) {
                    dynamicAccounts = data;
                    // Hapus semua baris detail yang ada
                    $('#journal-details-body').empty();
                    // Tambahkan 2 baris baru dengan akun yang baru
                    addRow();
                    addRow();
                    // Aktifkan kembali (seharusnya sudah otomatis dari addRow)
                    // $('.select2-akun').prop('disabled', false);
                    calculateTotals(); // Hitung ulang total
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Error:", textStatus, errorThrown);
                    toastr.error('Gagal memuat daftar akun untuk klinik ini.');
                    dynamicAccounts = initialAccounts; // Kembalikan ke akun awal
                    $('#journal-details-body').empty(); // Hapus baris
                     addRow(); // Tambah baris dengan akun awal
                     addRow();
                    // $('.select2-akun').prop('disabled', false);
                     calculateTotals();
                }
            });
        });
        @endrole
        // --- AKHIR AJAX ---

    }); // Akhir $(function() {})

    // --- PASTE FUNGSI calculateTotals (SUDAH ADA DI ATAS) dan addRow (SUDAH ADA DI ATAS) LENGKAP DI LUAR $(function() {}) DI SINI ---
     function calculateTotals() {
         let totalDebit = 0; let totalKredit = 0;
         $('#journal-details-body tr').each(function() {
             totalDebit += parseFloat($(this).find('.input-debit').val()) || 0;
             totalKredit += parseFloat($(this).find('.input-kredit').val()) || 0;
         });
         let formatter = new Intl.NumberFormat('id-ID');
         $('#total-debit').text(formatter.format(totalDebit));
         $('#total-kredit').text(formatter.format(totalKredit));
         let selisih = totalDebit - totalKredit;
         $('#total-selisih').text(formatter.format(selisih));
         if (Math.abs(selisih) > 0.001) { $('#total-selisih').removeClass('text-success').addClass('text-danger'); }
         else { $('#total-selisih').removeClass('text-danger').addClass('text-success'); }
     }
     // let rowIndex = 0; // Sudah global
     // function addRow(detail = null) { ... } // Sudah global
     // function initializeSelect2(element, accountsData = initialAccounts) { ... } // Sudah global

</script>
@endpush