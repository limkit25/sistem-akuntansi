<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // Import HasMany

class Jurnal extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tanggal_transaksi',
        'nomor_bukti',
        'deskripsi',
    ];

    /**
     * Mendapatkan semua detail (baris debit/kredit) untuk Jurnal ini.
     */
    public function details(): HasMany
    {
        return $this->hasMany(JurnalDetail::class);
    }
}