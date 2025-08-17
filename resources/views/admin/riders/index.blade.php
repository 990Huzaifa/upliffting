@extends('admin.layout.app')
@section('title', 'Riders')
@section('content')

    <div class="section-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="d-flex justify-content-end mt-3 align-items-center">

                        <div class="header-action d-md-flex">
                            <div class="input-group mr-2">
                                <input type="text" class="form-control" placeholder="Search...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="section-body mt-3">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-vcenter mb-0 text-nowrap">
                            <thead>
                                <tr>
                                    <th>Rider</th>
                                    <th class="w100">Email</th>
                                    <th class="w100">Phone</th>
                                    <th>Status</th>
                                    <th>Is Approved</th>
                                    <th></th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data as $rider)
                                    @if (!empty($rider) )
                                        <tr>
                                        <td class="d-flex align-items-center">                                            
                                            @if($rider->avatar != null)
                                                <img src="{{ asset($rider->avatar) }}" alt="Avatar" class="w30 rounded-circle mr-2">
                                            @else
                                                <div class="avatar-placeholder rounded-circle d-flex align-items-center justify-content-center mr-2"
                                                    style="width: 30px; height: 30px; background-color: #ccc; font-weight: bold; font-size: 14px; color: #fff;">
                                                    {{ strtoupper(substr($rider->first_name ?? $rider->last_name, 0, 2)) }}
                                                </div>
                                            @endif
                                            <span>{{ $rider->first_name }} {{ $rider->last_name }}</span>
                                        </td>
                                        <td>{{ $rider->email }}</td>
                                        <td>{{ $rider->phone }}</td>
                                        <td>
                                            <span class="tag 
                                                    @if ($rider->status == 'inactive')
                                                    tag-info
                                                    @else
                                                    tag-danger
                                                    @endif
                                                ">{{ $rider->status }}</span>
                                        </td>
                                        <td>
                                            <span class="tag 
                                                    @if ($rider->status == 'pending')
                                                    tag-info
                                                    @elseif ($rider->status == 'suspended')
                                                    tag-warning
                                                    @else
                                                    tag-danger
                                                    @endif
                                                ">{{ $rider->is_approved }}</span>
                                        </td>
                                        <td>
                                            <label class="custom-switch m-0">
                                                <input type="checkbox" name="status" value="1" class="custom-switch-input rider-status-toggle" 
                                                data-rider-id="{{ $rider->id }}"
                                                @if ($rider->status == 'active') checked @endif
                                                @if ($rider->is_approved != 'approved') disabled @endif
                                                >
                                                <span class="custom-switch-indicator"></span>
                                            </label>
                                        </td> 
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="dropdown d-flex position-absolute ">
                                                <a class="nav-link icon d-none d-md-flex btn btn-default btn-icon ml-2"
                                                    data-toggle="dropdown"><i class="fa fa-ellipsis-v"></i></a>
                                                <div class="dropdown-menu ">
                                                    <a class="dropdown-item" href="{{ route('admin.riders.show', $rider->id) }}"><i class="dropdown-icon fa fa-eye"></i>
                                                        View</a>
                                                    <a class="dropdown-item" href="{{ route('admin.riders.edit', $rider->id) }}"><i
                                                            class="dropdown-icon fa fa-pencil"></i> Edit</a>
                                                </div>
                                            </div>
                                            </div>
                                        </td>                                          
                                    </tr>
                                    @else
                                    <tr>
                                        <td colspan="5" class="text-center">No Riders Found</td>
                                    </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
  $(document).ready(function(){
    $('.rider-status-toggle').on('change', function() {
        var checkbox = $(this);
        var riderId = checkbox.data('rider-id');
        var status = checkbox.is(':checked') ? 1 : 0;

        $.ajax({
            url: "{{ route('admin.riders.updateStatus') }}",
            method: 'POST',
            data: {
                rider_id: riderId,
                status: status,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr['success']('Status updated successfully', 'Successfully');
                    window.location.reload();
                }else{
                    toastr['error'](response.message, 'Oops!');
                }
            }
        })
    });
  });
</script>
@endpush

