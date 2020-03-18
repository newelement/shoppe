@extends('neutrino::admin.template.header-footer')
@section('title', 'Create Subscription Tax Rate | ')
@section('content')
    <div class="container">
        <div class="content">
            <div class="title-search">
                <h2>Create Subscription Tax Rate</h2>
            </div>

            <form action="/admin/stripe/tax-rates" method="post">
                @csrf

                <div class="form-row">
                    <label class="label-col" for="name">Display Name</label>
                    <div class="input-col">
                        <input id="name" type="text" name="display_name" value="{{ old('display_name') }}" required>
                    </div>
                </div>

                <div class="form-row">
                    <label class="label-col" for="description">Description</label>
                    <div class="input-col">
                        <input id="description" type="text" name="description" value="{{ old('description') }}">
                    </div>
                </div>

                <div class="form-row">
                    <label class="label-col" for="jurisdiction">Jurisdiction</label>
                    <div class="input-col">
                        <input id="jurisdiction" type="text" name="jurisdiction" value="{{ old('jurisdiction') }}">
                    </div>
                </div>

                <div class="form-row">
                    <label class="label-col" for="inclusive">Inclusive</label>
                    <div class="input-col has-checkbox">
                        <label><input id="inclusive" type="checkbox" name="inclusive" value="1"> Yes</label>
                    </div>
                </div>

                <div class="form-row">
                    <label class="label-col" for="percentage">Percentage</label>
                    <div class="input-col">
                        <input id="percentage" type="number" name="percentage" placeholder="7" value="{{ old('percentage') }}" required>
                    </div>
                </div>


                <button type="submit" class="btn">Create Tax Rate</button>
            </form>

        </div>

    </div>
@endsection
