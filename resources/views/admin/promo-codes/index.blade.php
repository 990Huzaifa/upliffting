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
                                    <th>Code</th>
                                    <th class="w100">Discount Type</th>
                                    <th class="w100">Discount Value</th>
                                    <th>Expiry</th>
                                    <th>Usage Limit</th>
                                    <th>Used</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data as $item)
                                    @if (!empty($item) )
                                        <tr>
                                        <td class="d-flex align-items-center">{{ $item->code }}</td>
                                        <td>{{ $item->discount_type }}</td>
                                        <td>{{ $item->discount_value }}</td>
                                        <td>{{ $item->expiry_date }}</td>
                                        <td>{{ $item->usage_limit }}</td>
                                        <td>
                                            <label class="custom-switch m-0">
                                                <input type="checkbox" name="status" value="1" class="custom-switch-input rider-status-toggle" 
                                                data-promocode-id="{{ $item->id }}"
                                                @if ($item->status == 'active') checked @endif
                                                >
                                                <span class="custom-switch-indicator"></span>
                                            </label>
                                        </td> 
                                        <td>
                                            <a class="btn " href="{{ route('admin.promocode.edit', $item->id) }}"><i
                                                            class="fa fa-pencil"></i> Edit</a>
                                        </td>                                          
                                    </tr>
                                    @else
                                    <tr>
                                        <td colspan="5" class="text-center">No Promo Codes Found</td>
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

