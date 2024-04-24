<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    public function index()
{
    $produkList = Produk::all();

    return view('laporan.form', [
        'produkList' => $produkList
    ]);
}

public function harian(Request $request)
{
    $penjualan = Penjualan::join('users', 'users.id', 'penjualans.user_id')
        ->leftJoin('pelanggans', 'pelanggans.id', 'penjualans.pelanggan_id')
        ->join('detil_penjualans', 'detil_penjualans.penjualan_id', '=', 'penjualans.id')
        ->join('produks', 'produks.id', '=', 'detil_penjualans.produk_id')
        ->whereDate('penjualans.tanggal', $request->tanggal)
        ->when($request->produk, function ($query) use ($request) {
            return $query->where('produks.nama_produk', $request->produk);
        })
        ->select('penjualans.*', 'pelanggans.nama as nama_pelanggan', 'users.nama as nama_kasir')
        ->orderBy('penjualans.id')
        ->get();

    return view('laporan.harian', [
        'penjualan' => $penjualan
    ]);
}





public function bulanan(Request $request)
{
    $produkList = Produk::all();
    $penjualan = Penjualan::join('detil_penjualans', 'detil_penjualans.penjualan_id', '=', 'penjualans.id')
        ->join('produks', 'produks.id', '=', 'detil_penjualans.produk_id')
        ->select(
            DB::raw('COUNT(penjualans.id) as jumlah_transaksi'),
            DB::raw('SUM(penjualans.total) as jumlah_total'),
            DB::raw('DATE_FORMAT(penjualans.tanggal, "%d/%m/%Y") as tgl')
        )
        ->whereMonth('penjualans.tanggal', $request->bulan)
        ->whereYear('penjualans.tanggal', $request->tahun)
        ->where('penjualans.status', '!=', 'batal')
        ->when($request->produk, function ($query) use ($request) {
            return $query->where('produks.nama_produk', $request->produk);
        })
        ->groupBy('tgl')
        ->get();

    $nama_bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    $bulan = isset($nama_bulan[$request->bulan]) ? $nama_bulan[$request->bulan] : null;

    return view('laporan.bulanan', [
        'penjualan' => $penjualan,
        'bulan' => $bulan,
        'produkList' => $produkList,
    ]);
}
}
