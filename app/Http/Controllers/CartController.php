<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pelanggan;
use App\Models\Produk;
use Jackiedo\Cart\Facades\Cart;
use Jackiedo\Cart\Cart as CartCart;
use Jackiedo\Cart\Facades\Cart as FacadesCart;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = FacadesCart::name($request->user()->id);

        $cart->applyTax([
            'id' => 1,
            'rate' => 10,
            'title' => 'Pajak PPN 10%'
        ]);

        $cartDetails = $cart->getDetails();
        $subtotal = $cartDetails->get('subtotal');
        $pajak = $cartDetails->get('tax_amount');
        $diskon = 0;
        if ($subtotal > 100000) {
            $diskon = $subtotal * 0.05;
        }
        $total = $subtotal +$pajak - $diskon;

        $cartDetails->put('diskon', $diskon);
        $cartDetails->put('total', $total);
        return $cartDetails->toJson();
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_produk' => ['required', 'exists:produks']
        ]);

        $produk = Produk::where('kode_produk', $request->kode_produk)->first();

        $cart = FacadesCart::name($request->user()->id);

        $cart->addItem([
            'id' => $produk->id,
            'title' => $produk->nama_produk,
            'quantity' => 1,
            'price' => $produk->harga
        ]);

        return response()->json(['message' => 'Berhasil Ditambahkan.']);
    }


    public function update(Request $request, $hash)
    {
    $request->validate([
        'qty' => ['required', 'numeric'],
    ]);

    $cart = Cart::name($request->user()->id);
    $item = $cart->getItem($hash);

    if (!$item) {
        return abort(404);
    }

    $newQuantity = $request->qty;

    if ($newQuantity < 0) {
        return response()->json(['message' => 'Jumlah tidak valid.'], 422);
    }

    $cart->updateItem($item->getHash(), ['quantity' => $newQuantity]);

    return response()->json(['message' => 'Berhasil diupdate.']);
    }

    public function destroy(Request $request, $hash)
    {
        $cart = FacadesCart::name($request->user()->id);
        $cart->removeItem($hash);
        return response()->json(['message' => 'Berhasil dihapus.']);
    }

    public function clear(Request $request)
    {
        $cart = FacadesCart::name($request->user()->id);
        $cart->destroy();

        return back();
    }
}
