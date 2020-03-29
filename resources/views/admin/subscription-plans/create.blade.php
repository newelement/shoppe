@extends('neutrino::admin.template.header-footer')
@section('title', 'Create Subscription Plan | ')
@section('content')
    <div class="container">
        <div class="content">
            <div class="title-search">
                <h2>Create Subscription Plan</h2>
            </div>

            <form action="/admin/subscription-plans" method="post">
                @csrf

                <div class="form-row">
                    <label class="label-col" for="name">Name</label>
                    <div class="input-col">
                        <input id="name" type="text" name="plan_name" value="{{ old('plan_name') }}" placeholder="Gold, Starter, Enterprise" required>
                    </div>
                </div>

                <div class="form-row">
                    <label class="label-col" for="amount">Amount</label>
                    <div class="input-col">
                        <input id="amount" type="number" name="amount" value="{{ old('amount') }}" placeholder="20.00" required>
                    </div>
                </div>

                <div class="form-row">
                    <label class="label-col" for="interval">Interval</small></label>
                    <div class="input-col">
                        <div class="select-wrapper">
                            <select id="interval" name="interval">
                                <option value="day">Daily</option>
                                <option value="week">Weekly</option>
                                <option value="month">Monthly</option>
                                <option value="year">Yearly</option>
                            </select>
                        </div>
                    </div>
                    <span class="input-notes"><span class="note">The billing frequency.</span></span>
                </div>

                <div class="form-row">
                    <label class="label-col" for="trial">Trial Period Days  <small>(optional)</small></label>
                    <div class="input-col">
                        <input id="trial" type="number" name="trial" value="{{ old('trial') }}">
                    </div>
                    <span class="input-notes"><span class="note">In days</span></span>
                </div>

                <button type="submit" class="btn">Create Plan</button>
            </form>

        </div>

    </div>
@endsection
