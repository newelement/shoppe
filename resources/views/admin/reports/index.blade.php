@extends('neutrino::admin.template.header-footer')
@section('title', 'Reports | ')
@section('content')
    <div class="container">
        <div class="content full">
            <div class="title-search">
                <h2>Shoppe Reports</h2>
            </div>

            <div class="report-forms">
                <div class="sales-report-form">
                    <h4>Sales</h4>
                    <form action="/admin/shoppe/sales-report" method="get">
                        <div class="form-date-col">
                            <input type="text" class="start-date" name="start_date" readonly value="{{ old('start_date', request('start_date') ) }}">
                        </div>
                        <div class="form-to-from">
                        from &rarr; to
                        </div>
                        <div class="form-date-col">
                            <input type="text" class="end-date" name="end_date" readonly value="{{ old('end_date', request('end_date') ) }}" >
                        </div>
                        <div class="report-form-actions">
                            <button type="submit" class="btn">Generate Sales Report</button>
                        </div>
                    </form>
                </div>

                <div class="profit-report-form">
                    <h4>Profit</h4>
                    <form action="/admin/shoppe/profit-report" method="get">
                        <div class="form-date-col">
                            <input type="text" class="profit-start-date" name="profit_start_date" readonly value="{{ old('start_date', request('profit_start_date') ) }}">
                        </div>
                        <div class="form-to-from">
                        from &rarr; to
                        </div>
                        <div class="form-date-col">
                            <input type="text" class="profit-end-date" name="profit_end_date" readonly value="{{ old('end_date', request('profit_end_date') ) }}" >
                        </div>
                        <div class="report-form-actions">
                            <button type="submit" class="btn">Generate Profit Report</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="report-overview">

            @if( $report_type === 'sales' )

            <p>
                Gross: ${{ $sales['gross'] }}<br>
                Net: ${{ $sales['net'] }}<br>
                Refunds: ${{ $sales['refunds'] }}<br>
                Shipping: ${{ $sales['shipping'] }}<br>
                Taxes: ${{ $sales['taxes'] }}<br>
                <small>Shipping and tax totals include any refunded amounts.</small>
            </p>

            </div>

            @endif

            @if( $report_type === 'profit' )

            <p>
            Sales: ${{ $sales['net'] }}<br>
            Profit: ${{ $sales['profit'] }}<br>
            Cost: ${{ $sales['cost'] }}<br>
            Margin: {{ $sales['margin'] }}%<br>
            <small>Sales excludes shipping and taxes.</small>
            </p>

            @endif

            <div class="responsive-table">
                <table cellpadding="0" cellspacing="0" class="table">
                    <thead>
                        <tr>
                            <th class="text-left" width="100">Order ID</th>
                            <th width="100">@sortablelink('status', 'Status')</th>
                            <th>Items Total</th>
                            <th>Taxes</th>
                            <th>Shipping</th>
                            <th>Order Total</th>
                            <th>Refunds</th>
                            <th>Customer</th>
                            <th>Created On</th>
                            <th class="text-center" width="100">View</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach( $sales['orders'] as $order )
                        <tr>
                            <td data-label="Order ID">
                                <a href="/admin/orders/{{ $order->id }}">{{ $order->id }}</a>
                            </td>
                            <td data-label="Status" class="text-center">
                                <span class="order-status-badge order-status-{{ $order->status }}">{{ $order->status_formatted }}</span>
                            </td>
                            <td data-label="Items Total" class="text-right">${{ $order->items_total }}</td>
                            <td data-label="Taxes" class="text-right">${{ $order->tax_amount }}</td>
                            <td data-label="Shipping" class="text-right">${{ $order->shipping_amount }}</td>
                            <td data-label="Order Total" class="text-right">${{ $order->items_total + $order->tax_amount + $order->shipping_amount }}</td>
                            <td data-label="Refunds" class="text-right">${{ $order->credit_total }}</td>
                            <td data-label="Created by" class="center">{{ $order->createdUser->name }}</td>
                            <td data-label="Created On" class="center">{{ $order->created_at->timezone( config('neutrino.timezone') )->format('M j, Y g:i a') }}</td>
                            <td data-label="Manage" class="text-center"><a href="/admin/orders/{{ $order->id }}">View</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pagination-links">
                {{-- $orders->appends($_GET)->links() --}}
            </div>
        </div>

    </div>
@endsection
