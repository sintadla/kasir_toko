<div class="card card-purple card-outline">
    <div class="card-body">
        <h3 class="m-0 text-right">Rp: <span id="totalJumlah">0</span> ,-</h3>
    </div>
</div>

@if (session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
@endif

<form action="{{ route('transaksi.store') }}" method="POST" class="card card-purple card-outline">
    @csrf
    <div class="card-body">
        <p class="text-right">
            Tanggal: {{ $tanggal }}
        </p>
        <div class="row">
            <div class="col">
                <label>Nama Pelanggan</label>
                <input type="text" id="namaPelanggan"
                    class="form-control @error('pelanggan_id') is-invalid @enderror" disabled>
                @error('pelanggan_id')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
                <input type="hidden" name="pelanggan_id" id="pelangganId">
            </div>
            <div class="col">
                <label>Nama Kasir</label>
                <input type="text" class="form-control" value="{{ $nama_kasir }}" disabled>
            </div>
        </div>
        <table class="table table-striped table-hover table-bordered st-3">
            <thead>
                <tr>
                    <th>Nama Produk</th>
                    <th>Qty</th>
                    <th>Harga</th>
                    <th>Sub Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="resultCart">
                <tr>
                    <td colspan="5" class="text-center"> Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
        <div class="row at-3">
            <div class="col-2 offset-6">
                <p>Total</p>
                <p>Pajak 10% </p>
                <p>Diskon</p>
                <p>Total Bayar</p>
            </div>
            <div class="col-4 text-right">
                <p id="subtotal">0</p>
                <p id="taxAmount">0</p>
                <p id="diskon">0</p>
                <p id="total">0</p>
            </div>
        </div>
        <div class="col-6 offset-6">
            <hr class="mt-0">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">Cash</span>
                </div>
                <input type="text" name="cash" class="form-control @error('cash') is-invalid @enderror"
                    placeholder="Jumlah Cash" value="{{ old('cash') }}">
            </div>
            <input type="hidden" name="total_bayar" id="totalBayar" />
            @error('cash')
                <div class="invalid-feedback d-block">
                    {{ $message }}
                </div>
            @enderror
        </div>
        <div class="col-12 form-inline mt-3">
            <a href="{{ route('transaksi.index') }}" class="btn btn-secondary mr-2">Ke Transaksi</a>
            <a href="{{ route('cart.clear') }}" class="btn btn-danger">Kosongkan</a>
            <button type="submit" class="btn btn-success ml-auto">
                <i class="fas fa-money-bill-wave mr-2"></i> Bayar Transaksi
            </button>
        </div>
    </div>
</form>
@push('scripts')
<script>
    $(function () {
        fetchCart();
    });

    function fetchCart() {
        $.getJSON("/cart", function (response) {
            $('#resultCart').empty();

            const {
                items,
                subtotal,
                tax_amount,
                diskon,
                total,
                extra_info
            } = response;

            const totalBayar = total;

            $('#subtotal').html(rupiah(subtotal));
            $('#taxAmount').html(rupiah(tax_amount));
            $('#diskon').html(rupiah(diskon));
            $('#total').html(rupiah(totalBayar));
            $('#totalJumlah').html(rupiah(totalBayar));
            $('#totalBayar').val(totalBayar);

            for (const property in items) {
                addRow(items[property]);
            }

            if (Array.isArray(items) && items.length === 0) {
                $('#resultCart').html('<tr><td colspan="5" class="text-center">Tidak ada data.</td></tr>');
            }

            if (extra_info && extra_info.pelanggan) {
                const {
                    id,
                    nama
                } = extra_info.pelanggan;
                $('#namaPelanggan').val(nama);
                $('#pelangganId').val(id);
            }

        });
    }

    function addRow(item) {
    const {
        hash,
        price,
        title,
        quantity,
        total_price
    } = item;

    const row = '<tr><td>' + title + '</td><td><input type="number" class="form-control quantity-input" id="qty_' + hash + '" value="' + quantity + '"></td><td class="price">' + rupiah(price) + '</td><td>' + rupiah(total_price) + '</td><td><button type="button" class="btn btn-xs btn-success mr-2" onclick="ePut(\'' + hash + '\', 1)"><i class="fa fa-plus"></i></button><button type="button" class="btn btn-xs btn-primary mr-2" onclick="ePut(\'' + hash +
        '\', -1)"><i class="fas fa-minus"></i></button><button type="button" class="btn btn-xs btn-info mr-2" onclick="updateQuantity(\'' + hash +
        '\')">Update</button><button type="button" class="btn btn-xs btn-danger" onclick="eDel(\'' + hash +
        '\')"><i class="fas fa-times"></i></button></td></tr>';

    $('#resultCart').append(row);
}


    function rupiah(number) {
        return new Intl.NumberFormat("id-ID").format(number);
    }

    function ePut(hash, qtyChange) {
    const currentQuantity = parseInt($('#qty_' + hash).val());
    const newQuantity = currentQuantity + qtyChange;
    if (newQuantity > 0) {
        $.ajax({
            type: "PUT",
            url: "/cart/" + hash,
            data: {
                qty: newQuantity
            },
            dataType: "json",
            success: function (response) {
                fetchCart();
            }
        });
    }
}


    function eDel(hash) {
        $.ajax({
            type: "DELETE",
            url: "/cart/" + hash,
            dataType: "json",
            success: function (response) {
                fetchCart();
            }
        });
    }

    function updateQuantity(hash) {
    const quantity = $('#qty_' + hash).val();
    $.ajax({
        type: "PUT",
        url: "/cart/" + hash,
        data: {
            qty: quantity
        },
        dataType: "json",
        success: function (response) {
            fetchCart();
        },
        error: function (xhr, status, error) {
            console.error(error);
        }
    });
}

</script>

@endpush
