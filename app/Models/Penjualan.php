<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class);
    }
    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'pelanggan_id',
        'nomor_transaksi',
        'tanggal',
        'total',
        'tunai',
        'kembalian',
        'status',
        'subtotal',
        'diskon',
        'pajak'
    ];
}
