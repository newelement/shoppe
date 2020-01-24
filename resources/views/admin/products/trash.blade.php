@extends('neutrino::admin.template.header-footer')
@section('title', 'Products Trash | ')
@section('content')
    <div class="container">
        <div class="content">
            <div class="title-search">
                <h2>Products in Trash</h2>
                <div class="object-search">
                </div>
            </div>

            <table cellpadding="0" cellspacing="0" class="table">
                <thead>
                    <tr>
                        <th class="text-left">Title</th>
                        <th>Status</th>
                        <th width="90">Recover</th>
                        <th width="90">Destroy</th>
                    </tr>
                </thead>
                <tbody>
                @foreach( $products as $product )
                    <tr>
                        <td data-label="Title">
                            {{ $product->title }}
                        </td>
                        <td data-label="Status" class="text-center">
                            {{ _translateStatus($product->status) }}
                        </td>
                        <td data-label="Recover" class="text-center"><a href="/admin/products/recover/{{ $product->id }}">Recover</a></td>
                        <td data-label="Destroy" class="text-center"><a class="destroy-btn" href="/admin/products/destroy/{{ $product->id }}">Destroy</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <div class="pagination-links">
                {{ $products->appends($_GET)->links() }}
            </div>
        </div>

        <aside class="sidebar">
        </aside>

    </div>
@endsection
