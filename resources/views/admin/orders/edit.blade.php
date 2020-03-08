@extends('neutrino::admin.template.header-footer')
@section('title', 'Edit Order | ')
@section('content')
    <div class="container">
        <div class="content">
            <div class="title">
                <h2><span>Order #{{ $order->id }}</span> <span class="order-status-badge order-status-{{ $order->status }}">{{ $order->status_formatted }}</span></h2>
            </div>

            <div class="order-header">

                <div class="order-header-top">
                    <div class="order-header-top-item">
                        <strong>Order Total:</strong> ${{ getOrderTotal( $order ) }}<br>
                        <small><strong>Shipping:</strong> ${{ $order->shipping_amount }}<br>
                        <strong>Sales Tax:</strong> ${{ $order->tax_amount }}</small>
                    </div>
                    <div class="order-header-top-item">
                        <strong>Ordered By:</strong> {{ $order->user->name }}<br>{{ $order->user->email }}
                        @if( $order->status > 1 )
                        <div class="resend-order-receipt-wrap">
                            <a href="#" class="resend-order-receipt">Resend order receipt</a>
                        </div>
                        @endif
                    </div>
                    <div class="order-header-top-item">
                        <strong>Order Date:</strong> {{ $order->created_at->timezone( config('neutrino.timezone') )->format('M j, Y g:i a') }}<br>
                        <small>
                        <strong>Last Updated:</strong> {{ $order->updated_at->timezone( config('neutrino.timezone') )->format('M j, Y g:i a') }}
                        </small><br>
                        <small><strong>Updated by:</strong> {{ $order->createdUser->name }}</small>
                    </div>
                </div>
                <div class="inner">
                    <div class="order-addresses">
                        <div class="order-shipping-details">
                            <h3>Shipping To</h3>
                            <address class="shipping-address">
                            {{ $order->shippingAddress->name }}<br>
                            @if( $order->shippingAddress->company_name )
                            {{ $order->shippingAddress->company_name }}<br>
                            @endif
                            {{ $order->shippingAddress->address }}<br>
                            @if( $order->shippingAddress->address2 )
                            {{ $order->shippingAddress->address2 }}<br>
                            @endif
                            {{ $order->shippingAddress->city }}, {{ $order->shippingAddress->state }} {{ $order->shippingAddress->zipcode }}<br>
                            </address>

                            @if( !$order->disabled() )

                                <h3>Tracking</h3>

                                @if( !$order->tracking_number )
                                <a href="#" class="print-shipping-label-btn view-shipping-label-link" data-order-id="{{ $order->id }}" role="button"><i class="fal fa-barcode-read"></i> Generate Shipping Label</a>
                                @endif

                                <div class="tracking-label-info">
                                    @if( $order->tracking_number )
                                        <strong>Carrier:</strong> {{ $order->carrier }} {{ $order->shipping_service }}<br>
                                        <strong>Tracking #:</strong> <a href="{{ $order->tracking_url }}" target="_blank">{{$order->tracking_number}}</a><br>
                                    @endif
                                    @if( $order->label_url )
                                        <a href="{{$order->label_url}}" class="view-shipping-label-link" target="_blank"><i class="fal fa-barcode-read"></i> View Shipping Label</a>
                                    @endif
                                </div>

                            @endif

                        </div>
                        <div class="order-payment-details">
                            <h3>Payment Details</h3>
                            <p>
                                <a href="#" class="view-payment-details-btn" data-transaction-id="{{ $order->transaction_id }}" role="button">View Payment Details</a>
                            </p>
                            <div class="payment-details">
                            </div>
                        </div>
                    </div>
                </div>
            </div>



            <ul class="form-tabs">
                <li><a href="#tab1" class="active">Items</a></li>
                <li><a href="#tab2">Transactions</a></li>
            </ul>
            <div class="tabs-content">
                <div id="tab1" class="tab-content active">
                    <div class="responsive-table">
                        <table cellpadding="0" cellspacing="0" class="table">
                            <thead>
                                <tr>
                                    <th class="text-center" width="40">#</th>
                                    <th width="60"></th>
                                    <th class="text-left">
                                        Item
                                    </th>
                                    <th>Price</th>
                                    <th>QTY</th>
                                    <th width="100">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach( $order->orderLines as $key => $line )
                                <tr>
                                    <td class="text-center">{{ $key+1 }}</td>
                                    <td>
                                    @if( $line->image )
                                        <img src="{{ $line->image }}" alt="{{ $line->product->title }}" class="mw-100">
                                    @endif
                                    </td>
                                    <td>
                                        <a href="/admin/product/{{ $line->product->id }}">{{ $line->product->title }}</a>
                                        @if( $line->variation )
                                        <br>{{ $line->variation }}
                                        @endif
                                    </td>
                                    <td class="text-right"> ${{ $line->price }} </td>
                                    <td class="text-center">{{ $line->qty }}</td>
                                    <td class="text-center">
                                    @if( !$order->disabled() && $line->status === 1 )
                                        <button type="button" data-line-id="{{ $key+1 }}" data-order-ref="{{ $order->ref_id }}" data-item-title="{{ $line->product->title }}" data-line-seq="{{ $line->id }}" data-qty="{{ $line->qty }}" data-amount="{{ $line->price }}" class="btn small refund-item-btn">Refund</button>
                                    @endif
                                    @if( $line->status === 4 )
                                        Refunded
                                    @endif
                                    </td>
                                </tr>
                                @foreach( $line->credits as $credit )
                                <tr class="tr-line-credit">
                                    <td></td>
                                    <td></td>
                                    <td>{{ $credit->notes }} </td>
                                    <td class="text-right">
                                        - ${{ $credit->amount }}<br>
                                        <small>
                                        <strong>Includes</strong>
                                        Taxes: {{ $credit->tax_amount }} /
                                        Shipping: {{ $credit->shipping_amount }}
                                        </small>
                                    </td>
                                    <td></td>
                                    <td class="text-center"></td>
                                </tr>
                                @endforeach
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="tab2" class="tab-content">

                    <div class="responsive-table">
                        <table cellpadding="0" cellspacing="0" class="table">
                            <thead>
                                <tr>
                                    <th class="text-center" width="40">#</th>
                                    <th class="text-left">ID</th>
                                    <th class="text-left">Notes</th>
                                    <th>Credit</th>
                                    <th>Debit</th>
                                    <th>User</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach( $order->transactions as $key => $trans )
                                <tr>
                                    <td class="text-center">{{ $key+1 }}</td>
                                    <td>
                                    {{ $trans->transaction_ref_id }}
                                    </td>
                                    <td class="text-left">{{ $trans->notes }}</td>
                                    <td class="text-right">
                                        @if( $trans->transaction_type === 'credit' )
                                        <span class="credit-tag">${{ $trans->amount }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if( $trans->transaction_type === 'debit' )
                                        <span class="debit-tag">${{ $trans->amount }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <small>{{ $trans->createdUser->name }}</small>
                                    </td>
                                    <td class="text-center">
                                    <small>{{ $trans->created_at->timezone( config('neutrino.timezone') )->format('M j, Y g:i a') }}</small>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

        </div>

        <aside class="sidebar">
            <div class="side-fields">
            @if( !$order->disabled() )
                <form action="/admin/orders/{{ $order->id }}/status" method="post" enctype="multipart/form-data">
                    @method('PUT')
                    @csrf
                    <div class="form-row">
                        <label class="label-col" for="status">Status</label>
                        <div class="input-col">
                            <div class="select-wrapper">
                                <select id="order-status" name="status">
                                    <option value="1" {{ $order->status === 1? 'selected="selected"' : '' }}>New</option>
                                    <option value="2" {{ $order->status === 2? 'selected="selected"' : '' }}>Hold</option>
                                    <option value="3" {{ $order->status === 3? 'selected="selected"' : '' }}>Complete</option>
                                    <option value="4" {{ $order->status === 4? 'selected="selected"' : '' }}>Refund</option>
                                    <option value="86" {{ $order->status === 86? 'selected="selected"' : '' }}>Canceled</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-row refund-order-row" style="display: none;">
                        <label class="label-col" for="refund-order">Refund Order?</label>
                        <div class="input-col">
                            <label><input type="checkbox" id="refund-order" name="refund_order" value="1"> Yes</label>
                        </div>
                    </div>

                    <div class="form-row refund-notes" style="display: none;">
                        <label class="label-col" for="refund-notes">Refund Notes</label>
                        <div class="input-col">
                            <textarea id="refund-notes" name="notes"></textarea>
                        </div>
                    </div>

                    <div class="form-row -actions">
                        <button type="submit" class="btn form-btn">Update Status</button>
                    </div>
                </form>
            @endif

                <form action="/orders/{{ $order->id }}/notes" method="post">
                    <div class="form-row">
                        <label class="label-col" for="order-note">Order Note</label>
                        <div class="input-col">
                            <textarea id="order-note" name="note"></textarea>
                        </div>
                        <div class="input-col">
                            <label><input id="order-note-public" type="checkbox" name="allow_public" value="1"> This is a customer note. (public)</label>
                        </div>
                    </div>
                    <div class="form-row form-actions">
                        <div class="order-notes-message"></div>
                        <button type="submit" id="create-note-btn" class="btn form-btn">Add Note</button>
                    </div>
                </form>

                <h3 style="margin-bottom: 12px" class="text-center">Order Notes</h3>
                @if( count($order->orderNotes) === 0 )
                <small class="text-center">There are no notes yet.</small>
                @endif

                <div class="order-notes-list">
                    <ul>
                        @foreach( $order->orderNotes as $note )
                        <li>
                            <div class="order-note-date">
                                By: {{ $note->createdUser->name }} on {{ $note->created_at->timezone(config('neutrino.timezone'))->format('M j, Y g:i a') }}
                            </div>
                            <div class="order-note {{ $note->public? 'public' : 'private' }}">
                            {!! nl2br( $note->notes ) !!}
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>

            </div>
        </aside>
    </div>

    <div class="line-refund-modal">
        <div class="inner">
            <a href="#" class="close-refund-item-modal"><i class="fal fa-times"></i></a>

            <h3 class="text-center">Item Refund</h3>

            <p class="item-refund-title" style="margin-bottom: 6px">
            </p>

            <div class="form-cols">
                <div class="form-col-5">
                    <label class="label-col" for="line-refund-amount">Amount</label>
                    <div class="input-col">
                        <input type="number" id="line-refund-amount" style="background-color: #eee; cursor: not-allowed" readonly>
                    </div>
                </div>
                <div class="form-col-2 text-center" style="padding-bottom: 18px; color: #444; font-size: 24px; display: flex; align-items: flex-end; justify-content: center">
                    <i class="fal fa-times"></i>
                </div>
                <div class="form-col-3">
                    <label class="label-col" for="line-refund-amount">Qty</label>
                    <div class="input-col">
                        <input type="number" id="line-refund-qty">
                    </div>
                </div>
            </div>

            <div class="form-cols" style="justify-content: center;">
                <div class="form-col-5">
                    <label class="label-col" for="line-refund-amount">Shipping</label>
                    <div class="input-col">
                        <input type="number" id="line-shipping-amount">
                    </div>
                </div>
            </div>

            <h4 class="text-center" style="margin-bottom: 6px">Notes</h4>
            <textarea id="line-refund-notes"></textarea>

            <div class="refund-messages" style="color: #9B2C2C"></div>
            <button type="button" class="btn full submit-refund-item-btn">Process Refund</button>

        </div>
    </div>

@endsection

@section('js')
<script>
window.orderId = {{ $order->id }};
</script>
@endsection
