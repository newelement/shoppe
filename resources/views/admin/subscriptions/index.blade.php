@extends('neutrino::admin.template.header-footer')
@section('title', 'Subscription Plans | ')
@section('content')
    <div class="container">
        <div class="content">
            <div class="title-search">
                <h2>Subscription Plans <a class="headline-btn" href="/admin/subscription" role="button">Create New Plan</a></h2>
            </div>

            <div class="responsive-table">
                <table cellpadding="0" cellspacing="0" class="table">
                    <thead>
                        <tr>
                            <th class="text-left" width="100">ID</th>
                            <th>Amount</th>
                            <th class="text-center">Interval</th>
                            <th class="text-center">Interval Count</th>
                            <th class="text-center" width="120">Edit</th>
                            <th width="80"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach( $subs as $sub )
                        <tr>
                            <td data-label="Plan ID">
                                <a href="/admin/subscriptions/{{ $sub['id'] }}">{{ $sub['id'] }}</a>
                            </td>
                            <td data-label="Amount" class="text-right">${{ $sub['amount'] }}</td>
                            <td data-label="Interval" class="text-center">{{ $sub['interval'] }}</td>
                            <td data-label="Interval Count" class="text-center">{{ $sub['interval_count'] }}</td>
                            <td data-label="Manage" class="text-center"><a href="/admin/subscriptions/{{ $sub['id'] }}">Edit</a></td>
                            <td class="text-center">
                                <form action="/admin/subscriptions/{{ $sub['id'] }}" method="post">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="delete-btn"><i class="fal fa-times"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

        </div>

    </div>
@endsection
