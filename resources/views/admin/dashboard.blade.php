@extends('neutrino::admin.template.header-footer')
@section('title', 'Shoppe Dashboard | ')
@section('content')
    <div class="container dashboard">
        <div class="content full">
            <div class="title-search">
                <h2>Shoppe Dashboard</h2>
            </div>

            <div class="dashboard-cols small">
                <div class="dashboard-col">
                    <div class="dashboard-card">
                        <div class="d-card-body small">
                            <div class="small-icon">
                                <i class="fal fa-dollar-sign"></i>
                            </div>
                            <div class="small-stat">
                                <div class="open-comments">
                                    Sales Today
                                    <strong>${{ formatCurrency( $sales_today ) }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="d-card-footer">
                            Yesterday: <strong>${{ formatCurrency( $sales_yesterday ) }}</strong>
                        </div>
                    </div>
                </div>
                <div class="dashboard-col">
                    <div class="dashboard-card">
                        <div class="d-card-body small">
                            <div class="small-icon">
                                <i class="fal fa-box-alt"></i>
                            </div>
                            <div class="small-stat">
                                <div class="open-comments">
                                    New Orders
                                    <a href="/admin/orders"><strong>{{ $orderCount }}</strong></a>
                                </div>
                            </div>
                        </div>
                        <div class="d-card-footer">
                            Orders needing processing.
                        </div>
                    </div>
                </div>
                <div class="dashboard-col">
                    <div class="dashboard-card">
                        <div class="d-card-body small">
                            <div class="small-icon">
                                <i class="fal fa-shopping-cart"></i>
                            </div>
                            <div class="small-stat">
                                <div class="open-comments">
                                    Active Carts
                                    <strong>{{ $active_carts->count() }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="d-card-footer">
                            Past 2 weeks.
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
@endsection
