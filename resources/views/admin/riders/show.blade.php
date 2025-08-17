@extends('admin.layout.app')
@section('title', 'Riders Details')
@section('content')

    <div class="section-body mt-3">
        <div class="container-fluid">
            <div class="row clearfix">
                <div class="col-lg-4 col-md-12">
                    <div class="card c_grid c_yellow">
                        <div class="card-body text-center">
                            <div class="circle d-flex align-items-center justify-content-center">
                                @if($data->avatar != null)
                                    <img class="rounded-circle" width="80px" src="{{ asset($data->avatar) }}" alt="">
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
                            <div class="d-flex justify-content-center align-items-center">
                                <button class="btn btn-default btn-sm mx-3">{{ $data->online_status }}</button>
                                @php
                                $statu_list = ['unknown','pending', 'approved', 'suspended'];
                                @endphp
                                <select id="statusSelect" class="form-select w-auto form-control">
                                    @foreach ($statu_list as $status)
                                        <option value="{{ $status }}" {{ $data->is_approved == $status ? 'selected' : '' }}>{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Rider Info</h3>
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
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Total Rides</h3>
                                </div>
                                <div class="card-body">
                                    <h5 class="number mb-0 font-32 counter">{{ $data->total_rides }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Current Rating</h3>
                                </div>
                                <div class="card-body">
                                    <h5 class="number mb-0 font-32 counter">{{$data->current_rating}}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Wallet</h3>
                                </div>
                                <div class="card-body">
                                    <h5 class="mb-0 font-32">$0.00</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Rider License</h3>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                <li class="list-group-item">
                                    <small class="text-muted">license_number: </small>
                                    <p class="mb-0">{{ $data->license_number }}</p>
                                </li>
                                <li class="list-group-item">
                                    <small class="text-muted">license_expiry: </small>
                                    <p class="mb-0">{{ $data->license_expiry }}</p>
                                </li>
                                <li class="list-group-item">
                                    <small class="text-muted">License Photo: </small>
                                    @php
                                        $license_photo = json_decode($data->license_photo);
                                        $front_photo = $license_photo[0] ?? null;
                                        $back_photo = $license_photo[1] ?? null;
                                    @endphp
                                    <div class="row">
                                        <div class="col-sm-6 col-12"><img class="img-fluid w-50" src="{{ asset($front_photo) }}" alt=""></div>
                                        <div class="col-sm-6 col-12"><img class="img-fluid w-50" src="{{ asset($back_photo) }}" alt=""></div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Rider Vehicle</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-hover">
                                <thead class="w100">
                                <tr>
                                    <th class="w100 text-center">Vehicle Type</th>
                                    <th class="w100 text-center">Photos</th>
                                    <th class="w100 text-center">Registration Number</th>
                                    <th class="w100 text-center">Model</th>
                                    <th class="w100 text-center">Color</th>
                                    <th class="w100 text-center">Make</th>
                                    <th class="w100 text-center">Year</th>
                                    <th class="w100 text-center">Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                    @if($vehicles->count() > 0)
                                    @foreach ($vehicles as $vehicle)
                                    <tr>
                                        <td>{{ $vehicle->vehicle_type }}</td>
                                        <td>
                                            <img class="img-fluid w-50" src="{{ assert($vehicle->photos) }}" alt="">
                                        </td>
                                        <td>{{ $vehicle->registration_number }}</td>
                                        <td>{{ $vehicle->model }}</td>
                                        <td>{{ $vehicle->color }}</td>
                                        <td>{{ $vehicle->make }}</td>
                                        <td>{{ $vehicle->year }}</td>
                                        <td><button type="button" class="inspect-btn btn btn-primary" data-toggle="modal" data-target="#inspect{{ $vehicle->id }}" data-vehicle-id="{{ $vehicle->id }}"><i class="fas fa-microscope"></i></button></td>
                                    </tr>
                                    @endforeach
                                    @else
                                    <tr><td colspan="7" class="text-center">No Vehicle</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- inspect car condition modal -->
    @if($vehicles->count() > 0)
    @foreach ($vehicles as $vehicle)
    <!-- Inspect Car Condition Modal -->
    <div class="modal fade" id="inspect{{ $vehicle->id }}" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="title" id="defaultModalLabel">Inspection Details</h6>
                </div>
                <div class="modal-body">
                    <div class="row clearfix">
                        <div class="col-12">
                            <form action="" method="post">

                                <h5>Inspection Points</h5>
                                <ul id="inspection-points" class="list-group">
                                    <!-- Inspection points will be added dynamically here -->
                                </ul>
                                <button type="button" class="btn btn-primary" id="approve-btn">Sumbit</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endforeach
    @endif


    <!-- Reject Reason Modal -->
    <x-modal id="suspendReasonModal" title="Reason for Suspension" form_id="suspendForm">
        <div class="mb-3">
        <label for="suspendReason" class="form-label">Why are you suspending?</label>
        <textarea id="suspendReason" name="reason"
                    class="form-control" rows="3" required></textarea>
        </div>
        <x-slot name="footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button> 
        <button type="submit" class="btn btn-danger">Suspend Rider</button>
        </x-slot>
    </x-modal>

@endsection


@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
        const statusSelect = document.getElementById('statusSelect');
        const suspendModal = new bootstrap.Modal(document.getElementById('suspendReasonModal'));
        let pendingStatus = null;

        statusSelect.addEventListener('change', function() {
            const status = this.value;
            if (!status) return;

            if (status === 'suspended') {
                // hold and open modal
                pendingStatus = status;
                suspendModal.show();
            } else {
                updateStatus(status);
            }
        });


        // 2) Wire up the modal form submit
        document.getElementById('suspendForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const reason = document.getElementById('suspendReason').value.trim();
            if (!reason) {
            return toastr['error']('Please enter a reason before suspending', 'Oops!');
            }
            suspendModal.hide();
            
            suspend(reason);
        });

        function updateStatus(status) {
            $.ajax({
                url: '/admin/riders/approved/' + {{ $data->user_id }}+ '/' + status,
                method: 'PUT',
                data: {_token: '{{ csrf_token() }}'}
            }).done(function (response) {
                if (response.success) {
                    toastr['success']('Status updated successfully', 'Successfully');
                }else{
                    toastr['error'](response.message, 'Oops!');
                }
            }).fail(function() {
            toastr['error']('Network or server error', 'Oops!');
            
            });
        }

        // 4) Helper to send mail then update status
        function suspend(reason) {
            var userId = {{ $data->user_id }};
            $.ajax({
            url: `/admin/riders/send-mail/${userId}`,
            method: 'post',
            data: {
                _token: '{{ csrf_token() }}',
                reason: reason
            }
            })
            .done(function(response) {
            if (!response.success) {
                throw response.error;
            }
            toastr['success']('Mail has been sent', 'Successfully');
            // now update status
            return $.ajax({
                url: `/admin/riders/approved/${userId}/${pendingStatus}`,
                method: 'PUT',
                data: { _token: '{{ csrf_token() }}' }
            });
            })
            .then(function(updateResp) {
            if (updateResp.success) {
                toastr['success']('Status updated successfully', 'Successfully');
                statusSelect.value = '';
            } else {
                throw updateResp.message;
            }
            })
            .fail(function(err) {
            toastr['error'](err.message || err, 'Oops!');
            });
        }
    });
  
  
  
    // for inspection

        // Approve inspection
        $('#approve-btn').on('click', function () {
            var vehicleId = $('#inspect').data('vehicle-id');  // Use modal's vehicle ID.
            
            // Prepare the data from the status select fields
            let statusData = {};
            $('.status-select').each(function () {
                const field = $(this).data('field');
                const status = $(this).val();
                statusData[field] = status;
            });

            // Send approval request via AJAX
            $.ajax({
                url: '/admin/vehicles/approve-inspection/' + vehicleId,  // Include the vehicleId in the URL
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    statuses: statusData   // Send status data for all inspection points
                },
                success: function (response) {
                    alert('Inspection Approved');
                    $('#inspect').modal('hide');  // Close the modal after approval
                }
            });
        });

        // When the inspect button is clicked

function toLabel(key) {
    return key
        .replace(/_/g, ' ')  // Replace underscores with spaces
        .replace(/\b\w/g, function (char) { return char.toUpperCase(); }); // Capitalize the first letter of each word
}
        // make Laravel's asset base URL available in JS
const baseUrl = "{{ asset('') }}";

// Dynamically render inspection points in the modal
$('.inspect-btn').on('click', function () {
    const vehicleId = $(this).data('vehicle-id');
    $('#inspect').data('vehicle-id', vehicleId);  // Set the vehicleId to modal

    $.ajax({
        url: '/admin/vehicles-inspection/' + vehicleId,
        method: 'GET',
        success: function (resp) {
            $('#inspection-points').empty();

            if (!resp.data || resp.data.length === 0) {
                return $('#inspection-points').append('<li class="list-group-item">Not uploaded yet</li>');
            }

            const pointData = resp.data[0];
            let html = '';

            // Render each inspection point
            Object.keys(pointData).forEach(key => {
                if (['id', 'vehicle_id', 'created_at', 'updated_at'].includes(key)) return;
                if (!pointData.hasOwnProperty(key) || key.startsWith('is_')) return;

                let images = [];
                try {
                    images = JSON.parse(pointData[key]) || [];
                } catch (e) {
                    console.error('Error parsing JSON for key: ' + key, e);
                }

                const statusKey = 'is_' + key;
                const currentStatus = pointData[statusKey];

                html += `<li class="list-group-item">
                            <strong>${toLabel(key)}</strong><br>`;

                // Debugging log to check images
                console.log("Images for " + key + ": ", images);

                images.forEach(src => {
                    // Make sure to properly generate the full URL for the image
                    const url = baseUrl + src.replace(/\\/g, '/');
                    // Debugging log to check the generated image URL
                    console.log("Image URL: ", url);

                    html += `<img src="${url}" style="max-width:120px; margin:4px;" class="img-thumbnail">`;
                });

                html += `<div class="mt-2">
                            <select class="form-control status-select" data-field="${key}">
                                <option value="0" ${currentStatus == 0 ? 'selected' : ''}>Pending</option>
                                <option value="1" ${currentStatus == 1 ? 'selected' : ''}>Approved</option>
                                <option value="2" ${currentStatus == 2 ? 'selected' : ''}>Reject</option>
                            </select>
                        </div>
                    </li>`;
            });

            $('#inspection-points').html(html);
        },
        error: function(xhr, status, error) {
            console.error('Error fetching inspection points:', error);
        }
    });
});


        // Reject inspection and show reason modal
        $('#reject-btn').on('click', function () {
            var vehicleId = $('.inspect-btn').data('vehicle-id');
            $('#submit-rejection').data('vehicle-id', vehicleId);
        });

        // Submit rejection with reason
        $('#submit-rejection').on('click', function () {
            var vehicleId = $(this).data('vehicle-id');
            var reason = $('#reject-reason').val();

            $.ajax({
                url: '/admin/vehicles/' + vehicleId + '/reject-inspection',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    reason: reason
                },
                success: function (response) {
                    alert('Inspection Rejected');
                    $('#reject-reason-modal').modal('hide');
                    $('#inspect').modal('hide');
                }
            });
        });
    </script>

@endpush