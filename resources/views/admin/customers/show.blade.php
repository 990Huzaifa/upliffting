@extends('admin.layout.app')
@section('title', 'Customer Details')
@section('content')

    <div class="section-body mt-3">
        <div class="container-fluid">
            <div class="row clearfix">
                <div class="col-lg-4 col-md-12">
                    <div class="card c_grid c_yellow">
                        <div class="card-body text-center">
                            <div class="circle d-flex align-items-center justify-content-center">
                                @if($data->avatar != null)
                                    <img class="rounded-circle" src="{{ asset($data->avatar) }}" alt="">
                                @else
                                    <div class="avatar-placeholder rounded-circle d-flex align-items-center justify-content-center"
                                        style="width: 80px; height: 80px; background-color: #ccc; font-weight: bold; font-size: 24px; color: #fff;">
                                        {{ strtoupper(substr($data->first_name ?? $data->last_name, 0, 2)) }}
                                    </div>
                                @endif
                            </div>
                            <h6 class="mt-3 mb-0">{{ $data->first_name }} {{ $data->last_name }}</h6>
                            <span>{{ $data->email }}</span>
                            <ul class="mt-3 list-unstyled d-flex justify-content-center">
                            </ul>
                            <button class="btn btn-default btn-sm">{{ $data->online_status }}</button>
                            <button class="btn btn-default btn-sm">Message</button>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Customer Info</h3>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                <li class="list-group-item">
                                    <small class="text-muted">username: </small>
                                    <p class="mb-0">{{ $data->username }}</p>
                                </li>
                                <li class="list-group-item">
                                    <small class="text-muted">Phone: </small>
                                    <p class="mb-0">{{ $data->phone }}</p>
                                </li>
                                <li class="list-group-item">
                                    <small class="text-muted">Nationality: </small>
                                    <p class="mb-0">{{ $data->nationality }}</p>
                                </li>
                                <li class="list-group-item">
                                    <small class="text-muted">National ID no: </small>
                                    <p class="mb-0">{{ $data->nat_id }}</p>
                                </li>
                                <li class="list-group-item">
                                    <small class="text-muted">National ID Photo: </small>
                                    <img class="img-fluid" src="{{ asset($data->nat_id_photo) }}" alt="">
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8 col-md-12">
                    <div class="row clearfix row-deck">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Total Rides</h3>
                                </div>
                                <div class="card-body">
                                    <h5 class="number mb-0 font-32 counter">{{ $data->total_rides }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Current Rating</h3>
                                </div>
                                <div class="card-body">
                                    <h5 class="number mb-0 font-32 counter">{{$data->current_rating}}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if($data->is_approved == 'pending')
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('admin.riders.approved', [$data->user_id, 'approved'] ) }}" class="btn btn-primary mx-3">Approved</a>
                            <a href="{{ route('admin.riders.approved', [$data->user_id, 'suspended'] ) }}" class="btn btn-danger">Suspended</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection