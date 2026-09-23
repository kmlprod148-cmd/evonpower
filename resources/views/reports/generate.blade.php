@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Générer un Rapport</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('reports.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="report_type">Type de rapport</label>
                            <select class="form-control" id="report_type" name="report_type" required>
                                <option value="">Sélectionner un type</option>
                                <option value="transactions">Transactions</option>
                                <option value="reservations">Réservations</option>
                                <option value="charging_points">Points de charge</option>
                                <option value="users">Utilisateurs</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="date_from">Date de début</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" required>
                        </div>
                        <div class="form-group">
                            <label for="date_to">Date de fin</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" required>
                        </div>
                        <div class="form-group">
                            <label for="format">Format</label>
                            <select class="form-control" id="format" name="format" required>
                                <option value="pdf">PDF</option>
                                <option value="excel">Excel</option>
                                <option value="csv">CSV</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Générer</button>
                        <a href="{{ route('reports.index') }}" class="btn btn-secondary">Annuler</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
