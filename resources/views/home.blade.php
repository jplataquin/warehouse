@extends('layouts.logger')

@section('inner_content')
<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">{{ __('Dashboard') }}</div>

            <div class="card-body">
                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                <p>Welcome, {{ Auth::user()->name }}!</p>
                
                @if(Auth::user()->isLogger())
                    <p>Select a warehouse from the sidebar to view its dashboard and manage entries.</p>
                @else
                    <p>You are logged in as <strong>{{ ucfirst(Auth::user()->role) }}</strong>.</p>
                    <p>Use the navigation menu to manage projects, warehouses, and items.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
