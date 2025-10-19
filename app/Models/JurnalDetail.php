<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Import BelongsTo

class JurnalDetail extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'jurnal_id',
        'chart_of_account_id',
        'debit',
        'kredit',
    ];

    /**
     * Mendapatkan Jurnal (induk) yang memiliki detail ini.
     */
    public function jurnal(): BelongsTo
    {
        return $this->belongsTo(Jurnal::class);
    }

    /**
     * Mendapatkan Akun (COA) yang terkait dengan detail ini.
     */
    public function account(): BelongsTo
    {
        // Kita beritahu nama foreign key-nya 'chart_of_account_id'
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }
}