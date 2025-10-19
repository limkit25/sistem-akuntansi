<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chart_of_accounts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'klinik_id',
        'kode_akun',
        'nama_akun',
        'tipe_akun',
        'saldo_normal',
    ];
    public function klinik(): BelongsTo
    {
        return $this->belongsTo(Klinik::class);
    }
    public function jurnalDetails(): HasMany
    {
        return $this->hasMany(JurnalDetail::class);
    }
}