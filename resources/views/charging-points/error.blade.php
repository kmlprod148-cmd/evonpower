@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="alert alert-danger">
            <h4>Erreur</h4>
            <p>{{ $message }}</p>
            @if(isset($error))
                <p><small>{{ $error }}</small></p>
            @endif
        </div>
        <a href="{{ route('charging-points.index') }}" class="btn btn-secondary">Retour à la liste</a>
    </div>
@endsection
