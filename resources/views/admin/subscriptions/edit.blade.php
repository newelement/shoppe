@extends('neutrino::admin.template.header-footer')
@section('title', 'Edit Subscription Plan | ')
@section('content')
    <div class="container">
        <div class="content full">
            <div class="title-search">
                <h2>Edit Subscription</h2>
            </div>

            <p>
            <strong>Status: </strong> {{ $sub['status'] }}
            </p>

            <form action="/admin/subscriptions/{{ $sub['id'] }}" style="margin-bottom: 12px" method="post">
                @csrf
                @method('put')
                <div class="form-row">
                    <label class="label-col" for="plan-name">Plans</label>
                    <div class="input-col">
                        <div class="select-wrapper">
                            <select name="plan_id">
                                @foreach( $plans as $plan )
                                <option value="{{ $plan['id'] }}" {{ $plan['id'] === $sub['plan']['id']? 'selected="selected"' : '' }}>{{ $plan['product']['name'] }} {{ currencySymbol() }}{{ number_format($plan['amount']/100, 2, '.', '')  }} {{$plan['interval_count']}}/{{ $plan['interval'] }} active:{{ $plan['active']? 'true' : 'false' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <label class="label-col" for="trial">Trial Ends On  <small>(optional)</small></label>
                    <div class="input-col">
                        <input id="trial" type="text" class="datetime-picker" data-date-picker="1" name="trial_end" value="{{ $sub['trial_end']? \Carbon\Carbon::createFromTimestamp($sub['trial_end'])->timezone( config('neutrino.timezone') )->format('Y-m-d g:i a') : '' }}">
                    </div>
                    <span class="input-notes"><span class="note">Specify a date when trial ends. <a class="clear-datetime-picker" data-date-picker="1" href="#">Clear</a></span></span>
                </div>

                <div class="form-row">
                    <label class="label-col" for="disable-trial">Disable Trial Period  <small>(optional)</small></label>
                    <div class="input-col has-checkbox">
                        <input id="disable-trial" type="checkbox" name="disable_trial" value="1">
                    </div>
                    <span class="input-notes"><span class="note">This will override any trial peroid that is set.</span></span>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">Update Subscription</button>
                </div>

            </form>

            <form action="/admin/subscriptions/{{ $sub['id'] }}/cancel" method="post">
                @csrf
                <button type="submit" class="btn" style="background-color: #b30000; float: right">Cancel Subscription</button>
            </form>

        </div>

    </div>
@endsection
