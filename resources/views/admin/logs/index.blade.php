@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Logs Système (Admin)</h3>
                    <div class="card-tools">
                        <form action="{{ route('admin.logs.clear') }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-warning" onclick="return confirm('Êtes-vous sûr de vouloir effacer tous les logs ?')">
                                <i class="fas fa-trash"></i> Effacer les logs
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted">Gestion des logs système - Interface en cours de développement</p>
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
