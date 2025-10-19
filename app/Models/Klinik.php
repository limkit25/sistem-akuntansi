<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;



class Klinik extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama_klinik',
        'kode_klinik',
        'alamat',
        'telepon',
        'is_active',
    ];
    public function accounts(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class);
    }
    public function users(): HasMany
{
    return $this->hasMany(User::class);
}

public function jurnals(): HasMany
{
    return $this->hasMany(Jurnal::class);
}
}