@extends('layouts.admin')

@section('title', 'Buat Jurnal Umum Baru')

{{-- Menambahkan CSS untuk Select2 --}}
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
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tanggal_transaksi">Tanggal Transaksi</label>
                                <input type="date" class="form-control" id="tanggal_transaksi" name="tanggal_transaksi" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="nomor_bukti">Nomor Bukti (Opsional)</label>
                                <input type="text" class="form-control" id="nomor_bukti" name="nomor_bukti" placeholder="Contoh: INV/10/2025/001">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="deskripsi">Deskripsi</label>
                                <input type="text" class="form-control" id="deskripsi" name="deskripsi" placeholder="Contoh: Pembelian perlengkapan" required>
                            </div>
                        </div>
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
                            {{-- Baris pertama akan ditambahkan oleh JavaScript --}}
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
                                <th>
                                    <span id="total-debit">0</span>
                                </th>
                                <th>
                                    <span id="total-kredit">0</span>
                                </th>
                                <td></td>
                            </tr>
                            <tr>
                                <th>Selisih</th>
                                <th colspan="2">
                                    <span id="total-selisih" class="text-danger">0</span>
                                </th>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>

                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Simpan Jurnal</button>
                    <a href="{{ route('jurnals.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </div>
            </form>
    </div>
</div>
@endsection

{{-- Menambahkan JS untuk Select2 dan Logika Dinamis --}}
@push('js')
<script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>

<script>
    $(function() {
        // 1. Inisialisasi Select2
        // Kita simpan daftar akun dalam variabel JS untuk digunakan nanti
        const accounts = @json($accounts);
        
        function initializeSelect2(element) {
            $(element).select2({
                theme: 'bootstrap4',
                placeholder: '-- Pilih Akun --',
                data: accounts.map(function(account) {
                    return {
                        id: account.id,
                        text: `${account.kode_akun} - ${account.nama_akun}`
                    };
                })
            }).val(null).trigger('change');
        }

        // 2. Fungsi untuk Menghitung Total
        function calculateTotals() {
            let totalDebit = 0;
            let totalKredit = 0;

            $('#journal-details-body tr').each(function() {
                let debit = parseFloat($(this).find('.input-debit').val()) || 0;
                let kredit = parseFloat($(this).find('.input-kredit').val()) || 0;
                totalDebit += debit;
                totalKredit += kredit;
            });

            // Format sebagai mata uang (Rupiah)
            let formatter = new Intl.NumberFormat('id-ID');
            $('#total-debit').text(formatter.format(totalDebit));
            $('#total-kredit').text(formatter.format(totalKredit));

            let selisih = totalDebit - totalKredit;
            $('#total-selisih').text(formatter.format(selisih));

            // Beri warna jika tidak balance
            if (selisih !== 0) {
                $('#total-selisih').removeClass('text-success').addClass('text-danger');
            } else {
                $('#total-selisih').removeClass('text-danger').addClass('text-success');
            }
        }

        // 3. Fungsi untuk Menambah Baris Baru
        let rowIndex = 0;
        function addRow() {
            let newRow = `
                <tr data-row-id="${rowIndex}">
                    <td>
                        <select class="form-control select2-akun" name="details[${rowIndex}][chart_of_account_id]" required>
                            {{-- Options akan diisi oleh initializeSelect2 --}}
                        </select>
                    </td>
                    <td>
                        <input type="number" class="form-control input-debit" name="details[${rowIndex}][debit]" value="0" min="0" step="0.01" required>
                    </td>
                    <td>
                        <input type="number" class="form-control input-kredit" name="details[${rowIndex}][kredit]" value="0" min="0" step="0.01" required>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger btn-remove-row">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            $('#journal-details-body').append(newRow);
            
            // Inisialisasi Select2 pada baris yang baru ditambahkan
            initializeSelect2(`tr[data-row-id="${rowIndex}"] .select2-akun`);
            
            rowIndex++;
        }

        // 4. Event Listeners
        
        // Tambah baris saat tombol #add-row diklik
        $('#add-row').on('click', function() {
            addRow();
        });

        // Hapus baris saat tombol .btn-remove-row diklik
        $('#journal-details-body').on('click', '.btn-remove-row', function() {
            $(this).closest('tr').remove();
            calculateTotals(); // Hitung ulang total setelah baris dihapus
        });

        // Hitung ulang total saat nilai debit/kredit berubah
        $('#journal-details-body').on('input', '.input-debit, .input-kredit', function() {
            calculateTotals();
        });

        // Validasi sebelum submit form
        $('#form-jurnal').on('submit', function(e) {
            let totalDebit = 0;
            let totalKredit = 0;

            $('#journal-details-body tr').each(function() {
                totalDebit += parseFloat($(this).find('.input-debit').val()) || 0;
                totalKredit += parseFloat($(this).find('.input-kredit').val()) || 0;
            });
            
            // Cek apakah ada baris detail
            if (totalDebit === 0 && totalKredit === 0) {
                e.preventDefault(); // Hentikan submit
                toastr.error('Jurnal harus memiliki setidaknya satu entri debit atau kredit.');
                return;
            }

            // Cek apakah balance
            if (totalDebit !== totalKredit) {
                e.preventDefault(); // Hentikan submit
                toastr.error('Jurnal tidak seimbang (unbalanced). Total Debit harus sama dengan Total Kredit.');
            }
        });

        // 5. Tambahkan dua baris pertama saat halaman dimuat
        addRow();
        addRow();
    });
</script>
@endpush