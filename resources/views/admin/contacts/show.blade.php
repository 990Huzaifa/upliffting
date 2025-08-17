@extends('admin.layout.app')
@section('title', 'Contact Details')
@section('content')
<div class="container mt-4">
    <h2>Contact Details</h2>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">{{ $contact->full_name }}</h5>
            <p><strong>Email:</strong> {{ $contact->email }}</p>
            <p><strong>Phone:</strong> {{ $contact->phone }}</p>
            <p><strong>Role:</strong> {{ ucfirst($contact->role) }}</p>
            <hr>
            <p><strong>Message:</strong></p>
            <p>{{ $contact->message }}</p>
            <a href="{{ url('admin/contact') }}" class="btn btn-secondary mt-3">Back to List</a>
        </div>
    </div>
</div>
@endsection


@push('scripts')
@endpush