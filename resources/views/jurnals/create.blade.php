@extends('layouts.admin')

@section('title', 'Buat Jurnal Umum Baru')

{{-- CSS for Select2 --}}
@push('css')
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <form action="{{ route('jurnals.store') }}" method="POST" id="form-jurnal">
            @csrf
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Formulir Jurnal Umum</h3>
                </div>
                <div class="card-body">
                    {{-- Baris Header Jurnal (Induk) --}}
                    <div class="row">
                        {{-- Kolom Tanggal (Lebar dinamis) --}}
                        <div class="{{ Auth::user()->hasRole('Superadmin') ? 'col-md-3' : 'col-md-4' }}">
                            <div class="form-group">
                                <label for="tanggal_transaksi">Tanggal Transaksi <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('tanggal_transaksi') is-invalid @enderror" id="tanggal_transaksi" name="tanggal_transaksi" value="{{ old('tanggal_transaksi', date('Y-m-d')) }}" required>
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
                                        <option value="{{ $klinik->id }}" {{ old('klinik_id') == $klinik->id ? 'selected' : '' }}>
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
                                <input type="text" class="form-control @error('nomor_bukti') is-invalid @enderror" id="nomor_bukti" name="nomor_bukti" placeholder="Contoh: INV/10/2025/001" value="{{ old('nomor_bukti') }}">
                                @error('nomor_bukti') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        {{-- Kolom Deskripsi (Lebar dinamis) --}}
                        <div class="{{ Auth::user()->hasRole('Superadmin') ? 'col-md-3' : 'col-md-4' }}">
                            <div class="form-group">
                                <label for="deskripsi">Deskripsi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('deskripsi') is-invalid @enderror" id="deskripsi" name="deskripsi" placeholder="Contoh: Pembelian perlengkapan" value="{{ old('deskripsi') }}" required>
                                @error('deskripsi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Input Hidden Klinik ID untuk Non-Superadmin --}}
                        @unlessrole('Superadmin')
                            @if(Auth::user()->klinik_id)
                                <input type="hidden" name="klinik_id" value="{{ Auth::user()->klinik_id }}">
                            @else
                                {{-- Jika tidak punya klinik_id, form tidak valid. Controller akan handle error. --}}
                                {{-- Tambahkan pesan error visual jika mau --}}
                                <div class="col-12"><div class="alert alert-danger">Akun Anda belum terhubung ke klinik.</div></div>
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
                            {{-- Rows added by JS --}}
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
                    {{-- Disable button if Staf/Admin has no clinic --}}
                    <button type="submit" class="btn btn-primary" {{ !Auth::user()->hasRole('Superadmin') && !$userKlinikId ? 'disabled' : '' }}>Simpan Jurnal</button>
                    <a href="{{ route('jurnals.index', ['klinik_id' => $userKlinikId ?? (Auth::user()->hasRole('Superadmin') ? '' : '')]) }}" class="btn btn-secondary">Batal</a>
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
    // Taruh variabel global di luar $(function) agar bisa diakses fungsi lain
    const initialAccounts = @json($accountsForView);
    let dynamicAccounts = initialAccounts; // Ini akan diupdate oleh AJAX
    let rowIndex = 0; // Counter baris global

    // Fungsi inisialisasi Select2 Akun
    function initializeSelect2(element, accountsData = initialAccounts) {
        $(element).empty();
        $(element).append(new Option('-- Pilih Akun --', '', true, true)).trigger('change');
        $(element).select2({
            theme: 'bootstrap4',
            placeholder: '-- Pilih Akun --',
            data: accountsData.map(function(account) {
                let prefix = account.klinik_id === null ? '[G] ' : '';
                return {
                    id: account.id,
                    text: `${prefix}${account.kode_akun} - ${account.nama_akun}`
                };
            })
        }).val(null).trigger('change');
    }

    // Fungsi hitung total
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

     // Fungsi tambah baris
     function addRow(detail = null) {
         let currentAccounts = dynamicAccounts; // Gunakan akun dinamis saat menambah baris
         let newRow = `
             <tr data-row-id="${rowIndex}">
                 <td><select class="form-control select2-akun" name="details[${rowIndex}][chart_of_account_id]" required></select></td>
                 <td><input type="number" class="form-control input-debit" name="details[${rowIndex}][debit]" value="${detail ? parseFloat(detail.debit).toFixed(2) : '0.00'}" min="0" step="0.01" required></td>
                 <td><input type="number" class="form-control input-kredit" name="details[${rowIndex}][kredit]" value="${detail ? parseFloat(detail.kredit).toFixed(2) : '0.00'}" min="0" step="0.01" required></td>
                 <td><button type="button" class="btn btn-sm btn-danger btn-remove-row"><i class="fas fa-trash"></i></button></td>
             </tr>
         `;
         $('#journal-details-body').append(newRow);
         let selectEl = $(`tr[data-row-id="${rowIndex}"] .select2-akun`);
         initializeSelect2(selectEl, currentAccounts); // Kirim akun saat ini
         if (detail) {
             selectEl.val(detail.chart_of_account_id).trigger('change');
         }
         rowIndex++;
     }

    // --- Kode yang berjalan saat dokumen siap ---
    $(function() {
        // Init Select2 for Klinik dropdown (jika ada)
        $('#klinik_id.select2bs4').select2({ theme: 'bootstrap4' });

        // Event Listeners
        $('#add-row').on('click', function() { addRow(); });

        $('#journal-details-body').on('click', '.btn-remove-row', function() {
            $(this).closest('tr').remove();
            calculateTotals();
        });

        $('#journal-details-body').on('input', '.input-debit, .input-kredit', function() {
            let row = $(this).closest('tr');
            let debitInput = row.find('.input-debit');
            let kreditInput = row.find('.input-kredit');
            if ($(this).hasClass('input-debit') && parseFloat($(this).val()) > 0) {
                kreditInput.val('0.00');
            } else if ($(this).hasClass('input-kredit') && parseFloat($(this).val()) > 0) {
                debitInput.val('0.00');
            }
            calculateTotals();
         });

        $('#form-jurnal').on('submit', function(e) { /* ... validasi balance ... */
            let totalDebit = 0; let totalKredit = 0; let hasDetails = false;
             $('#journal-details-body tr').each(function() {
                 hasDetails = true;
                totalDebit += parseFloat($(this).find('.input-debit').val()) || 0;
                totalKredit += parseFloat($(this).find('.input-kredit').val()) || 0;
            });
             if (!hasDetails) { e.preventDefault(); toastr.error('Jurnal harus memiliki detail.'); return; }
            if (totalDebit === 0 && totalKredit === 0) { e.preventDefault(); toastr.error('Jurnal harus ada nilai.'); return; }
            if (Math.abs(totalDebit - totalKredit) > 0.001) { e.preventDefault(); toastr.error('Jurnal tidak seimbang.'); }
         });

        // Add initial rows
        addRow();
        addRow();

        // --- AJAX UNTUK SUPERADMIN ---
        @role('Superadmin')
        $('#klinik_id').on('change', function() {
            let selectedKlinikId = $(this).val();
            let url = "{{ route('ajax.getAccountsByKlinik') }}";

            // Tambahkan klinik_id ke URL jika ada, jika tidak, route akan ambil global saja
            if (selectedKlinikId) {
                url = url + '/' + selectedKlinikId;
            }

            // Tampilkan loading
            $('.select2-akun').prop('disabled', true).empty().append(new Option('Memuat Akun...', '', true, true)).trigger('change');

            $.ajax({
                url: url,
                method: 'GET',
                success: function(data) {
                    dynamicAccounts = data; // Update akun dinamis
                    // Update semua dropdown akun yang sudah ada
                    $('.select2-akun').each(function() {
                        // Simpan value lama jika ada
                        let oldValue = $(this).val();
                        initializeSelect2(this, dynamicAccounts);
                        // Coba set value lama kembali (jika masih valid di daftar baru)
                        $(this).val(oldValue).trigger('change.select2');
                        $(this).prop('disabled', false);
                    });
                    // Reset total karena akun mungkin berubah
                    calculateTotals();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Error:", textStatus, errorThrown);
                    toastr.error('Gagal memuat daftar akun untuk klinik ini.');
                    // Kembalikan ke initialAccounts jika gagal
                    dynamicAccounts = initialAccounts;
                    $('.select2-akun').each(function() {
                        initializeSelect2(this, initialAccounts);
                        $(this).prop('disabled', false);
                    });
                     calculateTotals(); // Hitung ulang total
                }
            });
        });
        @endrole
        // --- AKHIR AJAX ---

    }); // Akhir $(function() {})
</script>
@endpush