<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #00E676; padding-bottom: 20px; margin-bottom: 20px; }
        .logo { max-width: 150px; }
        .greeting { font-size: 18px; margin-bottom: 10px; }
        .highlight { font-weight: bold; color: #00E676; }
        .details-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .details-table th, .details-table td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
        .details-table th { background-color: #f9f9f9; color: #555; }
        .total-row td { font-weight: bold; font-size: 16px; border-top: 2px solid #333; }
        .footer { margin-top: 30px; font-size: 12px; color: #888; text-align: center; }
        .btn { display: inline-block; padding: 10px 20px; margin-top: 20px; background-color: #00E676; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Facture de votre session EVON</h2>
        </div>
        
        <p class="greeting">Bonjour {{ $invoice['user']['name'] }},</p>
        
        <p>Merci d'avoir utilisé notre réseau de recharge. Voici le récapitulatif de votre session effectuée le <strong>{{ \Carbon\Carbon::parse($invoice['session']['ended_at'])->format('d/m/Y à H:i') }}</strong>.</p>
        
        <p>Borne : <span class="highlight">{{ $invoice['charging_point']['name'] }}</span><br>
        Adresse : {{ $invoice['charging_point']['address'] }}</p>
        
        <table class="details-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: right;">Montant ({{ $invoice['pricing']['currency'] }})</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice['breakdown'] as $line)
                <tr>
                    <td>
                        {{ $line['description'] }}
                        @if(isset($line['quantity']) && isset($line['unit']))
                            <br><small>{{ $line['quantity'] }} {{ $line['unit'] }} × {{ number_format($line['unit_price'], 2) }} {{ $invoice['pricing']['currency'] }}/{{ $line['unit'] }}</small>
                        @endif
                    </td>
                    <td style="text-align: right;">{{ number_format($line['total'], 2) }}</td>
                </tr>
                @endforeach
                
                @if($invoice['pricing']['vat_amount'] > 0)
                <tr>
                    <td style="text-align: right;">Sous-total HT :</td>
                    <td style="text-align: right;">{{ number_format($invoice['pricing']['subtotal'], 2) }}</td>
                </tr>
                <tr>
                    <td style="text-align: right;">TVA ({{ $invoice['pricing']['vat_rate'] }}%) :</td>
                    <td style="text-align: right;">{{ number_format($invoice['pricing']['vat_amount'], 2) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td style="text-align: right;">Total TTC (payé) :</td>
                    <td style="text-align: right; color: #00E676;">{{ number_format($invoice['pricing']['total'], 2) }} {{ $invoice['pricing']['currency'] }}</td>
                </tr>
            </tbody>
        </table>
        
        <p style="text-align: center; margin-top: 20px;">
            Vous trouverez de plus amples détails dans le document PDF en pièce jointe.
        </p>

        <div class="footer">
            <p>&copy; {{ date('Y') }} EVON. Tous droits réservés.</p>
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        </div>
    </div>
</body>
</html>
