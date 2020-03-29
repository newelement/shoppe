@extends('neutrino::admin.template.header-footer')
@section('title', 'Subscriptions | ')
@section('content')
    <div class="container">
        <div class="content full">
            <div class="title-search">
                <h2>Subscriptions</h2>
                <div class="object-search">
                    <form class="search-form" action="{{url()->full()}}" method="get">
                        <input type="text" name="s" value="{{ request('s') }}" placeholder="Search subscriptions" autocomplete="off">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>

            <div class="responsive-table">
                <table cellpadding="0" cellspacing="0" class="table">
                    <thead>
                        <tr>
                            <th class="text-left" width="100">ID</th>
                            <th class="text-left">User</th>
                            <th class="text-left">Email</th>
                            <th class="text-center">Sub Name</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Created On</th>
                            <th class="text-center" width="120">Edit</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach( $subs as $sub )
                        <tr>
                            <td data-label="ID">
                                <a href="/admin/subscriptions/{{ $sub->stripe_id }}">{{ $sub->stripe_id }}</a>
                            </td>
                            <td data-label="Amount" class="text-left">{{ $sub->user->name }}</td>
                            <td data-label="Amount" class="text-left">{{ $sub->user->email }}</td>
                            <td data-label="Sub Name" class="text-center">{{ $sub->name }}</td>
                            <td data-label="Status" class="text-center">{{ $sub->stripe_status }}</td>
                            <td class="text-center">{{ $sub->created_at->timezone( config('neutrino.timezone') )->format('m-j-y g:i a') }}</td>
                            <td data-label="Edit" class="text-center"><a href="/admin/subscriptions/{{ $sub->stripe_id }}">Edit</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

        </div>

    </div>
@endsection
