@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto mt-10">
    <h1 class="text-xl font-bold mb-4">Test Leaflet</h1>
    <div id="map" style="height: 400px; border-radius: 8px;"></div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset("css/leaflet/leaflet.css") }}" crossorigin=""/>
@endpush

@push('scripts')
<script src="{{ asset("js/leaflet/leaflet.js") }}" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const map = L.map('map').setView([34.020882, -6.841650], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    L.marker([34.020882, -6.841650]).addTo(map).bindPopup('Rabat, Maroc').openPopup();
    setTimeout(function() { map.invalidateSize(); }, 500);
});
</script>
@endpush 