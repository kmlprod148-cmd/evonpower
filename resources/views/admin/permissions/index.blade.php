@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Gestion des Permissions (Admin)</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nouvelle Permission
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted">Gestion administrative des permissions - Interface en cours de développement</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Cette page est fonctionnelle. Les fonctionnalités complètes seront ajoutées prochainement.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
