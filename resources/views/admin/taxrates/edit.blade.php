@extends('neutrino::admin.template.header-footer')
@section('title', 'Edit Subscription Tax Rate | ')
@section('content')
    <div class="container">
        <div class="content full">
            <div class="title-search">
                <h2>Edit Subscription Tax Rate <a class="headline-btn" href="/admin/stripe/tax-rate" role="button">Create Tax Rate</a></h2>
            </div>

            <form action="/admin/stripe/tax-rates/{{ $rate['id'] }}" method="post">
                @csrf
                @method('put')
                <input id="id" type="hidden" name="id" value="{{ $rate['id'] }}">

                <div class="form-row">
                    <label class="label-col" for="name">Display Name</label>
                    <div class="input-col">
                        <input id="name" type="text" name="display_name" value="{{ old('display_name', $rate['display_name']) }}" required>
                    </div>
                </div>

                <div class="form-row">
                    <label class="label-col" for="description">Description</label>
                    <div class="input-col">
                        <input id="description" type="text" name="description" value="{{ old('description', $rate['description']) }}">
                    </div>
                </div>

                <div class="form-row">
                    <label class="label-col" for="jurisdiction">Jurisdiction</label>
                    <div class="input-col">
                        <input id="jurisdiction" type="text" name="jurisdiction" value="{{ old('jurisdiction', $rate['jurisdiction']) }}">
                    </div>
                </div>

                <div class="form-row">
                    <label class="label-col" for="active">Active</label>
                    <div class="input-col has-checkbox">
                        {{ $rate['active']? 'Yes' : 'No' }}
                    </div>
                </div>

                <div class="form-row">
                    <label class="label-col" for="percent">Percentage</label>
                    <div class="input-col">
                        <input id="percent" type="number" value="{{ $rate['percentage'] }}" readonly>
                    </div>
                </div>

                <div class="form-row">
                    <label class="label-col" for="active">Active</label>
                    <div class="input-col has-checkbox">
                        <label><input id="active" type="checkbox" name="active" value="1" {{ $rate['active']? 'checked="checked"' : '' }} > Yes</label>
                    </div>
                </div>

                <button type="submit" class="btn">Update Tax Rate</button>

            </form>

        </div>

    </div>
@endsection
