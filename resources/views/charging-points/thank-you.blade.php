@extends('layouts.app')

@section('title', 'Réservation Confirmée')

@push('styles')
<style>
    /* ============================================
       SUCCESS PAGE DESIGN SYSTEM
       ============================================ */
    
    :root {
        --success-primary: #10b981;
        --success-dark: #059669;
        --warning-primary: #f59e0b;
        --warning-dark: #d97706;
        --blue-primary: #3b82f6;
        --blue-dark: #2563eb;
        --gray-50: #f9fafb;
        --gray-100: #f3f4f6;
        --gray-600: #4b5563;
        --gray-900: #111827;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        -webkit-font-smoothing: antialiased;
    }

    /* Container */
    .success-container {
        min-height: 100vh;
        background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 50%, #eff6ff 100%);
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Main Card */
    .success-card {
        max-width: 800px;
        width: 100%;
        background: white;
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        animation: slideUp 0.6s ease-out;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Header Section */
    .success-header {
        background: linear-gradient(135deg, var(--success-primary) 0%, var(--success-dark) 100%);
        padding: 48px 32px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .success-header.pending {
        background: linear-gradient(135deg, var(--warning-primary) 0%, var(--warning-dark) 100%);
    }

    .success-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: pulse 3s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); opacity: 0.5; }
        50% { transform: scale(1.1); opacity: 0.8; }
    }

    .success-icon {
        width: 96px;
        height: 96px;
        background: white;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 24px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        animation: bounceIn 0.8s ease-out;
        position: relative;
        z-index: 1;
    }

    @keyframes bounceIn {
        0% { transform: scale(0); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    .success-icon svg {
        width: 48px;
        height: 48px;
    }

    .success-icon.success svg {
        color: var(--success-primary);
    }

    .success-icon.pending svg {
        color: var(--warning-primary);
    }

    .success-title {
        font-size: 32px;
        font-weight: 800;
        color: white;
        margin: 0 0 12px 0;
        position: relative;
        z-index: 1;
    }

    .success-subtitle {
        font-size: 18px;
        color: rgba(255, 255, 255, 0.95);
        margin: 0;
        position: relative;
        z-index: 1;
    }

    /* Content Section */
    .success-content {
        padding: 32px;
    }

    /* Info Card */
    .info-card {
        background: linear-gradient(135deg, var(--gray-50) 0%, white 100%);
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
    }

    .info-card-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0 0 20px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .info-card-title svg {
        width: 24px;
        height: 24px;
        color: var(--blue-primary);
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }

    .info-item {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .info-label {
        font-size: 13px;
        color: var(--gray-600);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-value {
        font-size: 16px;
        color: var(--gray-900);
        font-weight: 600;
    }

    /* Status Badge */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 100px;
        font-size: 14px;
        font-weight: 600;
    }

    .status-badge.pending {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        color: #92400e;
    }

    .status-badge.confirmed {
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        color: #065f46;
    }

    .status-badge svg {
        width: 18px;
        height: 18px;
    }

    /* Steps Card */
    .steps-card {
        background: linear-gradient(135deg, #fef3c7 0%, #fef9e7 100%);
        border: 2px solid #fde68a;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
    }

    .steps-card.confirmed {
        background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%);
        border-color: #93c5fd;
    }

    .steps-title {
        font-size: 18px;
        font-weight: 700;
        margin: 0 0 20px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .steps-card.pending .steps-title {
        color: #92400e;
    }

    .steps-card.confirmed .steps-title {
        color: #1e40af;
    }

    .steps-title svg {
        width: 24px;
        height: 24px;
    }

    .steps-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .step-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .step-number {
        flex-shrink: 0;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 14px;
        color: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .steps-card.pending .step-number {
        background: linear-gradient(135deg, var(--warning-primary) 0%, var(--warning-dark) 100%);
    }

    .steps-card.confirmed .step-number {
        background: linear-gradient(135deg, var(--blue-primary) 0%, var(--blue-dark) 100%);
    }

    .step-text {
        flex: 1;
        font-size: 15px;
        line-height: 1.6;
        padding-top: 4px;
    }

    .steps-card.pending .step-text {
        color: #78350f;
    }

    .steps-card.confirmed .step-text {
        color: #1e3a8a;
    }

    /* Note Box */
    .note-box {
        background: rgba(251, 191, 36, 0.1);
        border-left: 4px solid #f59e0b;
        border-radius: 8px;
        padding: 16px;
        margin-top: 16px;
    }

    .note-box.confirmed {
        background: rgba(59, 130, 246, 0.1);
        border-left-color: #3b82f6;
    }

    .note-box p {
        margin: 0;
        font-size: 13px;
        line-height: 1.6;
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }

    .note-box.pending p {
        color: #92400e;
    }

    .note-box.confirmed p {
        color: #1e40af;
    }

    .note-box i {
        flex-shrink: 0;
        margin-top: 2px;
    }

    /* QR Code Section */
    .qr-section {
        text-align: center;
        padding: 24px;
        background: linear-gradient(135deg, var(--gray-50) 0%, white 100%);
        border-radius: 16px;
        margin-bottom: 24px;
    }

    .qr-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0 0 20px 0;
    }

    .qr-container {
        display: inline-block;
        background: white;
        padding: 20px;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .qr-container img {
        width: 160px;
        height: 160px;
        display: block;
    }

    .qr-help {
        margin-top: 12px;
        font-size: 14px;
        color: var(--gray-600);
    }

    /* Action Buttons */
    .action-buttons {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 32px;
    }

    .btn {
        padding: 16px 24px;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 700;
        text-align: center;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        border: none;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--blue-primary) 0%, var(--blue-dark) 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
    }

    .btn-secondary {
        background: white;
        color: var(--gray-900);
        border: 2px solid #e5e7eb;
    }

    .btn-secondary:hover {
        background: var(--gray-50);
        transform: translateY(-2px);
    }

    /* Support Section */
    .support-section {
        text-align: center;
        padding: 24px;
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        border-radius: 12px;
        margin-bottom: 24px;
    }

    .support-title {
        font-size: 14px;
        color: var(--gray-600);
        margin: 0 0 12px 0;
    }

    .support-links {
        display: flex;
        justify-content: center;
        gap: 24px;
        flex-wrap: wrap;
    }

    .support-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--success-dark);
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        padding: 8px 16px;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .support-link:hover {
        background: white;
        transform: translateY(-1px);
    }

    /* Footer Note */
    .footer-note {
        text-align: center;
        padding: 16px;
    }

    .footer-note p {
        margin: 0;
        font-size: 13px;
        color: var(--gray-600);
        line-height: 1.6;
    }

    /* Countdown Timer */
    .countdown-timer {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border: 1px solid #93c5fd;
        border-radius: 12px;
        padding: 16px;
        text-align: center;
        margin-bottom: 24px;
    }

    .countdown-timer p {
        margin: 0;
        font-size: 14px;
        color: #1e40af;
    }

    .countdown-timer strong {
        font-size: 18px;
        font-weight: 700;
        color: var(--blue-dark);
    }

    /* Responsive Design */
    @media (max-width: 640px) {
        .success-container {
            padding: 12px;
        }

        .success-header {
            padding: 32px 20px;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            margin-bottom: 16px;
        }

        .success-icon svg {
            width: 40px;
            height: 40px;
        }

        .success-title {
            font-size: 24px;
        }

        .success-subtitle {
            font-size: 16px;
        }

        .success-content {
            padding: 20px;
        }

        .info-card,
        .steps-card {
            padding: 20px;
        }

        .info-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .action-buttons {
            grid-template-columns: 1fr;
        }

        .qr-container img {
            width: 120px;
            height: 120px;
        }

        .support-links {
            flex-direction: column;
            gap: 12px;
        }
    }

    /* Animation delays for staggered effect */
    .info-card { animation: fadeIn 0.6s ease-out 0.2s both; }
    .steps-card { animation: fadeIn 0.6s ease-out 0.3s both; }
    .qr-section { animation: fadeIn 0.6s ease-out 0.4s both; }
    .action-buttons { animation: fadeIn 0.6s ease-out 0.5s both; }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Print Styles */
    @media print {
        .success-container {
            background: white;
        }

        .action-buttons,
        .support-section,
        .countdown-timer {
            display: none;
        }
    }
</style>
@endpush

@section('content')
@php
    $statusValue = $reservation->status instanceof \App\Enums\ReservationStatus ? $reservation->status->value : (string) ($reservation->status ?? '');
    $isPending = ($statusValue === 'pending' || $statusValue === 'pending_confirmation') && !$reservation->isPaid();
@endphp
<div class="success-container">
    <div class="success-card">
        <!-- Header -->
        <div class="success-header {{ $isPending ? 'pending' : '' }}">
            <div class="success-icon {{ $isPending ? 'pending' : 'success' }}">
                @if($isPending)
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @else
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                @endif
            </div>
            
            @if($isPending)
                <h1 class="success-title">Réservation en attente !</h1>
                <p class="success-subtitle">Votre demande est en cours de vérification</p>
            @else
                <h1 class="success-title">Réservation confirmée !</h1>
                <p class="success-subtitle">Votre recharge peut commencer immédiatement</p>
            @endif
        </div>

        <!-- Content -->
        <div class="success-content">
            <!-- Reservation Details -->
            <div class="info-card">
                <h3 class="info-card-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Détails de votre réservation
                </h3>
                
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Borne de recharge</span>
                        <span class="info-value">{{ $reservation->chargingPoint->name ?? 'Borne de recharge' }}</span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Type de réservation</span>
                        <span class="info-value">
                            @if($reservation->reservation_type === 'duration' || $reservation->reservation_type === 'minute')
                                {{ $reservation->reservation_value }} minutes
                            @else
                                {{ $reservation->reservation_value }} kWh
                            @endif
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Montant</span>
                        <span class="info-value">{{ number_format($reservation->amount ?? 0, 2) }} EUR</span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Statut</span>
                        @if($isPending)
                            <span class="status-badge pending">
                                <svg fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                                En attente
                            </span>
                        @else
                            <span class="status-badge confirmed">
                                <svg fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                {{ $reservation->getDisplayStatusLabel() }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            @if($isPending)
                <!-- Pending Steps -->
                <div class="steps-card pending">
                    <h3 class="steps-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Processus d'approbation
                    </h3>
                    
                    <div class="steps-list">
                        <div class="step-item">
                            <div class="step-number">1</div>
                            <div class="step-text">Votre réservation a été soumise avec succès</div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">2</div>
                            <div class="step-text">Le propriétaire du point de charge examine votre demande</div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">3</div>
                            <div class="step-text">Vous recevrez une notification dès l'approbation</div>
                        </div>
                    </div>

                    <div class="note-box">
                        <p>
                            <i class="fas fa-info-circle"></i>
                            <span><strong>Note :</strong> Le processus d'approbation prend généralement quelques minutes. Vous serez notifié par email et SMS dès confirmation.</span>
                        </p>
                    </div>
                </div>

                <!-- Countdown Timer -->
                <div class="countdown-timer">
                    <p>
                        Temps estimé d'approbation : <strong id="countdown-timer">5:00</strong>
                    </p>
                </div>
            @else
                <!-- Confirmed Steps -->
                <div class="steps-card confirmed">
                    <h3 class="steps-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Prochaines étapes
                    </h3>
                    
                    <div class="steps-list">
                        <div class="step-item">
                            <div class="step-number">1</div>
                            <div class="step-text">Rendez-vous à la borne de recharge</div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">2</div>
                            <div class="step-text">Scannez le code QR ou entrez votre code de réservation</div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">3</div>
                            <div class="step-text">Connectez votre véhicule et la recharge commencera automatiquement</div>
                        </div>
                    </div>

                    <div class="note-box confirmed">
                        <p>
                            <i class="fas fa-bolt"></i>
                            <span><strong>Astuce :</strong> Assurez-vous que votre véhicule est correctement stationné et que le câble de charge est compatible avec la borne.</span>
                        </p>
                    </div>
                </div>
            @endif

            <!-- QR Code -->
            @if(isset($qrCode))
            <div class="qr-section">
                <h3 class="qr-title">Code QR pour accès rapide</h3>
                <div class="qr-container">
                    <img src="{{ $qrCode }}" alt="QR Code de réservation">
                </div>
                <p class="qr-help">Scannez ce code à la borne pour démarrer la recharge</p>
            </div>
            @endif

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="{{ route('charging-points.index') }}" class="btn btn-primary">
                    <i class="fas fa-charging-station"></i>
                    Voir toutes les bornes
                </a>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                    <i class="fas fa-home"></i>
                    Tableau de bord
                </a>
            </div>

            <!-- Support Section -->
            <div class="support-section">
                <p class="support-title">Besoin d'aide ?</p>
                <div class="support-links">
                    <a href="tel:+212537123456" class="support-link">
                        <i class="fas fa-phone"></i>
                        Appeler le support
                    </a>
                    <a href="mailto:support@evon.ma" class="support-link">
                        <i class="fas fa-envelope"></i>
                        Envoyer un email
                    </a>
                    <a href="{{ route('faq') }}" class="support-link">
                        <i class="fas fa-question-circle"></i>
                        Centre d'aide
                    </a>
                </div>
            </div>

            <!-- Footer Note -->
            <div class="footer-note">
                <p>
                    <i class="fas fa-check-circle" style="color: var(--success-primary);"></i>
                    Vous recevrez un email de confirmation avec tous les détails de votre réservation.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if($isPending)
        // Countdown timer for pending reservations
        let timeLeft = 300; // 5 minutes in seconds
        const timerElement = document.getElementById('countdown-timer');
        
        if (timerElement) {
            const countdown = setInterval(function() {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                
                if (timeLeft <= 0) {
                    clearInterval(countdown);
                    timerElement.textContent = 'En cours...';
                }
                
                timeLeft--;
            }, 1000);
        }

        // Auto-refresh every 30 seconds to check status
        const autoRefresh = setInterval(function() {
            // You can implement AJAX call here to check reservation status
            // and update the page without full reload
            console.log('Checking reservation status...');
        }, 30000);
        @endif

        // Print function
        window.printConfirmation = function() {
            window.print();
        };

        // Share function (if Web Share API is supported)
        window.shareConfirmation = function() {
            if (navigator.share) {
                navigator.share({
                    title: 'Réservation confirmée',
                    text: 'Ma réservation de borne de recharge a été confirmée!',
                    url: window.location.href
                });
            }
        };
    });
</script>
@endpush
