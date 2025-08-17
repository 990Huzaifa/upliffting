@extends('admin.layout.app')
@section('title', 'Vehicle Type Rates')

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Add Vehicale Type Rate</h3>
                </div>
                <form id="form" enctype="multipart/form-data" action="{{ route('admin.vehicle-type-rates.update', $data->id) }}" class="card-body" method="post">
                    @csrf
                    {{-- Hidden so you can pick it up in JS --}}
                    <input type="hidden" id="initial-country" value="{{ old('country_id', $data->country_id) }}">
                    <input type="hidden" id="initial-state"   value="{{ old('state_id',   $data->state_id) }}">
                    <input type="hidden" id="initial-city"    value="{{ old('city_id',    $data->city_id) }}">
                    <div class="row clearfix">
                        <div class="col-md-4 col-sm-12">
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="title" value="{{ old('title', $data->title) }}" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <div class="form-group">
                                <label>Base Price</label>
                                <input type="text" value="{{ old('base_price', $data->base_price) }}" name="base_price" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <div class="form-group">
                                <label>Booking Fee</label>
                                <input type="text" value="{{ old('booking_fee', $data->booking_fee) }}" name="booking_fee" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label>Price Per KM</label>
                                <input type="text" value="{{ old('price_per_km', $data->price_per_km) }}" name="price_per_km" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label>Price Per Mint</label>
                                <input type="text" value="{{ old('price_per_min', $data->price_per_min) }}" name="price_per_min" class="form-control">
                            </div>
                        </div>
                        
                        <div class="col-md-4 col-sm-12">
                            <label>Countries</label>
                            <select class="form-control" id="country-select" name="country_id" placeholder="Start typing...">
                                <option value="">— Choose a Country —</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}" @if ($country->id == $data->country_id) 
                                        
                                    @endif>{{ $country->name }}</option>
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
                                    placeholder="Please type what you want...">{{ old('description', $data->description) }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group mt-3 mb-3">
                                <label>Icon</label>
                                <input type="file" class="dropify" name="icon" data-default-file="{{ $data->icon ? asset($data->icon) : '' }}">
                                <small id="fileHelp" class="form-text text-muted">This is some placeholder block-level help
                                    text for the above input. It's a bit lighter and easily wraps to a new line.</small>
                            </div>
                        </div>                        
                        <div class="col-sm-12">
                            <button type="submit" class="btn btn-primary">Update</button>
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
$(function(){

  // 1. grab initial values
  var initCountry = $('#initial-country').val(),
      initState   = $('#initial-state').val(),
      initCity    = $('#initial-city').val();

  // 2. init Select2
  $('#country-select, #state-select, #cities-select').select2({
    placeholder: 'Type to search…',
    allowClear: true
  });

  // 3. country → load states
  $('#country-select').on('change', function(){
    var countryId = $(this).val();
    $('#state-select')
      .empty()
      .append('<option value="">— Choose a State —</option>')
      .trigger('change');         // clear cities too
    $('#cities-select').empty().append('<option value="">— Choose a City —</option>');

    if(!countryId) return;

    $.getJSON('/admin/states/'+countryId, function(states){
      states.forEach(function(s){
        $('#state-select')
          .append('<option value="'+s.id+'">'+s.name+'</option>');
      });
      // select initial state on first load
      if(initState){
        $('#state-select').val(initState).trigger('change');
        initState = null;  // only do this once
      }
    });
  });

  // 4. state → load cities
  $('#state-select').on('change', function(){
    var stateId = $(this).val(),
        $city   = $('#cities-select');
    $city.empty().append('<option value="">— Choose a City —</option>');

    if(!stateId) return;

    $.getJSON("{{ asset('assets/cities.json') }}")
      .done(function(json){
        json.cities
          .filter(c => c.stateId == stateId)
          .forEach(c => {
            $city.append('<option value="'+c.id+'">'+c.name+'</option>');
          });
        // select initial city on first load
        if(initCity){
          $city.val(initCity);
          initCity = null;
        }
      })
      .fail(function(){
        $city.html('<option value="">Error loading cities</option>');
      });
  });

  // 5. kick things off if we have an initial country
  if(initCountry){
    $('#country-select').val(initCountry).trigger('change');
  }

});
</script>


@endpush
