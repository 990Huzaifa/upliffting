@extends('admin.layout.app')
@section('title', 'Vehicle Type Rates')

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Add Vehicale Type Rate</h3>
                </div>
                <form id="form" enctype="multipart/form-data" action="{{ route('admin.vehicle-type-rates.store') }}" class="card-body" method="Post">
                    @csrf
                    <div class="row clearfix">
                        <div class="col-md-4 col-sm-12">
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="title" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <div class="form-group">
                                <label>Base Price</label>
                                <input type="text" name="base_price" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <div class="form-group">
                                <label>Booking Fee</label>
                                <input type="text" name="booking_fee" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label>Price Per KM</label>
                                <input type="text" name="price_per_km" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label>Price Per Mint</label>
                                <input type="text" name="price_per_min" class="form-control">
                            </div>
                        </div>
                        
                        <div class="col-md-4 col-sm-12">
                            <label>Countries</label>
                            <select class="form-control" id="country-select" name="country_id" placeholder="Start typing...">
                                <option value="">— Choose a Country —</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <label>States</label>
                            <select class="form-control" id="state-select" name="state_id" placeholder="Start typing...">
                                <option value="">— Choose a State —</option>

                                <!-- …more options… -->
                            </select>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <label>City</label>
                            <select class="form-control" id="cities-select" name="city_id" placeholder="Start typing...">
                                <option value="">— Choose a City —</option>

                                <!-- …more options… -->
                            </select>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group mt-3">
                                <label>Description</label>
                                <textarea rows="9" class="form-control no-resize" name="description"
                                    placeholder="Please type what you want..."></textarea>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group mt-3 mb-3">
                                <label>Icon</label>
                                <input type="file" class="dropify" name="icon">
                                <small id="fileHelp" class="form-text text-muted">This is some placeholder block-level help
                                    text for the above input. It's a bit lighter and easily wraps to a new line.</small>
                            </div>
                        </div>                        
                        <div class="col-sm-12">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
  <!-- Select2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script>
    $(document).ready(function() {

      // 1) Initialize static select
      $('#country-select').select2({
        placeholder: 'Type to search…',
        allowClear: true
      });
      $('#state-select').select2({
        placeholder: 'Type to search…',
        allowClear: true
      });
      $('#cities-select').select2({
        placeholder: 'Type to search…',
        allowClear: true
      });
      

    // get state-select
    $('#country-select').on('change', function() {
        var countryId = $(this).val();
        if (countryId) {
            $.ajax({
                url: '/admin/states/' + countryId,
                type: "GET",
                dataType: "json",
                success: function(data) {
                    $('#state-select').empty();
                    $('#state-select').append('<option value="">Select State</option>');
                    // need a loop to print name and id
                    $.each(data, function(key, value) {
                        $('#state-select').append('<option value="' + value.id + '">' + value.name + '</option>');
                    });
                }
            });
        } else {
            $('#state-select').empty();
        }


    });

    // get cities-select
    $('#state-select').on('change', function() {
    var stateId = $(this).val();
    var $citySel = $('#cities-select');

    // reset if no state chosen
    if (!stateId) {
      return $citySel.html('<option value="">-- Select City --</option>');
    }

    // fetch the JSON each time
    $.getJSON("{{ asset('assets/cities.json') }}")
      .done(function(data) {
        // data.cities is your array
        var allCities = data.cities;

        // debug: check we have data
        console.log('loaded', allCities.length, 'cities');

        // filter by stateId (loose compare allows "1" == 1)
        var filtered = allCities.filter(function(city) {
          return city.stateId == stateId;
        });

        console.log('filtered', filtered.length, 'cities for state', stateId);

        // build new options
        var opts = '<option value="">-- Select City --</option>';
        filtered.forEach(function(city) {
          opts += '<option value="' + city.id + '">' + city.name + '</option>';
        });

        // inject
        $citySel.html(opts);
      })
      .fail(function(jqxhr, status, err) {
        console.error('Error loading cities.json:', status, err);
        $citySel.html('<option value="">Error loading cities</option>');
      });
  });

});
</script>

@endpush
