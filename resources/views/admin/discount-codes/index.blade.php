@extends('neutrino::admin.template.header-footer')
@section('title', 'Discount Codes | ')
@section('content')
    <div class="container">
        <div class="content">
            <div class="title-search">
                <h2>Discount Codes</h2>
            </div>

            <table class="table" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th class="text-left">Code</th>
                        <th>Use Type</th>
                        <th>$ - % - Shippping</th>
                        <th>Expiration Date</th>
                        <th>Min. Order Amount</th>
                        <th width="80" class="text-center">Edit</th>
                        <th width="60"></th>
                    </tr>
                </thead>
                <tbody>
                @foreach( $codes as $code )
                    <tr>
                        <td>
                            <a href="/admin/discount-codes/{{ $code->id }}">{{ $code->code }}</a>
                        </td>
                        <td class="text-center">{{ $code->type }}</td>
                        <td class="text-center">
                        @php
                        if( $code->amount_type === 'DOLLAR' ){
                            echo '$'.$code->amount;
                        }
                        if( $code->amount_type === 'PERCENT' ){
                            echo $code->percent.'%';
                        }
                        if( $code->amount_type === 'SHIPPING' ){
                            echo 'Free Shipping';
                        }
                        @endphp
                        </td>
                        <td class="text-center">@if($code->expires_on) {{ $code->expires_on->format('M j Y') }} @endif</td>
                        <td class="text-right">@if($code->minimum_order_amount)${{ $code->minimum_order_amount }}@endif</td>
                        <td class="text-center">
                            <a href="/admin/discount-codes/{{ $code->id }}">Edit</a>
                        </td>
                        <td>
                            <form class="delete-form" action="/admin/discount-codes/{{ $code->id }}" method="post">
                                @method('delete')
                                @csrf
                                <button type="submit" class="delete-btn">&times;</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

        </div>

        <aside class="sidebar terms-sidebar">

            <h3>{{ $edit->edit? 'Edit' : 'Create' }} Discount Code</h3>

            @if( $edit->edit )
            <form action="" method="post">
                @method('put')
            @else
            <form action="" method="post">
            @endif
                @csrf

                <div class="form-row">
                    <label class="label-col" for="code">Discount Code</label>
                    <div class="input-col">
                        <input id="code" type="text" name="code" value="{{ old('code', $edit->code) }}">
                    </div>
                    <span class="note">Leave empty to auto-generate a code.</span>
                </div>

                <div class="form-row">
                    <label class="label-col" for="type">Code Use Type</label>
                    <div class="input-col">
                        <div class="select-wrapper">
                            <select id="type" name="type" required>
                                <option value="">Choose ...</option>
                                <option value="UNLIMITED" {{ $edit->type === 'UNLIMITED'? 'selected="selected"' : ''}}>Unlimited</option>
                                <option value="ONCE_PER_CUSTOMER" {{ $edit->type === 'ONCE_PER_CUSTOMER'? 'selected="selected"' : ''}}>Once per customer</option>
                                <option value="SINGLE" {{ $edit->type === 'SINGLE'? 'selected="selected"' : ''}}>Single Use</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="label-col">Amount Type</div>
                    <div class="input-col">
                        <label><input type="radio" name="amount_type" value="DOLLAR" {{ $edit->amount_type === '' || $edit->amount_type === 'DOLLAR'? 'checked="checked"' : ''}}> <span>Dollar</span></label>
                        <label><input type="radio" name="amount_type" value="PERCENT" {{ $edit->amount_type === 'PERCENT'? 'checked="checked"' : ''}}> <span>Percent</span></label>
                        <label><input type="radio" name="amount_type" value="SHIPPING" {{ $edit->amount_type === 'SHIPPING'? 'checked="checked"' : ''}}> <span>Free Shipping</span></label>
                    </div>
                </div>

                <div id="amount-row" class="form-row @if( $edit->edit && $edit->amount_type !== 'DOLLAR' ) hide @endif">
                    <label class="label-col" for="amount">Discount Amount</label>
                    <div class="input-col input-col-group">
                        $<input id="amount" type="number" name="amount" value="{{ old('amount', $edit->amount) }}">
                    </div>
                </div>

                <div id="percent-row" class="form-row hide">
                    <label class="label-col" for="percent">Discount Percent</label>
                    <div class="input-col input-col-group">
                        <input id="percent" type="number" name="percent" value="{{ old('percent', $edit->percent) }}">%
                    </div>
                </div>

                <div class="form-row">
                    <label class="label-col" for="min">Minimum Order Amount</label>
                    <div class="input-col">
                        <input id="min" type="number" name="minimum_order_amount" value="{{ old('minimum_order_amount', $edit->minimum_order_amount) }}">
                    </div>
                    <span class="note">Set minimum order amount for the discount code. Optional.</span>
                </div>

                <div class="form-row">
                    <label class="label-col" for="expires">Expiration Date</label>
                    <div class="input-col">
                        <input id="expires" type="text" class="expires-date" readonly name="expires_on" value="@if($edit->edit && $edit->expires_on) {{ $edit->expires_on->format('D M d Y') }}  @else {{ old('expires_on', $edit->expires_on ) }} @endif">
                    </div>
                    <span class="note">Set an expiration date for the discount code. Optional. <a href="#" data-date-picker="expires" class="clear-datetime-picker">Clear date</a></span>
                </div>

                <div class="form-row">
                    <label class="label-col" for="notes">Public Notes</label>
                    <div class="input-col">
                        <textarea id="notes" class="small-editor" name="notes" style="height: 180px;">{{ old('notes', html_entity_decode($edit->notes) ) }}</textarea>
                    </div>
                    <span class="note">These notes will display to customer.</span>
                </div>

                <div class="form-row">
                    <label class="label-col" for="internal-notes">Internal Notes</label>
                    <div class="input-col">
                        <textarea id="internal-notes" name="internal_notes" style="height: 180px;">{{ old('internal_notes', $edit->internal_notes) }}</textarea>
                    </div>
                    <span class="note">These notes will display to you.</span>
                </div>

                <p>
                <button type="submit" class="btn full">{{ $edit->edit? 'Save' : 'Create' }} Discount Code</button>
                </p>

                @if( $edit->edit )
                <div class="text-center">
                    <a href="/admin/discount-codes">Clear form to add new discount code</a>
                </div>
                @endif

            </form>
        </aside>

</div>
@endsection

@section('js')
<script>
window.editorStyles = <?php echo json_encode(config('neutrino.editor_styles')) ?>;
window.editorCss = '<?php echo getEditorCss(); ?>';
window.blocks = <?php echo getBlocks() ?>;
window.currentBlocks = [];
</script>
@endsection
