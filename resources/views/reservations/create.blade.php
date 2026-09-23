@extends('layouts.app')

@section('title', 'Nouvelle Réservation - ' . $chargingPoint->name)

@push('meta')
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
@endpush

@section('styles')
<!-- Tailwind CSS CDN pour l'animation 3D -->
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
<link rel="stylesheet" href="{{ asset('css/reservation-page.css') }}">
<style>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap");

/* Global Design System */
:root{
  --brand-1:#4f46e5; /* indigo-600 */
  --brand-2:#7c3aed; /* violet-600 */
  --brand-3:#ec4899; /* pink-500 */
  --accent:#10b981;  /* emerald-500 */
  --muted:#f8fafc;
  --ring:#6366f1;
}

html.dark{
  color-scheme: dark;
}

.glass-effect{
  background: rgba(255,255,255,.7);
  backdrop-filter: blur(8px);
}
.dark .glass-effect{
  background: rgba(17,24,39,.6);
}

.floating-card{
  border: 1px solid rgba(0,0,0,.06);
  transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
}
.floating-card:hover{
  transform: translateY(-2px);
  box-shadow: 0 12px 28px rgba(2,6,23,.08);
  border-color: rgba(99,102,241,.25);
}

.touch-target{ min-height: 44px; }

/* Buttons */
.btn{
  @apply inline-flex items-center justify-center font-bold rounded-2xl px-5 py-3 transition focus:outline-none focus-visible:ring-4;
  box-shadow: 0 8px 20px rgba(79,70,229,.18);
}
.btn-primary{
  background: linear-gradient(90deg,var(--brand-1),var(--brand-2),var(--brand-3));
  color:#fff;
}
.btn-primary:hover{ filter: brightness(1.05); }
.btn-primary:focus-visible{ box-shadow: 0 0 0 4px rgba(99,102,241,.25); }

.btn-ghost{
  background:#fff; color:#111827; border:2px solid #e5e7eb;
}
.btn-ghost:hover{ background:#f9fafb; }
.dark .btn-ghost{ background: #0b1220; color:#e5e7eb; border-color:#1f2937; }
.dark .btn-ghost:hover{ background:#111827; }

/* Cards with section headers */
.section{
  @apply rounded-3xl shadow-2xl border;
  border-color: rgba(0,0,0,.06);
}
.section-title{
  @apply text-xl font-bold text-gray-900 flex items-center gap-3 mb-4;
}
.dark .section-title{ @apply text-gray-100; }

/* Inputs */
.modern-input{
  @apply px-4 py-3 rounded-xl border focus:ring-2 focus:border-transparent transition;
  border-color:#e5e7eb;
}
.modern-input:focus{
  --tw-ring-color: var(--ring);
}

/* Toggle cards */
.toggle-card{
  @apply cursor-pointer border-2 rounded-2xl p-5 transition;
  border-color:#e5e7eb;
}
.toggle-card:hover{ box-shadow: 0 10px 24px rgba(2,6,23,.06); }
.toggle-card.is-active{
  border-color: var(--ring);
  background: linear-gradient(0deg, rgba(99,102,241,.06), rgba(99,102,241,.02));
}

/* Timepicker polish */
.timeline-simple{ height:10px; border-radius:6px; background:#eef2ff; }
.timeline-track{ background: linear-gradient(90deg,var(--brand-1),var(--brand-2)); border-radius:6px; }
.timeline-cursor{
  width:20px; height:20px; top:-6px;
  background:#fff; border:3px solid var(--brand-2);
  box-shadow: 0 8px 20px rgba(124,58,237,.25);
}
.timeline-cursor:hover{ transform: scale(1.08); }

/* Enhanced duration selector with 10-minute intervals */
#duration-timepicker {
  width: 100%;
  margin: 20px 0;
}

.timeline-enhanced {
  width: 100%;
  height: 12px;
  background: #eef2ff;
  border-radius: 6px;
  position: relative;
  cursor: pointer;
}

#duration-track {
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  background: linear-gradient(90deg, #4f46e5, #7c3aed);
  border-radius: 6px;
  transition: width 0.2s ease;
}

/* Timeline markers for 10-minute intervals */
.timeline-markers {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
}

.timeline-marker {
  position: absolute;
  top: 0;
  transform: translateX(-50%);
}

.marker-dot {
  width: 4px;
  height: 4px;
  background: #6366f1;
  border-radius: 50%;
  position: absolute;
  top: 4px;
  left: 50%;
  transform: translateX(-50%);
  opacity: 0.6;
}

.marker-label {
  position: absolute;
  top: -20px;
  left: 50%;
  transform: translateX(-50%);
  font-size: 10px;
  font-weight: 500;
  color: #6b7280;
  white-space: nowrap;
}

/* Responsive marker labels */
@media (max-width: 640px) {
  .marker-label {
    font-size: 8px;
    top: -18px;
  }
  
  .marker-label:nth-child(even) {
    top: -35px;
  }
}

#duration-cursor {
  position: absolute;
  top: -6px;
  left: 0;
  width: 22px;
  height: 22px;
  background: #fff;
  border: 3px solid #7c3aed;
  border-radius: 50%;
  box-shadow: 0 8px 20px rgba(124, 58, 237, 0.25);
  cursor: grab;
  outline: none;
  transition: transform 0.08s ease, box-shadow 0.2s ease;
  z-index: 15;
  display: block !important;
  visibility: visible !important;
  opacity: 1 !important;
}

#duration-cursor:hover {
  transform: scale(1.1);
  box-shadow: 0 12px 24px rgba(124, 58, 237, 0.35);
}

#duration-cursor:active {
  cursor: grabbing;
  transform: scale(1.05);
}

#duration-cursor:focus {
  box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.2), 0 8px 20px rgba(124, 58, 237, 0.25);
}

#duration-display {
  font-weight: 600;
  color: #1f2937;
  font-size: 18px;
}

/* Cost badges */
.badge{
  @apply inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-semibold;
  background:#eef2ff; color:#3730a3;
}
.dark .badge{ background:#111827; color:#c7d2fe; }

/* Modal */
.modal-card{
  border:1px solid rgba(0,0,0,.08);
  border-radius: 1.25rem;
  box-shadow: 0 20px 50px rgba(2,6,23,.25);
}

/* Reduce strong debug styles from original */
#time-picker-element, #time-picker-container { border: none !important; background: transparent !important; }

/* Base styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Poppins", sans-serif;
}

.form-control {
    display: block;
    width: 100%;
    height: calc(1.5em + 0.75rem + 2px);
    padding: 8px;
    font-size: 0.875rem;
    font-weight: 400;
    color: #5c6873;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #e4e7ea;
    border-radius: 0;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    min-height: 45px;
}

/* input-time-wrap css start */
.input-time-wrap {
    position: relative;
}

.input-time-wrap ul {
    list-style-type: none;
}

.input-time-wrap .input-wrap {
    position: relative;
}

.input-time-wrap input {
    padding-right: 45px;
}

.input-time-wrap .input-wrap .timeIcon {
    position: absolute;
    top: 0;
    right: 0;
    width: 50px;
    height: 100%;
    background: #54b3ff;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
}

.input-time-wrap .input-wrap .timeIcon i {
    position: relative;
    width: 20px;
    height: 20px;
    background: #fff;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.input-time-wrap .input-wrap .timeIcon i::before,
.input-time-wrap .input-wrap .timeIcon i::after {
    content: "";
    position: absolute;
    width: 6px;
    height: 2px;
    background: #54b3ff;
    pointer-events: none;
}

.input-time-wrap .input-wrap .timeIcon i::before {
    transform: rotate(90deg) translateX(-3px);
}

.input-time-wrap .input-wrap .timeIcon i::after {
    transform: rotate(225deg) translateX(-3px);
}

.time-drop-down {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 9;
    background: #fff;
    padding: 10px;
    max-width: 100%;
    width: 260px;
    height: 260px;
    box-shadow: rgb(99 99 99 / 20%) 0px 2px 8px 0px;
    display: flex;
    display: none;
}

.time-drop-down.show {
    display: flex;
}

.input-time-wrap .li-wrap {
    overflow-y: auto;
    padding: 0 5px;
    flex-grow: 1;
    width: calc(100% / 3);
}

/* width */
.input-time-wrap .li-wrap::-webkit-scrollbar {
    width: 3px;
}

/* Track */
.input-time-wrap .li-wrap::-webkit-scrollbar-track {
    background: #f1f1f1;
}

/* Handle */
.input-time-wrap .li-wrap::-webkit-scrollbar-thumb {
    background: #54b3ff;
}

/* Handle on hover */
.input-time-wrap .li-wrap::-webkit-scrollbar-thumb:hover {
    background: #555;
}

.input-time-wrap li {
    position: relative;
    width: 100%;
    height: 40px;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    justify-content: center;
    align-items: center;
    transition: 0.3s ease-in-out;
    cursor: pointer;
}

.input-time-wrap li:not(:last-child) {
    margin-bottom: 10px;
}

.input-time-wrap li:hover {
    background-color: #a4d7ff;
    transition: 0s;
}

.input-time-wrap li.active {
    background-color: #54b3ff;
    color: #fff;
}

/* input-time-wrap css end */

/* CURSEUR ULTRA SIMPLE */
.timepicker {
    position: relative;
    width: 100%;
    height: 60px;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    pointer-events: auto !important;
    padding: 10px;
    margin: 10px 0;
}

.timeline-simple {
    position: relative;
    width: 100%;
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    margin: 20px 0;
    cursor: pointer;
    pointer-events: auto !important;
}

.timeline-track {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    background: #3b82f6;
    border-radius: 4px;
    transition: width 0.2s ease;
    pointer-events: none !important;
}

/* CURSEUR ULTRA SIMPLE */
.timeline-cursor {
    position: absolute;
    top: -4px;
    left: 0px;
    width: 16px;
    height: 16px;
    background: #ef4444;
    border: 2px solid #ffffff;
    border-radius: 50%;
    cursor: pointer;
    z-index: 10;
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    pointer-events: auto !important;
}

.timeline-cursor:hover {
    background: #dc2626;
    transform: scale(1.1);
}

.duration-display {
    text-align: center;
    font-size: 20px;
    font-weight: bold;
    color: #1f2937;
    margin: 10px 0;
}

.duration-label {
    text-align: center;
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 10px;
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.timepicker-container-outer {
    width: 100%;
    max-width: 700px;
    float: left;
    display: block;
    padding: 40px 30px 30px;
    position: relative;
    top: 50px;
    overflow: hidden;
    -webkit-tap-highlight-color: transparent;
    transition: background 0.15s ease;
}

.timepicker-container-inner {
    width: 100%;
    height: 100%;
    max-width: 320px;
    margin: 0 auto;
    position: relative;
    display: block;
}

.timeline-container {
    display: block;
    float: left;
    position: relative;
    width: 100%;
    height: 36px;
}

.current-time {
    display: block;
    position: absolute;
    z-index: 1;
    width: 40px;
    height: 40px;
    border-radius: 20px;
    top: -25px;
    left: -20px;
    cursor: pointer;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

.current-time::after {
    content: "";
    display: block;
    width: 40px;
    height: 40px;
    position: absolute;
    background: #ff6e40;
    transition: all 0.15s ease;
    transform: rotate(45deg);
    border-radius: 20px 20px 3px 20px;
    z-index: -1;
    top: 0;
}

.actual-time {
    color: white;
    line-height: 40px;
    font-size: 12px;
    text-align: center;
    transition: all 0.15s ease;
}

.timeline {
    display: block;
    z-index: 1;
    width: 100%;
    height: 2px;
    position: absolute;
    bottom: 0;
    background: #e5e7eb;
    position: relative;
}

.timeline::after {
    left: auto;
    right: -1px;
}

.timeline-snap-points {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
}

.snap-point {
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.hours-container {
    display: block;
    z-index: 0;
    width: 100%;
    height: 10px;
    position: absolute;
    top: 31px;
    left: 1px;
}

.hour-mark {
    width: 2px;
    display: block;
    float: left;
    height: 4px;
    background: #ff5b38;
    position: relative;
    margin-left: calc((100% / 12) - 2px);
    transition: background 0.15s ease;
}

.hour-mark:nth-child(3n) {
    height: 6px;
    top: -1px;
}

.display-time {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 20px;
    padding: 0 10px;
}

.decrement-time,
.increment-time {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border-radius: 50%;
    background: #f3f4f6;
    transition: all 0.15s ease;
}

.decrement-time:hover,
.increment-time:hover {
    background: #e5e7eb;
}

.decrement-time {
    left: 0;
    text-align: left;
}

.increment-time {
    right: 0;
    text-align: right;
}

.increment-time path,
.decrement-time path {
    transition: all 0.15s ease;
}

.time {
    width: calc(100% - 48px);
    position: relative;
    left: 24px;
    height: 36px;
}

.time-input {
    width: 100%;
    height: 100%;
    border: none;
    background: transparent;
    text-align: center;
    font-size: 18px;
    font-weight: bold;
    color: #374151;
}

.formatted-time {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: bold;
    color: #374151;
    pointer-events: none;
}

.time-active .formatted-time {
    display: none;
}

.am-pm-container {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 20px;
}

.am-pm-button {
    width: calc(50% - 5px);
    height: 36px;
    line-height: 36px;
    background: #2196f3;
    text-align: center;
    color: white;
    border-radius: 4px;
    float: left;
    cursor: pointer;
    transition: all 0.15s ease;
}

.am-pm-button:first-child {
    background: #ff5b38;
    color: white;
}

.am-pm-button:last-child {
    background: white;
    color: #ff5b38;
    margin-left: 10px;
}

.am-pm-button:hover {
    opacity: 0.8;
}

.am-pm-button.active {
    background: #ff5b38;
    color: white;
}

/* Duration Button Styles */
.duration-btn {
    transition: all 0.3s ease;
    cursor: pointer;
    padding: 10px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    margin: 5px;
    background: white;
}

.duration-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-color: #3b82f6;
}

.duration-btn.selected {
    border-color: #3b82f6;
    background-color: #eff6ff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
</style>
@endsection

@section('content')
<div class="min-h-screen bg-white safe-area-top safe-area-bottom relative overflow-hidden">
    <!-- Background decoration -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-gray-100 opacity-20 rounded-full"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-gray-100 opacity-20 rounded-full"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-gray-100 opacity-10 rounded-full"></div>
    </div>
    
    <!-- Mobile-first responsive container - max-width scales from mobile to desktop -->
    <div class="relative z-10 max-w-sm sm:max-w-md md:max-w-lg lg:max-w-2xl xl:max-w-4xl mx-auto px-3 sm:px-4 md:px-6 py-4 sm:py-6 md:py-8">
        <!-- Header mobile-first -->
        <div class="text-center mb-4 sm:mb-6 md:mb-8">
            {{-- Animation 3D du chargeur - reduced size for mobile --}}
            <div class="mb-3 sm:mb-4 md:mb-6">
                @include('components.charger-3d-animation', ['state' => 'idle'])
            </div>
            <h1 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold text-gray-900 dark:text-gray-100 mb-2">Réservez votre session de charge</h1>
            <p class="text-sm sm:text-base md:text-lg text-gray-600 dark:text-gray-300">Borne de recharge {{ $chargingPoint->name }}</p>
        </div>

        <!-- Mobile-first grid - single column on mobile, responsive on larger screens -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 sm:gap-4 md:gap-6">
            <!-- Colonne principale - Formulaire -->
            <div class="lg:col-span-2">
                <div class="glass-effect floating-card mobile-card rounded-xl sm:rounded-2xl md:rounded-3xl shadow-lg sm:shadow-xl md:shadow-2xl overflow-hidden">
                    <!-- En-tête du formulaire - mobile optimized -->
                    <div class="bg-gradient-to-r from-indigo-600 via-violet-600 to-pink-600 px-3 sm:px-4 md:px-6 lg:px-8 py-3 sm:py-4 md:py-6 relative overflow-hidden rounded-t-xl sm:rounded-t-2xl md:rounded-t-3xl">
                        <div class="absolute -right-16 -top-16 w-48 h-48 rounded-full bg-white/10"></div>
                        <div class="absolute -left-24 -bottom-24 w-64 h-64 rounded-full bg-white/10"></div>

                        <div class="relative flex items-center justify-between">
                            <div class="flex items-center gap-4 flex-1 min-w-0">
                                <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl grid place-items-center shadow-lg">
                                    <i class="fas fa-charging-station text-white text-xl"></i>
                                </div>
                                <div class="min-w-0">
                                    <h2 class="text-white font-bold text-xl truncate drop-shadow">{{ $chargingPoint->name }}</h2>
                                    <p class="text-white/80 text-sm truncate">{{ $chargingPoint->address ?? 'Adresse non définie' }}, {{ $chargingPoint->city ?? 'Ville non définie' }}</p>
                                </div>
                            </div>

                            <!-- QR Code Section -->
                            <div class="flex items-center gap-3 ml-4">
                                <div class="text-center">
                                    <div class="w-20 h-20 bg-white/90 backdrop-blur-sm rounded-xl p-2 shadow-lg cursor-pointer hover:scale-105 transition-transform duration-200" 
                                         onclick="window.open('{{ route('public.charging-point.offer', $chargingPoint->id) }}', '_blank')"
                                         title="Cliquez pour voir les détails de la borne">
                                        <div class="w-full h-full bg-black rounded-lg flex items-center justify-center">
                                            <!-- QR Code placeholder - you can replace this with actual QR code generation -->
                                            <div class="grid grid-cols-4 gap-0.5 w-full h-full">
                                                <div class="bg-white"></div><div class="bg-black"></div><div class="bg-white"></div><div class="bg-black"></div>
                                                <div class="bg-black"></div><div class="bg-white"></div><div class="bg-black"></div><div class="bg-white"></div>
                                                <div class="bg-white"></div><div class="bg-black"></div><div class="bg-white"></div><div class="bg-black"></div>
                                                <div class="bg-black"></div><div class="bg-white"></div><div class="bg-black"></div><div class="bg-white"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-white/80 text-xs mt-1 font-medium">Scan to manage</p>
                                </div>
                                
                                <!-- Evon Power Logo -->
                                <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl grid place-items-center shadow-lg">
                                    <img src="/images/evon-logo.png" alt="Evon Power" class="h-6 w-auto">
                                </div>
                            </div>

                            <a href="{{ route('public.charging-point.offer', $chargingPoint->id) }}"
                               class="touch-target btn btn-ghost !rounded-xl ml-4">
                                <i class="fas fa-arrow-left mr-2"></i>
                                <span class="hidden sm:inline">Retour</span>
                            </a>
                        </div>
                    </div>

                    <!-- Contenu du formulaire -->
                    <div class="p-3 sm:p-4 md:p-6 lg:p-8 space-y-4 sm:space-y-6 md:space-y-8">
                        @if(isset($plan) && $plan)
                            <form id="reservationForm" method="POST" action="{{ route('reservations.store', ['chargingPoint' => $chargingPoint->id]) }}" class="space-y-4 sm:space-y-6 md:space-y-8">
                                @csrf
                                
                                <!-- Hidden inputs for required data -->
                                <input type="hidden" name="pricing_plan_id" value="{{ $plan->id }}">
                                
                                <!-- Plan tarifaire sélectionné -->
                                <div class="section p-3 sm:p-4 md:p-6 glass-effect">
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 rounded-xl grid place-items-center" style="background:linear-gradient(135deg,#10b98122,#10b98133)">
                                                <i class="fas fa-tag text-emerald-600 text-lg"></i>
                                            </div>
                                            <div>
                                                <h3 class="font-bold text-gray-900 text-lg">{{ $plan->name }}</h3>
                                                <p class="text-sm text-gray-500">Plan tarifaire actif</p>
                                            </div>
                                        </div>
                                        <div class="text-right space-y-1">
                                            @if($plan->price_per_kwh)
                                                <div class="badge"><i class="fas fa-bolt"></i>{{ number_format($plan->price_per_kwh,2) }} {{ $plan->currency ?? 'EUR' }}/kWh</div>
                                            @endif
                                            @if($plan->price_per_minute)
                                                <div class="badge"><i class="fas fa-clock"></i>{{ number_format($plan->price_per_minute,2) }} {{ $plan->currency ?? 'EUR' }}/min</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Type de réservation -->
                                @php
                                    $hasKwhPricing = !is_null($plan->price_per_kwh) && $plan->price_per_kwh > 0;
                                    $hasMinutePricing = !is_null($plan->price_per_minute) && $plan->price_per_minute > 0;
                                    $showBothTypes = $hasKwhPricing && $hasMinutePricing;
                                @endphp
                                
                                @if($showBothTypes)
                                <div>
                                    <label class="section-title"><i class="fas fa-cog text-indigo-600"></i>Type de réservation</label>
                                    <div class="grid grid-cols-2 gap-4">
                                        <label class="group block">
                                            <input type="radio" name="reservation_type" value="minute" class="sr-only" {{ old('reservation_type', $hasKwhPricing && !$hasMinutePricing ? 'kwh' : 'minute') === 'minute' ? 'checked' : '' }}>
                                            <div class="toggle-card group-[input:checked+&]:is-active">
                                                <div class="flex items-center gap-4">
                                                    <div class="w-11 h-11 rounded-xl grid place-items-center bg-emerald-50">
                                                        <i class="fas fa-clock text-emerald-600"></i>
                                                    </div>
                                                    <div>
                                                        <div class="font-semibold">Par Durée</div>
                                                        <div class="text-sm text-gray-500">Minutes</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>

                                        <label class="group block">
                                            <input type="radio" name="reservation_type" value="kwh" class="sr-only" {{ old('reservation_type', $hasKwhPricing && !$hasMinutePricing ? 'kwh' : 'minute') === 'kwh' ? 'checked' : '' }}>
                                            <div class="toggle-card group-[input:checked+&]:is-active">
                                                <div class="flex items-center gap-4">
                                                    <div class="w-11 h-11 rounded-xl grid place-items-center bg-indigo-50">
                                                        <i class="fas fa-bolt text-indigo-600"></i>
                                                    </div>
                                                    <div>
                                                        <div class="font-semibold">Par Énergie</div>
                                                        <div class="text-sm text-gray-500">kWh</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                @else
                                    <input type="hidden" name="reservation_type" value="{{ old('reservation_type', $hasKwhPricing ? 'kwh' : 'minute') }}">
                                @endif

                                <!-- Durée de réservation avec Time Picker -->
                                @php
                                  $maxDuration = data_get($plan, 'max_duration', 180);   // minutes - increased default
                                  $initialDuration = (int) old('reservation_value', 30); // minutes par défaut
                                @endphp

                                <div class="section p-3 sm:p-4 md:p-6 space-y-3 sm:space-y-4" id="duration-section">
                                  <label class="section-title">
                                    <i class="fas fa-clock text-purple-600"></i>
                                    <span id="value_label">Durée de réservation (minutes)</span>
                                  </label>

                                  <div class="flex items-center justify-between text-sm text-gray-600">
                                    <span class="badge"><i class="fas fa-info-circle"></i> Intervalles de 10 min • Max: {{ $maxDuration }} min</span>
                                    <span class="text-gray-500">Glissez ou utilisez ← →</span>
                                  </div>

                                  <!-- Enhanced Slider with 10-minute intervals -->
                                  <div id="duration-timepicker">
                                    <div class="timeline-enhanced relative" id="duration-timeline">
                                      <div class="timeline-track" id="duration-track" style="width:0%"></div>
                                      
                                      <!-- 10-minute interval markers -->
                                      <div class="timeline-markers">
                                        @for($i = 10; $i <= $maxDuration; $i += 10)
                                          <div class="timeline-marker" style="left: {{ ($i - 5) / ($maxDuration - 5) * 100 }}%">
                                            <div class="marker-dot"></div>
                                            <div class="marker-label">{{ $i }}min</div>
                                          </div>
                                        @endfor
                                      </div>
                                      
                                      <div
                                        class="timeline-cursor"
                                        id="duration-cursor"
                                        role="slider"
                                        tabindex="0"
                                        aria-label="Durée (minutes)"
                                        aria-valuemin="10"
                                        aria-valuemax="{{ $maxDuration }}"
                                        aria-valuenow="{{ $initialDuration }}"
                                        aria-valuetext="{{ $initialDuration }} minutes"
                                      ></div>
                                    </div>
                                    <div class="flex items-center justify-between mt-3">
                                      <small id="value_help" class="text-sm text-gray-500"></small>
                                      <div id="duration-display" class="text-base font-semibold text-gray-900">{{ $initialDuration }} min</div>
                                    </div>
                                  </div>

                                  <!-- Valeur envoyée au serveur -->
                                  <input type="hidden" id="reservation_value" name="reservation_value" value="{{ $initialDuration }}">
                                  <span id="value_unit" class="hidden">min</span>
                                </div>



                                <!-- Informations de contact pour utilisateurs publics -->
                                <div class="section p-3 sm:p-4 md:p-6">
                                    <h3 class="section-title text-base sm:text-lg md:text-xl"><i class="fas fa-user-circle text-indigo-600"></i>Informations de contact</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4 md:gap-6">
                                        <div>
                                            <label for="guest_email" class="block text-sm font-bold text-gray-700 mb-2">
                                                Email <span class="text-red-500">*</span>
                                            </label>
                                            <input type="email" id="guest_email" name="guest_email" 
                                               class="modern-input block w-full"
                                               placeholder="votre@email.com"
                                               value="{{ old('guest_email', auth()->user()?->email ?? '') }}">
                                        </div>
                                        <div>
                                            <label for="guest_phone" class="block text-sm font-bold text-gray-700 mb-2">
                                                Téléphone
                                            </label>
                                            <input type="tel" id="guest_phone" name="guest_phone" 
                                               class="modern-input block w-full"
                                               placeholder="+212 6 12 34 56 78"
                                               value="{{ old('guest_phone', auth()->user()?->phone ?? '') }}">
                                        </div>
                                    </div>
                                </div>

                                <!-- Type de paiement -->
                                <label class="section-title mt-6"><i class="fas fa-credit-card text-pink-600"></i>Mode de paiement</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3 md:gap-4">
                                    @auth
                                    @php
                                        $wallet = auth()->user()->getOrCreateWallet();
                                        $balance = (float) ($wallet->balance ?? 0);
                                    @endphp
                                    @if($balance > 0)
                                    <label class="group block">
                                        <input type="radio" name="payment_method" value="credit" class="sr-only" {{ old('payment_method') === 'credit' ? 'checked' : '' }}>
                                        <div class="toggle-card group-[input:checked+&]:is-active">
                                            <div class="flex items-center gap-4">
                                                <div class="w-11 h-11 rounded-xl grid place-items-center bg-green-50">
                                                    <i class="fas fa-wallet text-green-600"></i>
                                                </div>
                                                <div>
                                                    <div class="font-semibold">{{ __('Solde') }}</div>
                                                    <div class="text-sm text-gray-500" data-balance-value>{{ number_format($balance, 2) }} {{ $plan->currency ?? 'EUR' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                    @endif
                                    @endauth
                                    <label class="group block">
                                        <input type="radio" name="payment_method" value="stripe" class="sr-only" {{ old('payment_method', 'stripe') === 'stripe' ? 'checked' : '' }}>
                                        <div class="toggle-card group-[input:checked+&]:is-active">
                                            <div class="flex items-center gap-4">
                                                <div class="w-11 h-11 rounded-xl grid place-items-center bg-blue-50">
                                                    <i class="fab fa-stripe text-blue-600 text-lg"></i>
                                                </div>
                                                <div>
                                                    <div class="font-semibold">Stripe</div>
                                                    <div class="text-sm text-gray-500">Carte bancaire</div>
                                                </div>
                                            </div>
                                        </div>
                                    </label>

                                    <label class="group block">
                                        <input type="radio" name="payment_method" value="cmi" class="sr-only" {{ old('payment_method', 'stripe') === 'cmi' ? 'checked' : '' }}>
                                        <div class="toggle-card group-[input:checked+&]:is-active">
                                            <div class="flex items-center gap-4">
                                                <div class="w-11 h-11 rounded-xl grid place-items-center bg-indigo-50">
                                                    <i class="fas fa-credit-card text-indigo-600"></i>
                                                </div>
                                                <div>
                                                    <div class="font-semibold">CMI</div>
                                                    <div class="text-sm text-gray-500">Paiement sécurisé</div>
                                                </div>
                                            </div>
                                        </div>
                                    </label>

                                    <label class="group block">
                                        <input type="radio" name="payment_method" value="offline" class="sr-only" {{ old('payment_method', 'stripe') === 'offline' ? 'checked' : '' }}>
                                        <div class="toggle-card group-[input:checked+&]:is-active">
                                            <div class="flex items-center gap-4">
                                                <div class="w-11 h-11 rounded-xl grid place-items-center bg-emerald-50">
                                                    <i class="fas fa-money-bill text-emerald-600"></i>
                                                </div>
                                                <div>
                                                    <div class="font-semibold">Sur place</div>
                                                    <div class="text-sm text-gray-500">Espèces</div>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>

                                <!-- Messages d'erreur -->
                                <div id="reservation-errors">
                                    @if ($errors->any())
                                        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">
                                            <div class="flex items-center mb-2">
                                                <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                                                <h4 class="text-sm font-medium text-red-800">Erreurs de validation</h4>
                                            </div>
                                            <div class="text-sm text-red-700">
                                                @foreach ($errors->all() as $error)
                                                    <div class="mb-1">• {{ $error }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Bouton de soumission -->
                                <div class="flex items-center justify-between pt-6">
                                    <button type="button" onclick="window.history.back()" class="btn btn-ghost">
                                        <i class="fas fa-arrow-left mr-2"></i>Annuler
                                    </button>
                                    <button type="submit" id="submitBtn" class="btn btn-primary" 
                                            onclick="document.querySelector('.charger-3d-container')?.__x.$data.state = 'started'; setTimeout(() => { document.querySelector('.charger-3d-container')?.__x.$data.state = 'charging'; }, 1000);">
                                        <span id="submitText">Créer la réservation</span>
                                        <span id="submitLoading" class="hidden ml-3"><i class="fas fa-spinner fa-spin"></i> Création…</span>
                                    </button>
                                </div>
                            </form>
                            
                            <!-- Fallback pour JavaScript désactivé -->
                            <noscript>
                                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mt-4">
                                    <div class="flex items-center">
                                        <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                                        <span class="text-sm text-yellow-700">
                                            JavaScript est désactivé. Le formulaire fonctionnera mais sans les fonctionnalités interactives.
                                        </span>
                                    </div>
                                </div>
                            </noscript>
                        @else
                            <div class="text-center py-12">
                                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-red-900 mb-2">Aucun plan tarifaire configuré</h3>
                                <p class="text-red-700">Cette borne de recharge n'a pas de plan tarifaire actif. Veuillez contacter l'administrateur.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Colonne latérale - Estimation et informations -->
            <div class="lg:col-span-1">
                <!-- Estimation du coût -->
                <div class="section p-3 sm:p-4 md:p-6">
                    <h3 class="section-title text-base sm:text-lg md:text-xl"><i class="fas fa-calculator text-indigo-600"></i>Calcul du montant total</h3>
                    <div id="costEstimation" class="space-y-4">
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-info-circle text-2xl mb-2 text-indigo-400"></i>
                            <p class="text-base font-medium">Saisissez les détails de votre réservation pour voir le montant total</p>
                        </div>
                    </div>
                </div>

                <!-- Informations de la borne -->
                <div class="glass-effect floating-card rounded-3xl shadow-2xl p-6 mb-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-info-circle text-emerald-600 mr-3 text-xl"></i>
                        Informations de la borne
                    </h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-gradient-to-r from-emerald-50 to-green-50 rounded-xl">
                            <span class="text-sm font-bold text-gray-700">Statut</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-emerald-100 text-emerald-800 shadow-sm">
                                <i class="fas fa-circle text-emerald-500 mr-2"></i>
                                {{ ucfirst($chargingPoint->status ?? 'inconnu') }}
                            </span>
                        </div>
                        
                        @if($chargingPoint->power_output)
                        <div class="flex items-center justify-between p-3 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl">
                            <span class="text-sm font-bold text-gray-700">Puissance</span>
                            <span class="text-sm font-bold text-blue-900">{{ $chargingPoint->power_output }} kW</span>
                        </div>
                        @endif
                        
                        @if($chargingPoint->model)
                        <div class="flex items-center justify-between p-3 bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl">
                            <span class="text-sm font-bold text-gray-700">Modèle</span>
                            <span class="text-sm font-bold text-purple-900">{{ $chargingPoint->model }}</span>
                        </div>
                        @endif
                        
                        @if($chargingPoint->manufacturer)
                        <div class="flex items-center justify-between p-3 bg-gradient-to-r from-orange-50 to-red-50 rounded-xl">
                            <span class="text-sm font-bold text-gray-700">Fabricant</span>
                            <span class="text-sm font-bold text-orange-900">{{ $chargingPoint->manufacturer }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Aide et support -->
                <div class="bg-gradient-to-r from-indigo-50 via-purple-50 to-pink-50 rounded-3xl border border-indigo-200 p-6 shadow-xl">
                    <h3 class="text-xl font-bold text-indigo-900 mb-4 flex items-center">
                        <i class="fas fa-question-circle text-indigo-600 mr-3 text-xl"></i>
                        Besoin d'aide ?
                    </h3>
                    <p class="text-indigo-800 text-base mb-6 font-medium">
                        Si vous avez des questions ou besoin d'assistance, n'hésitez pas à nous contacter.
                    </p>
                    <div class="space-y-3">
                        <div class="flex items-center text-base text-indigo-700 font-medium p-2 rounded-lg hover:bg-indigo-100 transition-colors">
                            <i class="fas fa-phone mr-3 text-indigo-600"></i>
                            <span>+212 5 22 34 56 78</span>
                        </div>
                        <div class="flex items-center text-base text-indigo-700 font-medium p-2 rounded-lg hover:bg-indigo-100 transition-colors">
                            <i class="fas fa-envelope mr-3 text-indigo-600"></i>
                            <span>support@evonpower.com</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom Alert Overlay -->
<div id="customAlertOverlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm grid place-items-center z-50 hidden">
    <div class="bg-white dark:bg-slate-900 modal-card p-6 max-w-md w-full mx-4">
        <div id="customAlertIcon" class="flex justify-center mb-3">
            <i id="customAlertIconClass" class="text-3xl text-blue-600"></i>
        </div>
        <h3 id="customAlertTitle" class="text-lg font-semibold text-center mb-1"></h3>
        <p id="customAlertMessage" class="text-gray-600 dark:text-gray-300 text-center mb-4"></p>
        <div id="customAlertBodyContent" class="text-sm text-gray-500 dark:text-gray-400 text-center mb-4"></div>
        <div class="flex justify-center gap-3">
            <button id="customAlertPrimaryBtn" class="btn btn-primary px-4 py-2"></button>
            <button id="customAlertSecondaryBtn" class="btn btn-ghost px-4 py-2 hidden"></button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('reservationForm');
    const reservationTypeInputs = document.querySelectorAll('input[name="reservation_type"]');
    const reservationValueInput = document.getElementById('reservation_value');
    const valueLabel = document.getElementById('value_label');
    const valueHelp = document.getElementById('value_help');
    const valueUnit = document.getElementById('value_unit');

    const costEstimation = document.getElementById('costEstimation');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const submitLoading = document.getElementById('submitLoading');
    
    // Gestion du type de réservation
    function updateReservationType() {
        const type = document.querySelector('input[name="reservation_type"]:checked')?.value || '{{ $hasKwhPricing && !$hasMinutePricing ? "kwh" : "minute" }}';
        
        if (type === 'minute') {
            valueLabel.textContent = 'Durée de réservation (minutes)';
            valueHelp.textContent = 'Saisissez la durée de votre réservation en minutes';
            valueUnit.textContent = 'min';
        } else {
            valueLabel.textContent = 'Quantité d\'énergie (kWh)';
            valueHelp.textContent = 'Saisissez la quantité d\'énergie souhaitée en kWh';
            valueUnit.textContent = 'kWh';
        }
        
        updateEstimations();
    }



    // Mise à jour des calculs en temps réel basée sur les sélections utilisateur
    window.updateEstimations = function() {
        // Récupérer les sélections actuelles de l'utilisateur
        const type = document.querySelector('input[name="reservation_type"]:checked')?.value || '{{ $hasKwhPricing && !$hasMinutePricing ? "kwh" : "minute" }}';
        const value = parseFloat(reservationValueInput.value) || 0;
        const paymentType = document.querySelector('input[name="payment_type"]:checked')?.value || 'offline';
        
        // Utiliser la valeur du sélecteur de durée
        let finalValue = value;
        
        console.log('Final value for estimation:', finalValue, 'Type:', type);
        
        if (!finalValue || finalValue <= 0) {
            costEstimation.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-info-circle text-2xl mb-2"></i>
                    <p>Saisissez les détails de votre réservation pour voir le montant total</p>
                </div>
            `;
            return;
        }

        // Estimation basée sur les données du plan tarifaire
        const plan = @json($plan ?? null);
        const chargingPoint = @json($chargingPoint ?? null);
        
        console.log('Plan data:', plan);
        console.log('Charging point data:', chargingPoint);
        console.log('Plan price_per_kwh:', plan?.price_per_kwh);
        console.log('Plan price_per_minute:', plan?.price_per_minute);
        console.log('Plan activation_fee:', plan?.activation_fee);
        console.log('Plan vatRate:', plan?.vatRate);
        
        if (!plan) {
            costEstimation.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                    <p>Aucun plan tarifaire disponible</p>
                </div>
            `;
            return;
        }

        // Check if plan has pricing data
        if (!plan.price_per_kwh && !plan.price_per_minute) {
            costEstimation.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                    <p>Plan tarifaire sans prix configuré</p>
                    <p class="text-sm mt-2">Contactez l'administrateur pour configurer les tarifs</p>
                </div>
            `;
            return;
        }

        // Calculs basés sur les sélections utilisateur
        let estimatedDuration = 0;
        let estimatedEnergy = 0;
        let baseAmount = 0;
        let calculationLabel = 'Tarif de base:';
        let calculationDetails = '';

        if (type === 'kwh') {
            estimatedEnergy = finalValue;
            if (plan.price_per_kwh) {
                baseAmount = estimatedEnergy * plan.price_per_kwh;
                calculationLabel = `Tarif énergie:`;
                calculationDetails = `${estimatedEnergy} kWh × ${plan.price_per_kwh.toFixed(2)} ${plan.currency || 'EUR'}/kWh`;
            }
            if (chargingPoint && chargingPoint.power_output) {
                estimatedDuration = (estimatedEnergy / chargingPoint.power_output) * 60;
            }
        } else {
            estimatedDuration = finalValue;
            if (plan.price_per_minute) {
                baseAmount = estimatedDuration * plan.price_per_minute;
                calculationLabel = `Tarif temps:`;
                calculationDetails = `${estimatedDuration} min × ${plan.price_per_minute.toFixed(2)} ${plan.currency || 'EUR'}/min`;
            }
            if (chargingPoint && chargingPoint.power_output) {
                estimatedEnergy = chargingPoint.power_output * (estimatedDuration / 60);
            }
        }

        // Calculs des frais et taxes
        const activationFeeAmount = plan.activation_fee || 0;
        const subtotalHT = baseAmount + activationFeeAmount;
        const vatRate = (plan.vatRate?.rate || 0) / 100;
        const vatAmount = subtotalHT * vatRate;
        const totalTTC = subtotalHT + vatAmount;

        // Calcul du pourcentage de la limite
        let limitPercentage = 0;
        if (plan.max_duration && type === 'minute') {
            limitPercentage = Math.round((estimatedDuration / plan.max_duration) * 100);
        } else if (plan.max_duration && type === 'kwh' && chargingPoint && chargingPoint.power_output) {
            // Check if there's a specific max energy limit in the pricing plan
            const maxEnergy = plan.max_energy || (plan.max_duration / 60) * chargingPoint.power_output;
            limitPercentage = Math.round((estimatedEnergy / maxEnergy) * 100);
        }

        // Afficher le calcul détaillé en temps réel basé sur les sélections utilisateur
        costEstimation.innerHTML = `
            <div class="grid gap-4">
                <div class="bg-white border rounded-2xl p-4 dark:bg-slate-900 dark:border-slate-800">
                    <div class="grid sm:grid-cols-2 gap-3 text-sm">
                        <div class="flex justify-between"><span class="text-gray-600">Type</span><span class="font-semibold">${type==='kwh'?'Par Énergie':'Par Durée'}</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">${type==='kwh'?'Énergie':'Durée'}</span><span class="font-semibold">${type==='kwh'?finalValue+' kWh':finalValue+' min'}</span></div>
                        ${estimatedDuration?`<div class="flex justify-between"><span class="text-gray-600">Durée calculée</span><span class="font-semibold">${Math.round(estimatedDuration)} min</span></div>`:''}
                        ${estimatedEnergy?`<div class="flex justify-between"><span class="text-gray-600">Énergie calculée</span><span class="font-semibold">${estimatedEnergy.toFixed(1)} kWh</span></div>`:''}
                    </div>
                </div>

                <div class="bg-white border rounded-2xl p-4 dark:bg-slate-900 dark:border-slate-800">
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <div class="text-gray-600">
                                <div>${calculationLabel}</div>
                                <div class="text-xs text-gray-500">${calculationDetails}</div>
                            </div>
                            <span class="font-semibold">${baseAmount.toFixed(2)} ${plan.currency||'EUR'}</span>
                        </div>
                        ${activationFeeAmount>0?`<div class="flex justify-between"><span class="text-gray-600">Frais d'activation</span><span class="font-semibold">${activationFeeAmount.toFixed(2)} ${plan.currency||'EUR'}</span></div>`:''}
                        <div class="flex justify-between border-t pt-2"><span class="font-semibold">Sous-total (HT)</span><span class="font-semibold">${subtotalHT.toFixed(2)} ${plan.currency||'EUR'}</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">TVA (${(plan.vatRate?.rate || 0).toFixed(1)}%)</span><span class="font-semibold">${vatAmount.toFixed(2)} ${plan.currency||'EUR'}</span></div>
                        <div class="flex justify-between items-center border-t pt-2">
                            <span class="text-lg font-bold">Total TTC</span>
                            <span class="text-lg font-bold text-indigo-600">${totalTTC.toFixed(2)} ${plan.currency||'EUR'}</span>
                        </div>
                    </div>
                </div>
            </div>`;
    }

    // Gestion des cartes de sélection
    function updateSelectionCards() {
        // Mettre à jour les cartes de type de réservation
        document.querySelectorAll('.reservation-type-card').forEach(card => {
            card.classList.remove('border-emerald-500', 'border-blue-500', 'bg-emerald-50', 'bg-blue-50');
            card.classList.add('border-gray-200');
        });
        
        const selectedType = document.querySelector('input[name="reservation_type"]:checked');
        if (selectedType) {
            const card = selectedType.closest('label').querySelector('.reservation-type-card');
            card.classList.remove('border-gray-200');
            if (selectedType.value === 'minute') {
                card.classList.add('border-emerald-500', 'bg-emerald-50');
            } else {
                card.classList.add('border-blue-500', 'bg-blue-50');
            }
        }

        // Mettre à jour les cartes de type de paiement
        document.querySelectorAll('.payment-type-card').forEach(card => {
            card.classList.remove('border-emerald-500', 'border-blue-500', 'bg-emerald-50', 'bg-blue-50');
            card.classList.add('border-gray-200');
        });
        
        const selectedPayment = document.querySelector('input[name="payment_type"]:checked');
        if (selectedPayment) {
            const card = selectedPayment.closest('label').querySelector('.payment-type-card');
            card.classList.remove('border-gray-200');
            if (selectedPayment.value === 'offline') {
                card.classList.add('border-emerald-500', 'bg-emerald-50');
            } else {
                card.classList.add('border-blue-500', 'bg-blue-50');
            }
        }
    }

            // Soumission du formulaire
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            console.log('Form submission started');
            
            const type = document.querySelector('input[name="reservation_type"]:checked')?.value || '{{ $hasKwhPricing && !$hasMinutePricing ? "kwh" : "minute" }}';
            const value = reservationValueInput.value;
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
            const guestEmail = document.getElementById('guest_email').value;
            const guestPhone = document.getElementById('guest_phone').value;
            
            console.log('Form data:', {
                type: type,
                value: value,
                paymentMethod: paymentMethod,
                guestEmail: guestEmail,
                guestPhone: guestPhone
            });

            // Validation
            console.log('Starting validation...');
            
            if (!value || value <= 0) {
                console.log('Validation failed: invalid value');
                showError('Veuillez saisir une valeur valide.');
                return;
            }

            if (!guestEmail && !guestPhone) {
                console.log('Validation failed: missing contact info');
                showError('Veuillez fournir un email ou un numéro de téléphone.');
                return;
            }
            
            console.log('Validation passed');

            // Validation des limites
            console.log('Checking plan limits...');
            const plan = @json($plan ?? null);
            if (plan) {
                if (type === 'minute' && plan.max_duration && parseFloat(value) > plan.max_duration) {
                    console.log('Validation failed: duration limit exceeded');
                    showError(`La durée de réservation ne peut pas dépasser ${plan.max_duration} minutes.`);
                    return;
                } else if (type === 'kwh' && plan.max_duration && @json($chargingPoint->power_output)) {
                    // Check if there's a specific max energy limit in the pricing plan
                    const maxEnergy = plan.max_energy || (@json($chargingPoint->power_output) * (plan.max_duration / 60));
                    if (parseFloat(value) > maxEnergy) {
                        console.log('Validation failed: energy limit exceeded');
                        showError(`La quantité d'énergie ne peut pas dépasser ${maxEnergy.toFixed(2)} kWh.`);
                        return;
                    }
                }
            }
            console.log('Plan limits validation passed');

            // Désactiver le bouton et démarrer l'animation
            console.log('Disabling submit button...');
            submitBtn.disabled = true;
            submitText.classList.add('hidden');
            submitLoading.classList.remove('hidden');
            
            // Animer le chargeur : started -> charging
            const chargerContainer = document.querySelector('.charger-3d-container');
            if (chargerContainer && chargerContainer.__x) {
                chargerContainer.__x.$data.state = 'started';
                setTimeout(() => {
                    chargerContainer.__x.$data.state = 'charging';
                }, 500);
            }

            // Préparer les données du formulaire
            console.log('Preparing form data...');
            const formData = new FormData(form);
            
            // Ajouter les données spécifiques au type
            if (type === 'kwh') {
                formData.append('energy_kwh', value);
            } else {
                formData.append('duration_minutes', value);
            }

            // Vérifier le token CSRF
            const csrfToken = '{{ csrf_token() }}';
            if (!csrfToken) {
                console.log('CSRF token missing');
                showError('Erreur de sécurité: Token CSRF manquant.');
                return;
            }
            
            console.log('CSRF token verified');
            
            console.log('Sending reservation request:', {
                url: `{{ route('reservations.store', ['chargingPoint' => $chargingPoint->id]) }}`,
                data: Object.fromEntries(formData.entries())
            });
            
            // Envoyer la requête avec timeout et retry
            console.log('Making fetch request...');
            
            // Créer un AbortController pour gérer le timeout
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 secondes timeout
            
            fetch(`{{ route('reservations.store', ['chargingPoint' => $chargingPoint->id]) }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData,
                signal: controller.signal
            })
            .then(response => {
                // Annuler le timeout car la réponse est arrivée
                clearTimeout(timeoutId);
                
                console.log('Response received:', response);
                console.log('Response status:', response.status);
                console.log('Response redirected:', response.redirected);
                if (response.redirected) {
                    console.log('Redirecting to:', response.url);
                    window.location.href = response.url;
                    return null;
                }
                // Check if response is a redirect (status 302)
                if (response.status === 302) {
                    const redirectUrl = response.headers.get('Location');
                    if (redirectUrl) {
                        console.log('Redirecting to:', redirectUrl);
                        window.location.href = redirectUrl;
                        return null;
                    }
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data) {
                    console.log('Processing response data...');
                    if (data.success && data.message && !data.errors) {
                        console.log('Reservation created successfully');
                        
                        // Gérer le paiement selon la méthode choisie
                        if (paymentMethod === 'stripe' && data.reservation && data.reservation.id) {
                            handleStripePayment(data.reservation.id);
                        } else if (paymentMethod === 'cmi' && data.reservation && data.reservation.id) {
                            handleCmiPayment(data.reservation.id);
                        } else {
                            // Paiement par crédit/solde : mise à jour solde avant redirection thank-you
                            if (data.remaining_balance !== undefined || data.formatted_balance) {
                                const balance = data.remaining_balance !== undefined ? data.remaining_balance : parseFloat(data.formatted_balance) || 0;
                                const formatted = data.formatted_balance || (balance.toFixed(2) + ' EUR');
                                document.dispatchEvent(new CustomEvent('walletBalanceUpdated', {
                                    detail: { balance: balance, formatted: formatted, remaining_balance: balance, currency: 'EUR' },
                                    bubbles: true
                                }));
                                window.dispatchEvent(new CustomEvent('walletBalanceUpdated', {
                                    detail: { balance: balance, formatted: formatted, remaining_balance: balance, currency: 'EUR' },
                                    bubbles: true
                                }));
                            }
                            const thankYouUrl = data.redirect_url || (data.reservation_id && '/reservations/thank-you/' + data.reservation_id) || (data.reservation?.id && '/reservations/thank-you/' + data.reservation.id);
                            if (thankYouUrl) {
                                window.location.href = thankYouUrl;
                            } else {
                                window.location.href = '/reservations';
                            }
                        }
                    } else {
                        // Gestion spéciale pour l'erreur de limite
                        if (data.error === 'limit_exceeded') {
                            const errorHtml = '<div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">' +
                                '<div class="flex items-center mb-2">' +
                                '<i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>' +
                                '<h4 class="text-sm font-medium text-red-800">Limite dépassée</h4>' +
                                '</div>' +
                                '<div class="text-sm text-red-700">' +
                                '<div class="mb-1">• ' + (data.message || 'La durée de réservation ne peut pas dépasser la limite autorisée.') + '</div>' +
                                '<div class="mb-1">• Veuillez réduire la durée ou la quantité d\'énergie de votre réservation.</div>' +
                                '</div></div>';
                            document.getElementById('reservation-errors').innerHTML = errorHtml;
                        } else {
                            // Gestion spéciale des erreurs de connexion
                            if (data.error === 'database_connection_error' || data.error === 'connection_error') {
                                showConnectionError(
                                    'Erreur de connexion',
                                    data.message || 'Problème de connexion au serveur. Le serveur peut être temporairement indisponible.'
                                );
                            } else if (data.error === 'timeout_error') {
                                showConnectionError(
                                    'Délai d\'attente dépassé',
                                    data.message || 'Le serveur met trop de temps à répondre. Veuillez réessayer.'
                                );
                            } else {
                                let errors = '';
                                if (data.errors) {
                                    errors = '<div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">';
                                    errors += '<div class="flex items-center mb-2">';
                                    errors += '<i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>';
                                    errors += '<h4 class="text-sm font-medium text-red-800">Erreurs de validation</h4>';
                                    errors += '</div>';
                                    errors += '<div class="text-sm text-red-700">';
                                    for (const key in data.errors) {
                                        errors += '<div class="mb-1">• ' + data.errors[key][0] + '</div>';
                                    }
                                    errors += '</div></div>';
                                } else if (data.message) {
                                    errors = '<div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">';
                                    errors += '<div class="flex items-center">';
                                    errors += '<i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>';
                                    errors += '<span class="text-sm text-red-700">' + data.message + '</span>';
                                    if (data.details) {
                                        errors += '<div class="text-xs text-red-600 mt-1">' + data.details + '</div>';
                                    }
                                    errors += '</div></div>';
                                }
                                document.getElementById('reservation-errors').innerHTML = errors;
                            }
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                console.error('Error details:', {
                    name: error.name,
                    message: error.message,
                    stack: error.stack
                });
                
                // Gestion spécifique des erreurs de connexion
                let errorMessage = 'Erreur lors de la création de la réservation.';
                let errorDetails = '';
                
                if (error.name === 'AbortError') {
                    errorMessage = 'Délai d\'attente dépassé';
                    errorDetails = 'La requête a pris trop de temps (plus de 30 secondes). Le serveur peut être surchargé.';
                } else if (error.name === 'TypeError' && error.message.includes('fetch')) {
                    errorMessage = 'Erreur de connexion';
                    errorDetails = 'Impossible de se connecter au serveur. Vérifiez votre connexion internet et réessayez.';
                } else if (error.message.includes('timeout')) {
                    errorMessage = 'Délai d\'attente dépassé';
                    errorDetails = 'Le serveur met trop de temps à répondre. Veuillez réessayer.';
                } else if (error.message.includes('network') || error.message.includes('Failed to fetch')) {
                    errorMessage = 'Erreur réseau';
                    errorDetails = 'Problème de connexion réseau. Vérifiez votre connexion internet.';
                } else if (error.message.includes('CORS')) {
                    errorMessage = 'Erreur de sécurité';
                    errorDetails = 'Problème de configuration de sécurité. Contactez le support technique.';
                }
                
                showConnectionError(errorMessage, errorDetails);
            })
            .finally(() => {
                // Réactiver le bouton
                submitBtn.disabled = false;
                submitText.classList.remove('hidden');
                submitLoading.classList.add('hidden');
            });
        });

    // Fonction pour afficher les erreurs
    function showError(message) {
        showCustomAlert({
            type: 'error',
            title: 'Erreur de réservation',
            message: message,
            primaryBtn: {
                text: 'OK',
                action: () => hideCustomAlert()
            }
        });
    }

    // Fonction pour afficher les erreurs de connexion avec retry
    function showConnectionError(title, details) {
        showCustomAlert({
            type: 'error',
            title: title,
            message: details,
            bodyContent: '<div class="text-sm text-gray-600 mt-2"><i class="fas fa-wifi mr-2"></i>Vérifiez votre connexion internet et réessayez.</div>',
            primaryBtn: {
                text: 'Réessayer',
                action: () => {
                    hideCustomAlert();
                    // Retry la soumission du formulaire
                    setTimeout(() => {
                        form.dispatchEvent(new Event('submit'));
                    }, 1000);
                }
            },
            secondaryBtn: {
                text: 'Annuler',
                action: () => hideCustomAlert()
            }
        });
    }



    // Événements pour mise à jour en temps réel du calcul
    reservationTypeInputs.forEach(input => {
        input.addEventListener('change', function() {
            updateReservationType();
            updateSelectionCards();
            setTimeout(() => {
                updateEstimations(); // Mise à jour immédiate du calcul
            }, 100);
        });
    });

    document.querySelectorAll('input[name="payment_type"]').forEach(input => {
        input.addEventListener('change', function() {
            updateSelectionCards();
            setTimeout(() => {
                updateEstimations(); // Mise à jour immédiate du calcul
            }, 100);
        });
    });

    reservationValueInput.addEventListener('input', function() {
        // Validation en temps réel des limites
        validateLimits();
        setTimeout(() => {
            updateEstimations(); // Mise à jour immédiate de l'estimation
        }, 100);
    });

    // Mise à jour du calcul lors des changements de contact
    document.getElementById('guest_email').addEventListener('input', function() {
        // Optionnel: mettre à jour le calcul si nécessaire
        // updateEstimations();
    });

    document.getElementById('guest_phone').addEventListener('input', function() {
        // Optionnel: mettre à jour le calcul si nécessaire
        // updateEstimations();
    });
    
    // Fonction de validation des limites en temps réel
    function validateLimits() {
        const type = document.querySelector('input[name="reservation_type"]:checked')?.value || '{{ $hasKwhPricing && !$hasMinutePricing ? "kwh" : "minute" }}';
        const value = parseFloat(reservationValueInput.value) || 0;
        const plan = @json($plan ?? null);
        const chargingPoint = @json($chargingPoint ?? null);
        
        // Réinitialiser les styles
        reservationValueInput.classList.remove('border-red-500', 'ring-red-500');
        reservationValueInput.classList.add('border-gray-300', 'focus:ring-purple-500');
        
        // Supprimer les messages d'erreur précédents
        const existingError = document.getElementById('limit-error-message');
        if (existingError) {
            existingError.remove();
        }
        
        if (plan && plan.max_duration) {
            let isValid = true;
            let errorMessage = '';
            
            if (type === 'minute' && value > plan.max_duration) {
                isValid = false;
                errorMessage = `La durée ne peut pas dépasser ${plan.max_duration} minutes.`;
            } else if (type === 'kwh' && chargingPoint && chargingPoint.power_output) {
                // Check if there's a specific max energy limit in the pricing plan
                const maxEnergy = plan.max_energy || ((plan.max_duration / 60) * chargingPoint.power_output);
                if (value > maxEnergy) {
                    isValid = false;
                    errorMessage = `L'énergie ne peut pas dépasser ${maxEnergy.toFixed(1)} kWh (${plan.max_duration} min max).`;
                }
            }
            
            if (!isValid) {
                // Appliquer les styles d'erreur
                reservationValueInput.classList.remove('border-gray-300', 'focus:ring-purple-500');
                reservationValueInput.classList.add('border-red-500', 'ring-red-500');
                
                // Afficher le message d'erreur
                const errorDiv = document.createElement('div');
                errorDiv.id = 'limit-error-message';
                errorDiv.className = 'mt-2 text-sm text-red-600 flex items-center';
                errorDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + errorMessage;
                reservationValueInput.parentNode.appendChild(errorDiv);
            }
        }
    }

    // Initialisation
    updateReservationType();
    updateSelectionCards();
    
    // Initialiser le calcul au chargement
    setTimeout(() => {
        updateEstimations();
    }, 500);
    








    // Fonctions pour le popup d'alerte moderne
    function showCustomAlert(options) {
        const overlay = document.getElementById('customAlertOverlay');
        const icon = document.getElementById('customAlertIcon');
        const iconClass = document.getElementById('customAlertIconClass');
        const title = document.getElementById('customAlertTitle');
        const message = document.getElementById('customAlertMessage');
        const bodyContent = document.getElementById('customAlertBodyContent');
        const primaryBtn = document.getElementById('customAlertPrimaryBtn');
        const secondaryBtn = document.getElementById('customAlertSecondaryBtn');

        // Configuration de l'icône
        icon.className = `flex justify-center mb-4 ${options.type}`;
        
        // Configuration de l'icône selon le type
        switch(options.type) {
            case 'success':
                iconClass.className = 'fas fa-check text-green-500 text-3xl';
                break;
            case 'error':
                iconClass.className = 'fas fa-exclamation-triangle text-red-500 text-3xl';
                break;
            case 'warning':
                iconClass.className = 'fas fa-exclamation-circle text-yellow-500 text-3xl';
                break;
            default:
                iconClass.className = 'fas fa-info-circle text-blue-500 text-3xl';
        }

        // Configuration du contenu
        title.textContent = options.title || '';
        message.textContent = options.message || '';
        bodyContent.innerHTML = options.bodyContent || '';

        // Configuration des boutons
        if (options.primaryBtn) {
            primaryBtn.textContent = options.primaryBtn.text;
            primaryBtn.onclick = options.primaryBtn.action;
            primaryBtn.style.display = 'block';
        } else {
            primaryBtn.style.display = 'none';
        }

        if (options.secondaryBtn) {
            secondaryBtn.textContent = options.secondaryBtn.text;
            secondaryBtn.onclick = options.secondaryBtn.action;
            secondaryBtn.style.display = 'block';
        } else {
            secondaryBtn.style.display = 'none';
        }

        // Afficher le popup
        overlay.classList.remove('hidden');
        
        // Empêcher le scroll du body
        document.body.style.overflow = 'hidden';
    }

    function hideCustomAlert() {
        const overlay = document.getElementById('customAlertOverlay');
        overlay.classList.add('hidden');
        
        // Restaurer le scroll du body
        document.body.style.overflow = '';
    }

    // Fermer le popup en cliquant sur l'overlay
    document.getElementById('customAlertOverlay').addEventListener('click', function(e) {
        if (e.target === this) {
            hideCustomAlert();
        }
    });

    // Fermer le popup avec la touche Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideCustomAlert();
        }
    });

    // Fonctions de gestion des paiements
    function handleStripePayment(reservationId) {
        console.log('Initiating Stripe payment for reservation:', reservationId);
        
        // Afficher un indicateur de chargement
        showCustomAlert({
            type: 'info',
            title: 'Initialisation du paiement',
            message: 'Préparation du paiement Stripe...',
            primaryBtn: {
                text: 'OK',
                action: () => hideCustomAlert()
            }
        });

        // Appeler l'API Stripe
        fetch(`/reservations/${reservationId}/pay/stripe`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.client_secret) {
                // Initialiser Stripe Elements
                initializeStripePayment(data.client_secret, reservationId);
            } else {
                showError('Erreur lors de l\'initialisation du paiement Stripe: ' + (data.error || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('Stripe payment error:', error);
            showError('Erreur lors de l\'initialisation du paiement Stripe');
        });
    }

    function handleCmiPayment(reservationId) {
        console.log('Initiating CMI payment for reservation:', reservationId);
        
        // Afficher un indicateur de chargement
        showCustomAlert({
            type: 'info',
            title: 'Initialisation du paiement',
            message: 'Préparation du paiement CMI...',
            primaryBtn: {
                text: 'OK',
                action: () => hideCustomAlert()
            }
        });

        // Appeler l'API CMI
        fetch(`/reservations/${reservationId}/pay/cmi`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.redirect_url) {
                // Rediriger vers CMI
                window.location.href = data.redirect_url;
            } else {
                showError('Erreur lors de l\'initialisation du paiement CMI: ' + (data.error || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('CMI payment error:', error);
            showError('Erreur lors de l\'initialisation du paiement CMI');
        });
    }

    function initializeStripePayment(clientSecret, reservationId) {
        // Charger Stripe.js si pas déjà chargé
        if (typeof Stripe === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://js.stripe.com/v3/';
            script.onload = () => {
                processStripePayment(clientSecret, reservationId);
            };
            document.head.appendChild(script);
        } else {
            processStripePayment(clientSecret, reservationId);
        }
    }

    function processStripePayment(clientSecret, reservationId) {
        const stripe = Stripe('{{ config("services.stripe.key") }}');
        
        // Créer un formulaire de paiement simple
        const paymentForm = document.createElement('div');
        paymentForm.innerHTML = `
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                    <h3 class="text-lg font-semibold mb-4">Paiement Stripe</h3>
                    <div id="stripe-payment-element"></div>
                    <div class="flex justify-end gap-3 mt-4">
                        <button id="stripe-cancel" class="px-4 py-2 bg-gray-300 rounded">Annuler</button>
                        <button id="stripe-pay" class="px-4 py-2 bg-blue-600 text-white rounded">Payer</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(paymentForm);
        
        // Initialiser Stripe Elements
        const elements = stripe.elements({
            clientSecret: clientSecret
        });
        
        const paymentElement = elements.create('payment');
        paymentElement.mount('#stripe-payment-element');
        
        // Gérer le paiement
        document.getElementById('stripe-pay').addEventListener('click', async () => {
            const {error} = await stripe.confirmPayment({
                elements,
                confirmParams: {
                    return_url: `${window.location.origin}/reservations/thank-you/${reservationId}`
                }
            });
            
            if (error) {
                showError('Erreur de paiement: ' + error.message);
            }
        });
        
        // Annuler
        document.getElementById('stripe-cancel').addEventListener('click', () => {
            paymentForm.remove();
        });
    }





    // Micro-interactions & A11y (JS minimal)
    // Toggle-card active state (if you don't use the group selector trick everywhere)
    function syncToggleCards(){
        document.querySelectorAll('input[name="reservation_type"]').forEach(inp=>{
            const card = inp.closest('label')?.querySelector('.toggle-card');
            if(card){ card.classList.toggle('is-active', inp.checked); }
        });
        document.querySelectorAll('input[name="payment_type"]').forEach(inp=>{
            const card = inp.closest('label')?.querySelector('.toggle-card');
            if(card){ card.classList.toggle('is-active', inp.checked); }
        });
    }
    document.querySelectorAll('input[name="reservation_type"],input[name="payment_type"]').forEach(i=>i.addEventListener('change', syncToggleCards));
    syncToggleCards();

    // Enhanced duration selector with 10-minute intervals
    (function(){
      // --- Options ---
      const STEP = 10;                              // 10-minute intervals
      const MIN  = 10;                              // 10 min minimum
      const MAX  = Number({{ $plan->max_duration ?? 120 }});      // ex: 120
      const START= Number({{ old('reservation_value', 30) }});  // ex: 30

      // --- Elements ---
      const timeline = document.getElementById('duration-timeline');
      const track    = document.getElementById('duration-track');
      const cursor   = document.getElementById('duration-cursor');
      const display  = document.getElementById('duration-display');
      const hidden   = document.getElementById('reservation_value');

      // Debug - vérifier que tous les éléments sont trouvés
      console.log('🔍 Vérification des éléments du sélecteur de durée:');
      console.log('Timeline:', timeline);
      console.log('Track:', track);
      console.log('Cursor:', cursor);
      console.log('Display:', display);
      console.log('Hidden:', hidden);

      // Vérifier que tous les éléments sont présents
      if (!timeline || !track || !cursor || !display || !hidden) {
        console.error('❌ Certains éléments du sélecteur de durée sont manquants');
        return;
      }

      // Helpers
      const clamp = (v, min, max) => Math.max(min, Math.min(max, v));
      const snap  = (v, step)      => Math.round(v/step)*step;

      function valueToPercent(v){ return (v - MIN) / (MAX - MIN) * 100; }
      function percentToValue(p){ return MIN + p/100 * (MAX - MIN); }

      function setValue(newValue, triggerChange=true){
        let v = snap(clamp(newValue, MIN, MAX), STEP);
        // UI
        const pct = valueToPercent(v);
        track.style.width = pct + '%';
        cursor.style.left = `calc(${pct}% - 10px)`; // 10px = moitié du curseur (20px)
        display.textContent = `${v} min`;
        // ARIA
        cursor.setAttribute('aria-valuenow', v);
        cursor.setAttribute('aria-valuetext', `${v} minutes`);
        // Form
        if (hidden) hidden.value = v;
        // Callback (calcul)
        if (triggerChange && typeof window.updateEstimations === 'function'){
          window.updateEstimations();
        }
      }

      function setFromPosition(clientX){
        const rect = timeline.getBoundingClientRect();
        const x = clamp(clientX - rect.left, 0, rect.width);
        const pct = (x / rect.width) * 100;
        setValue(percentToValue(pct));
      }

      // Init - ensure START value snaps to 10-minute interval
      const initialValue = Math.max(MIN, Math.round((START || 30) / STEP) * STEP);
      setValue(initialValue, false);
      
      // Forcer l'affichage du curseur
      if (cursor) {
        cursor.style.display = 'block';
        cursor.style.visibility = 'visible';
        cursor.style.opacity = '1';
        cursor.style.zIndex = '10';
        console.log('✅ Curseur forcé visible');
      }

      // Mouse
      let dragging = false;
      timeline.addEventListener('mousedown', (e)=>{ dragging = true; setFromPosition(e.clientX); });
      document.addEventListener('mousemove', (e)=>{ if (!dragging) return; setFromPosition(e.clientX); });
      document.addEventListener('mouseup',   ()=>{ dragging = false; });

      // Touch
      timeline.addEventListener('touchstart', (e)=>{
        dragging = true;
        setFromPosition(e.touches[0].clientX);
      }, {passive:true});
      document.addEventListener('touchmove', (e)=>{
        if (!dragging) return;
        setFromPosition(e.touches[0].clientX);
      }, {passive:true});
      document.addEventListener('touchend', ()=>{ dragging = false; });

      // Keyboard (cursor is focusable)
      cursor.addEventListener('keydown', (e)=>{
        let cur = Number(hidden.value || START || MIN);
        if (e.key === 'ArrowRight' || e.key === 'ArrowUp'){ e.preventDefault(); setValue(cur + STEP); }
        if (e.key === 'ArrowLeft'  || e.key === 'ArrowDown'){ e.preventDefault(); setValue(cur - STEP); }
        if (e.key === 'Home'){ e.preventDefault(); setValue(MIN); }
        if (e.key === 'End'){ e.preventDefault(); setValue(MAX); }
      });

      // Cliquer n'importe où sur la timeline
      timeline.addEventListener('click', (e)=>{
        // éviter double set si on vient du drag start
        if (!dragging) setFromPosition(e.clientX);
      });

      // Intégration avec tes radios (minute/kwh)
      // Si tu as les deux types, on peut masquer/afficher le slider selon le type choisi.
      const typeRadios = document.querySelectorAll('input[name="reservation_type"]');
      function syncTypeVisibility(){
        const type = document.querySelector('input[name="reservation_type"]:checked')?.value || 'minute';
        const section = document.getElementById('duration-section');
        if (!section) return;
        if (type === 'minute'){
          section.style.display = '';
        } else {
          section.style.display = 'none';
        }
        if (typeof window.updateEstimations === 'function'){
          window.updateEstimations();
        }
      }
      typeRadios.forEach(r => r.addEventListener('change', syncTypeVisibility));
      syncTypeVisibility();

      // Texte d'aide
      const help = document.getElementById('value_help');
      if (help){
        help.textContent = 'Glissez le curseur pour choisir la durée (intervalles de 10 min)';
      }
    })();

});
</script>
@endsection