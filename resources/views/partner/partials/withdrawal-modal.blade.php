@php
    $displayCurrency = $balance['currency'] ?? ($appCurrency ?? 'EUR');
@endphp
<div class="modal fade" id="withdrawalModal" tabindex="-1" aria-labelledby="withdrawalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="withdrawalModalLabel">
                    <i class="fas fa-wallet me-2"></i>
                    {{ __('withdrawals.request_withdrawal') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('partner.withdrawals.store') }}" id="withdrawalForm">
                @csrf
                <div class="modal-body">
                    <!-- Available Balance Info -->
                    <div class="alert alert-info mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>{{ __('wallet.available_to_withdraw') }}</span>
                            <span class="fw-bold fs-5">{{ number_format($balance['available'] ?? 0, 2) }} {{ $displayCurrency }}</span>
                        </div>
                    </div>

                    <!-- Amount Field -->
                    <div class="mb-3">
                        <label for="amount" class="form-label">
                            {{ __('fields.amount') }} <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="number" 
                                   class="form-control form-control-lg" 
                                   id="amount" 
                                   name="amount" 
                                   step="0.01" 
                                   min="1" 
                                   max="{{ $balance['available'] ?? 0 }}"
                                   placeholder="0.00"
                                   required>
                            <span class="input-group-text">{{ $displayCurrency }}</span>
                        </div>
                        <div class="form-text">
                            {{ __('wallet.max_withdrawal') }}: {{ number_format($balance['available'] ?? 0, 2) }} {{ $displayCurrency }}
                        </div>
                        @error('amount')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Bank Account Reminder -->
                    <div class="alert alert-warning mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>{{ __('withdrawals.bank_reminder') }}</strong>
                        <p class="mb-0 mt-2 small">
                            {{ __('withdrawals.bank_account_note') }}
                        </p>
                    </div>

                    <!-- Confirmation Step -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirmWithdrawal" required>
                        <label class="form-check-label" for="confirmWithdrawal">
                            {{ __('withdrawals.confirmation_text') }}
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        {{ __('actions.cancel') }}
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitWithdrawal" disabled>
                        <i class="fas fa-paper-plane me-1"></i>
                        {{ __('withdrawals.submit_request') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const withdrawalCurrency = @json($displayCurrency);
        const confirmCheckbox = document.getElementById('confirmWithdrawal');
        const submitBtn = document.getElementById('submitWithdrawal');
        const amountInput = document.getElementById('amount');
        
        // Enable/disable submit button based on confirmation
        confirmCheckbox.addEventListener('change', function() {
            submitBtn.disabled = !this.checked || !amountInput.value || parseFloat(amountInput.value) <= 0;
        });

        // Validate amount on input
        amountInput.addEventListener('input', function() {
            const max = parseFloat(this.max);
            const value = parseFloat(this.value);
            
            if (value > max) {
                this.setCustomValidity('Le montant ne peut pas dépasser le solde disponible');
            } else if (value <= 0 || isNaN(value)) {
                this.setCustomValidity('Veuillez entrer un montant valide');
            } else {
                this.setCustomValidity('');
            }
            
            submitBtn.disabled = !confirmCheckbox.checked || !this.value || value <= 0;
        });

        // Form validation
        document.getElementById('withdrawalForm').addEventListener('submit', function(e) {
            const amount = parseFloat(amountInput.value);
            const max = parseFloat(amountInput.max);
            
            if (amount > max) {
                e.preventDefault();
                alert('Le montant ne peut pas dépasser le solde disponible de ' + max + ' ' + withdrawalCurrency);
                return false;
            }
            
            if (!confirm('Êtes-vous sûr de vouloir demander un retrait de ' + amount + ' ' + withdrawalCurrency + '?')) {
                e.preventDefault();
                return false;
            }
        });
    });
</script>
@endpush
