@extends('neutrino::admin.template.header-footer')
@section('title', 'Orders | ')
@section('content')
    <div class="container">
        <div class="content full">
            <div class="title-search">
                <h2>Orders</h2>
                <div class="object-search">
                    <form class="search-form" action="{{url()->full()}}" method="get">
                        <input type="text" name="s" value="{{ request('s') }}" placeholder="Search orders" autocomplete="off">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>

            <div class="responsive-table">
                <table cellpadding="0" cellspacing="0" class="table">
                    <thead>
                        <tr>
                            <th class="text-left" width="100">ID</th>
                            <th width="100">@sortablelink('status', 'Status')</th>
                            <th>Items Total</th>
                            <th>Taxes</th>
                            <th>Shipping</th>
                            <th>Order Total</th>
                            <th>Customer</th>
                            <th>Created On</th>
                            <th class="text-center" width="120">Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach( $orders as $order )
                        <tr>
                            <td data-label="Order ID">
                                <a href="/admin/orders/{{ $order->id }}">{{ $order->id }}</a>
                            </td>
                            <td data-label="Status" class="text-center">
                                <span class="order-status-badge order-status-{{ $order->status }}">{{ $order->status_formatted }}</span>
                            </td>
                            <td data-label="Items Total" class="text-right row-order-status-{{ $order->status }}">{{ currencySymbol() }}{{ formatCurrency($order->items_total) }}</td>
                            <td data-label="Taxes" class="text-right row-order-status-{{ $order->status }}">${{ $order->tax_amount }}</td>
                            <td data-label="Shipping" class="text-right row-order-status-{{ $order->status }}">${{ $order->shipping_amount }}</td>
                            <td data-label="Order Total" class="text-right row-order-status-{{ $order->status }}">${{ number_format( getOrderTotal( $order ), 2, '.', ',' ) }}</td>
                            <td data-label="Created by" class="center">{{ $order->createdUser->name }}</td>
                            <td data-label="Created On" class="center">{{ $order->created_at->timezone( config('neutrino.timezone') )->format('M j, Y g:i a') }}</td>
                            <td data-label="Manage" class="text-center"><a href="/admin/orders/{{ $order->id }}">Manage</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pagination-links">
                {{ $orders->appends($_GET)->links() }}
            </div>
        </div>

    </div>
@endsection
