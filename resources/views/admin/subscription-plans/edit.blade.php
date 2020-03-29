@extends('neutrino::admin.template.header-footer')
@section('title', 'Edit Subscription Plan | ')
@section('content')
    <div class="container">
        <div class="content full">
            <div class="title-search">
                <h2>Edit Subscription Plan <a class="headline-btn" href="/admin/subscription-plan" role="button">Create New Plan</a></h2>
            </div>

            <form action="/admin/subscription-plans/{{ $plan['id'] }}" method="post">
                @csrf
                @method('put')
                <input id="id" type="hidden" name="id" value="{{ $plan['id'] }}">

                <div class="form-row">
                    <label class="label-col" for="plan-name">Name</label>
                    <div class="input-col">
                        <input id="plan-name" type="text" name="plan_name" value="{{ $plan['name'] }}" required>
                    </div>
                </div>

                <div class="form-row">
                    <label class="label-col" for="trial">Trial Period Days  <small>(optional)</small></label>
                    <div class="input-col">
                        <input id="trial" type="number" name="trial" value="{{ $plan['trial'] }}">
                    </div>
                    <span class="input-notes"><span class="note">In days</span></span>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">Update Plan</button>
                </div>

            </form>

        </div>

    </div>
@endsection
