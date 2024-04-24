<form action="" method="get" id="formCariProduk">
    <div class="input-group">
        <input type="text" class="form-control" placeholder="Nama Produk" id="searchProduk" >
            <div class="input-group-append">
                <button type="submit" class="btn btn-primary" >
                    Cari
                </button>
            </div>
    </div>
</form>
<table class="table table-sm mt-3">
    <thead>
        <tr>
            <th colspan="2" class="border-0">Hasil Pencarian :</th>
        </tr>
    </thead>
    <tbody id="resultProduk"></tbody>
</table>
@push('scripts')
    <script>
          function addItem(kode_produk) {
                $.ajax({
                    type: "POST",
                    url: "/cart",
                    data: {
                        kode_produk: kode_produk
                    },
                    dataType: "json",
                    success: function(response) {
                        fetchCart();
                    }
                });
            }
        function fetchCart() {
            $.getJSON("/cart", function(response) {
            });
        }

        $(function() {
            fetchCart();

            $('#formCariProduk').submit(function(e) {
                e.preventDefault();
                const search = $('#searchProduk').val().trim();

                if (search.length >= 3) {
                    fetchCariProduk(search);
                }
            });

            function fetchCariProduk(search) {
                $.getJSON("/transaksi/produk", {
                        search: search
                    },
                    function(response) {
                        $('#resultProduk').html('');

                        response.forEach(item => {
                            addResultProduk(item);
                        });
                    }
                );
            }

            function addResultProduk(item) {
                const {
                    nama_produk,
                    kode_produk
                } = item;
                const btn = $('<button>').addClass('btn btn-xs btn-success').text('Add').on('click', function() {
                    addItem(kode_produk);
                });
                const row = $('<tr>').append($('<td>').text(nama_produk)).append($('<td>').addClass('text-right')
                    .append(btn));
                $('#resultProduk').append(row);
            }


        });
    </script>
@endpush
