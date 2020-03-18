@extends('neutrino::admin.template.header-footer')
@section('title', 'Subscription Tax Rates | ')
@section('content')
    <div class="container">
        <div class="content">
            <div class="title-search">
                <h2>Subscription Tax Rates <a class="headline-btn" href="/admin/stripe/tax-rate" role="button">Create Tax Rate</a></h2>
            </div>

            <div class="responsive-table">
                <table cellpadding="0" cellspacing="0" class="table">
                    <thead>
                        <tr>
                            <th class="text-left">ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th class="text-center">Jurisdiction</th>
                            <th class="text-center">Inclusive</th>
                            <th class="text-center">Percentage</th>
                            <th class="text-center" width="80">Active</th>
                            <th class="text-center" width="100">Edit</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach( $rates['rates'] as $rate )
                        <tr>
                            <td data-label="Rate ID">
                                <a href="/admin/stripe/tax-rates/{{ $rate['id'] }}">{{ $rate['id'] }}</a>
                            </td>
                            <td data-label="Name">{{ $rate['display_name'] }}</td>
                            <td data-label="Description">{{ $rate['description'] }}</td>
                            <td data-label="Jurisdiction" class="text-center">{{ $rate['jurisdiction'] }}</td>
                            <td data-label="Inclusive" class="text-center">{{ $rate['inclusive']? 'Yes' : 'No' }}</td>
                            <td data-label="Percentage" class="text-center">{{ $rate['percentage'] }}</td>
                            <td data-label="Manage" class="text-center">{{ $rate['active']? 'Yes' : 'No' }}</td>
                            <td class="text-center">
                                <a href="/admin/stripe/tax-rates/{{ $rate['id'] }}">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

        </div>

    </div>
@endsection
