<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth;
use Illuminate\Http\Request;
use App\Models\DetilPenjualan;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\DB;
use Jackiedo\Cart\Facades\Cart;

class TransaksiController extends Controller
{


    public function index(Request $request)
    {
        $search = $request->search;

        $penjualans = Penjualan::join('users', 'users.id', 'penjualans.user_id')
            ->leftJoin('pelanggans', 'pelanggans.id', 'penjualans.pelanggan_id')
            ->select('penjualans.*', 'users.nama as nama_kasir', 'pelanggans.nama as nama_pelanggan')
            ->orderBy('id', 'desc')
            ->when($search, function ($q) use ($search) {
                return $q->where('nomor_transaksi', 'like', "%{$search}%");
            })
            ->paginate();

        if ($search) $penjualans->appends(['search' => $search]);

        return view('transaksi.index', [
            'penjualans' => $penjualans
        ]);
    }


    public function create()
    {
        return view('transaksi.create', [
            'nama_kasir' => FacadesAuth::user()->nama,
            'tanggal' => date('d F Y'),
        ]);


    }

    public function store(Request $request)
    {
        $request->validate([
            'cash' => ['required', 'numeric', 'gte:total_bayar'],
            'pelanggan_id' => ['nullable', 'exists:pelanggans,id'],
        ]);
        if ($request->pelanggan_id) {
        $pelanggan = Pelanggan::find($request->pelanggan_id);
        $pelanggan->increment('poin', 1);
    }

        try {
            \DB::beginTransaction();

            $user = $request->user();
            $lastPenjualan = Penjualan::orderBy('id', 'desc')->first();
            $cart = Cart::name($user->id);
            $cartDetails = $cart->getDetails();
            $subtotal = $cartDetails->get('subtotal');
            $pajak = $cartDetails->get('tax_amount');

            $diskon = $subtotal > 100000 ? $subtotal * 0.05 : 0;

            $totalSetelahDiskon = $subtotal + $pajak - $diskon;
            $kembalian = $request->cash - $totalSetelahDiskon;
            $no = $lastPenjualan ? $lastPenjualan->id + 1 : 1;
            $nomor_transaksi = sprintf("%04d", $no);

            $allItems = $cartDetails->get('items');

            $totalBelanja = $subtotal + $pajak;

            $penjualan = Penjualan::create([
                'user_id' => $user->id,
                'pelanggan_id' => $cart->getExtraInfo('pelanggan.id'),
                'nomor_transaksi' => date('Ymd').$no,
                'tanggal' => date('Y-m-d H:i:s'),
                'total' => $totalSetelahDiskon,
                'tunai' => $request->cash,
                'kembalian' => $kembalian,
                'pajak' => $cartDetails->get('tax_amount'),
                'subtotal' => $cartDetails->get('subtotal'),
                'diskon' => $diskon,
            ]);

            foreach ($allItems as $item) {
                $produk = Produk::find($item->id);
                if ($produk->stok < $item->quantity) {
                    \DB::rollback();
                    return redirect()->back()->with('error', 'Transaksi ' . $produk->nama_produk . ' tidak tersedia atau stok kosong.');
                }
                $produk->update([
                    'stok' => $produk->stok - $item->quantity
                ]);

                DetilPenjualan::create([
                    'penjualan_id' => $penjualan->id,
                    'produk_id' => $item->id,
                    'jumlah' => $item->quantity,
                    'harga_produk' => $item->price,
                    'subtotal' => $item->subtotal,
                ]);
            }

            $cart->destroy();

            \DB::commit();

            return redirect()->route('transaksi.show', ['transaksi' => $penjualan->id]);
        } catch (\Exception $e) {
            \DB::rollback();
            return redirect()->back()->with('error', 'Terjadi kesalahan saat membuat transaksi.');
        }
    }



    public function show(Request $request, Penjualan $transaksi)
{
    $pelanggan = Pelanggan::find($transaksi->pelanggan_id);
    $user = User::find($transaksi->user_id);
    $detilPenjualan = DetilPenjualan::join('produks', 'produks.id', 'detil_penjualans.produk_id')
        ->select('detil_penjualans.*', 'nama_produk')
        ->where('penjualan_id', $transaksi->id)->get();

    // Menambahkan informasi poin pelanggan
    $nilaiPoinSebelumnya = 0;
    $nilaiPoinSetelahnya = 0;
    if ($pelanggan) {
        $nilaiPoinSebelumnya = $pelanggan->poin - 1;
        $nilaiPoinSetelahnya = $pelanggan->poin;
    }

    return view('transaksi.invoice', [
        'penjualan' => $transaksi,
        'pelanggan' => $pelanggan ? $pelanggan : null,
        'user' => $user,
        'detilPenjualan' => $detilPenjualan,
        'nilaiPoinSebelumnya' => $nilaiPoinSebelumnya,
        'nilaiPoinSetelahnya' => $nilaiPoinSetelahnya,
    ]);
}


    public function destroy(Request $request, Penjualan $transaksi)
    {
        $detilPenjualan = DetilPenjualan::query()->where('penjualan_id', $transaksi->id)->get();
        foreach ($detilPenjualan as $detail) {
            $produk = Produk::find($detail->produk_id);
            $newproduk = $produk->stok + $detail->jumlah;

            $produk->update([
                'stok' => $newproduk,
            ]);
        }

        $transaksi->update([
            'status' => 'batal'
        ]);

        return back()->with('destroy', 'success');
    }

    public function produk(Request $request)
    {
        $search = $request->search;
        $produks = Produk::select('id', 'kode_produk', 'nama_produk')
            ->when($search, function ($q) use ($search) {
                return $q->where('nama_produk', 'like', "%{$search}%");
            })
            ->orderBy('nama_produk')
            ->take(15)
            ->get();

        return response()->json($produks);
    }

    public function pelanggan(Request $request)
    {
        $search = $request->search;
        $pelanggans = Pelanggan::select('id', 'nama')
            ->when($search, function ($q) use ($search) {
                return $q->where('nama', 'like', "%{$search}%");
            })
            ->orderBy('nama')
            ->take(15)
            ->get();

        return response()->json($pelanggans);
    }

    public function addPelanggan(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:pelanggans']
        ]);
        $pelanggan = Pelanggan::find($request->id);

        $cart = Cart::name($request->user()->id);

        $cart->setExtraInfo([
            'pelanggan' => [
                'id' => $pelanggan->id,
                'nama' => $pelanggan->nama,
            ]
        ]);

        return response()->json(['message' => 'Berhasil.']);
    }

    public function cetak(Penjualan $transaksi)
    {
        $pelanggan = Pelanggan::find($transaksi->pelanggan_id);
        $user = User::find($transaksi->user_id);
        $detilPenjualan = DetilPenjualan::join('produks', 'produks.id', 'detil_penjualans.produk_id')
            ->select('detil_penjualans.*', 'nama_produk')
            ->where('penjualan_id', $transaksi->id)->get();

        return view('transaksi.cetak', [
            'penjualan' => $transaksi,
            'pelanggan' => $pelanggan,
            'user' => $user,
            'detilPenjualan' => $detilPenjualan
        ]);
    }
}
