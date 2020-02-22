@extends('neutrino::admin.template.header-footer')
@section('title', 'Shoppe Settings | ')
@section('content')
    <div class="container">
        <div class="content full">
            <div class="title-search">
                <h2>Shoppe Settings</h2>
            </div>

            <div class="responsive-table">
                <table cellpadding="0" cellspacing="0" class="table">
                    <thead>
                        <tr>
                            <th class="text-left" width="100">ID</th>

                        </tr>
                    </thead>
                    <tbody>

                        <tr>
                            <td data-label="Order ID">

                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

            <div class="pagination-links">
                {{-- $orders->appends($_GET)->links() --}}
            </div>
        </div>

    </div>
@endsection
