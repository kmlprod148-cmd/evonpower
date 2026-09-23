<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Facture {{ $invoice['invoice_number'] }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; margin: 0; padding: 20px; line-height: 1.5; }
        .invoice-box { border: 1px solid #eee; padding: 30px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); max-width: 800px; margin: 0 auto; position: relative; }
        .header { border-bottom: 2px solid #00E676; padding-bottom: 20px; margin-bottom: 20px; display: table; width: 100%; }
        .logo-container { display: table-cell; vertical-align: top; width: 50%; }
        .invoice-details { display: table-cell; text-align: right; width: 50%; }
        .invoice-details h2 { margin: 0; font-size: 24px; color: #333; }
        .invoice-details p { margin: 5px 0 0; color: #555; }
        
        .addresses { display: table; width: 100%; margin-bottom: 30px; }
        .company-address, .client-address { display: table-cell; width: 50%; }
        h3 { font-size: 16px; margin: 0 0 10px; color: #333; border-bottom: 1px dashed #ccc; padding-bottom: 5px; }
        .address-box p { margin: 2px 0; color: #555; }
        
        table.items { width: 100%; border-collapse: collapse; margin-top: 20px; margin-bottom: 20px; }
        table.items th, table.items td { padding: 12px; text-align: left; }
        table.items th { background: #f5f5f5; font-weight: bold; border-bottom: 2px solid #ddd; }
        table.items td { border-bottom: 1px solid #eee; }
        table.items tr:last-child td { border-bottom: none; }
        
        .totals { float: right; width: 300px; margin-top: 20px; }
        .totals table { width: 100%; border-collapse: collapse; }
        .totals td { padding: 5px 10px; }
        .totals .total-row { font-weight: bold; font-size: 18px; color: #00E676; border-top: 2px solid #333; }
        .text-right { text-align: right !important; }
        
        .footer { position: absolute; bottom: 20px; text-align: center; width: 100%; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        .payment-status { margin-top: 50px; text-align: left; color: #555; font-style: italic; }
        .paid-stamp { color: #00E676; font-weight: bold; border: 2px solid #00E676; padding: 5px 15px; display: inline-block; transform: rotate(-5deg); margin-top: 20px; }
        
        .clearfix::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="header">
            <div class="logo-container">
                <!-- <img src="logo.png" style="width: 150px; max-width: 150px"> -->
                <h1 style="color: #00E676; margin: 0; font-size: 32px;">EVON</h1>
                <p style="margin: 0; color: #777;">Réseau de recharge électrique</p>
            </div>
            
            <div class="invoice-details">
                <h2>Facture {{ $invoice['invoice_number'] }}</h2>
                <p>Émise le : {{ \Carbon\Carbon::parse($invoice['invoice_date'])->format('d/m/Y') }}</p>
                <p>Session ID : {{ $invoice['session']['session_id'] }}</p>
            </div>
        </div>

        <div class="addresses">
            <div class="company-address address-box">
                <h3>OPÉRATEUR DE RECHARGE</h3>
                <p><strong>EVON Services</strong></p>
                <p>123 Boulevard de la Mobilité</p>
                <p>75001 Paris, France</p>
                <p>contact@evon-network.com</p>
                <p>N° TVA : </p>
            </div>
            
            <div class="client-address address-box">
                <h3>ADRESSÉE À</h3>
                <p><strong>{{ $invoice['user']['name'] }}</strong></p>
                <p>{{ $invoice['user']['email'] }}</p>
                <br>
                <p><em>Borne utilisée :</em></p>
                <p>{{ $invoice['charging_point']['name'] }}</p>
                <p>{{ $invoice['charging_point']['address'] }}</p>
            </div>
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th>Détail du service</th>
                    <th class="text-right">Unités</th>
                    <th class="text-right">Prix Unitaire</th>
                    <th class="text-right">Montant ({{ $invoice['pricing']['currency'] }})</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice['breakdown'] as $line)
                <tr>
                    <td>
                        {{ $line['description'] }}
                        @if($line['type'] === 'energy')
                            <br><small>Énergie consommée</small>
                        @elseif($line['type'] === 'time')
                            <br><small>Temps de connexion</small>
                        @endif
                    </td>
                    <td class="text-right">
                        @if(isset($line['quantity']) && isset($line['unit']))
                            {{ $line['quantity'] }} {{ $line['unit'] }}
                        @else
                            1
                        @endif
                    </td>
                    <td class="text-right">
                        @if(isset($line['unit_price']))
                            {{ number_format($line['unit_price'], 2) }}
                        @else
                            {{ number_format($line['total'], 2) }}
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($line['total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="clearfix">
            <div style="float: left; width: 40%; margin-top: 30px;">
                <p class="payment-status">
                    Statut du paiement : <strong>Réglé</strong> via {{ ucfirst($invoice['payment']['method'] ?? 'Portefeuille') }}<br>
                    Date : {{ \Carbon\Carbon::parse($invoice['payment']['paid_at'])->format('d/m/Y H:i') }}
                </p>
                <div class="paid-stamp">PAYÉE</div>
            </div>
            
            <div class="totals">
                <table>
                    @if($invoice['pricing']['vat_amount'] > 0)
                    <tr>
                        <td class="text-right">Sous-total HT</td>
                        <td class="text-right">{{ number_format($invoice['pricing']['subtotal'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-right">TVA ({{ $invoice['pricing']['vat_rate'] }}%)</td>
                        <td class="text-right">{{ number_format($invoice['pricing']['vat_amount'], 2) }}</td>
                    </tr>
                    @else
                    <tr>
                        <td class="text-right">Total HT</td>
                        <td class="text-right">{{ number_format($invoice['pricing']['total'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-right">TVA (0%)</td>
                        <td class="text-right">0.00</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td class="text-right">Total TTC</td>
                        <td class="text-right">{{ number_format($invoice['pricing']['total'], 2) }} {{ $invoice['pricing']['currency'] }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="footer">
            Merci d'avoir choisi EVON pour vos recharges. Pour toute question, contactez notre support client.<br>
            Facture générée informatiquement, tient lieu d'original.
        </div>
    </div>
</body>
</html>
