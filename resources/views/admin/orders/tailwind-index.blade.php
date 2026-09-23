<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Commandes</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset("vendor/fontawesome/css/all.min.css") }}">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        secondary: '#60a5fa',
                        accent: '#10b981',
                        dark: '#1e293b',
                        light: '#f8fafc'
                    }
                }
            }
        }
    </script>
    <style>
        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .json-details {
            max-height: 300px;
            overflow: auto;
            font-family: monospace;
            font-size: 0.85rem;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-dark text-white min-h-screen p-5">
            <div class="flex items-center mb-10">
                <div class="bg-gray-200 border-2 border-dashed rounded-xl w-16 h-16"></div>
                <div class="ml-3">
                    <h2 class="text-xl font-bold">EVON Power</h2>
                    <p class="text-gray-400 text-sm">Administration</p>
                </div>
            </div>
            <nav>
                <ul class="space-y-2">
                    <li>
                        <a href="#" class="flex items-center p-3 rounded-lg bg-primary text-white">
                            <i class="fas fa-tachometer-alt mr-3"></i>
                            Tableau de bord
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center p-3 rounded-lg text-gray-300 hover:bg-gray-700">
                            <i class="fas fa-charging-station mr-3"></i>
                            Bornes de recharge
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center p-3 rounded-lg text-gray-300 hover:bg-gray-700">
                            <i class="fas fa-users mr-3"></i>
                            Utilisateurs
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center p-3 rounded-lg bg-gray-700 text-white">
                            <i class="fas fa-shopping-cart mr-3"></i>
                            Commandes
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center p-3 rounded-lg text-gray-300 hover:bg-gray-700">
                            <i class="fas fa-chart-line mr-3"></i>
                            Rapports
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center p-3 rounded-lg text-gray-300 hover:bg-gray-700">
                            <i class="fas fa-cog mr-3"></i>
                            Paramètres
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">
                        <i class="fas fa-shopping-cart mr-3 text-primary"></i>Commandes en attente
                    </h1>
                    <p class="text-gray-600 mt-2">Gestion des commandes de recharge en attente de traitement</p>
                </div>
                <a href="{{ route('admin.orders.history') }}" class="btn bg-primary hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg transition flex items-center">
                    <i class="fas fa-clock mr-2"></i> Historique
                </a>
            </div>

            <!-- Alerts -->
            @if(session('success'))
                <div class="alert bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg flex items-start mb-4">
                    <i class="fas fa-check-circle text-xl mr-3 mt-1 text-green-500"></i>
                    <div>
                        <p class="font-medium">{{ session('success') }}</p>
                    </div>
                    <button class="ml-auto text-gray-500 hover:text-gray-700" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg flex items-start mb-4">
                    <i class="fas fa-exclamation-circle text-xl mr-3 mt-1 text-red-500"></i>
                    <div>
                        <p class="font-medium">{{ session('error') }}</p>
                    </div>
                    <button class="ml-auto text-gray-500 hover:text-gray-700" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif

            <!-- Filter Section -->
            <form method="GET" class="bg-white rounded-xl shadow-sm p-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Filtrer les commandes</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                        <div class="relative">
                            <input type="date" name="from" value="{{ request('from') }}" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-gray-500">
                                <i class="fas fa-calendar"></i>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                        <div class="relative">
                            <input type="date" name="to" value="{{ request('to') }}" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-gray-500">
                                <i class="fas fa-calendar"></i>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                        <div class="relative">
                            <select name="status" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="pending" {{ request('status', 'pending') == 'pending' ? 'selected' : '' }}>En attente</option>
                                <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmée</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulée</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-gray-500">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-end space-x-2">
                        <button type="submit" class="w-full bg-primary hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg transition">
                            <i class="fas fa-filter mr-2"></i> Appliquer
                        </button>
                        <a href="{{ route('admin.orders.index') }}" class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2.5 rounded-lg transition text-center">
                            <i class="fas fa-redo mr-2"></i> Réinitialiser
                        </a>
                    </div>
                </div>
            </form>

            <!-- Orders Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-8">
                <div class="bg-white rounded-xl shadow-sm p-5 border-t-4 border-blue-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm">Commandes totales</p>
                            <h3 class="text-2xl font-bold mt-1">{{ $stats['total'] ?? 0 }}</h3>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-5 border-t-4 border-yellow-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm">En attente</p>
                            <h3 class="text-2xl font-bold mt-1">{{ $stats['pending'] ?? 0 }}</h3>
                        </div>
                        <div class="bg-yellow-100 p-3 rounded-full">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-5 border-t-4 border-green-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm">Confirmées</p>
                            <h3 class="text-2xl font-bold mt-1">{{ $stats['confirmed'] ?? 0 }}</h3>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-5 border-t-4 border-red-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm">Annulées</p>
                            <h3 class="text-2xl font-bold mt-1">{{ $stats['cancelled'] ?? 0 }}</h3>
                        </div>
                        <div class="bg-red-100 p-3 rounded-full">
                            <i class="fas fa-times-circle text-red-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Commande</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Borne</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($orders as $order)
                                <tr class="order-card transition-all duration-300 hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900">#{{ $order->id }}</div>
                                        <div class="text-sm text-gray-500">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="bg-gray-200 border-2 border-dashed rounded-xl w-10 h-10"></div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $order->user ? $order->user->name : '-' }}</div>
                                                <div class="text-sm text-gray-500">ID: {{ $order->user_id }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $order->chargingPoint->name ?? $order->chargingPoint->id ?? '-' }}</div>
                                        <div class="text-sm text-gray-500">ID: {{ $order->charging_point_id }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $order->plan->name ?? $order->plan->id ?? '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-lg font-bold text-gray-900">{{ $order->amount ?? '-' }} €</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($order->status == 'pending')
                                            <span class="status-badge bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-clock mr-1"></i> En attente
                                            </span>
                                        @elseif($order->status == 'confirmed')
                                            <span class="status-badge bg-green-100 text-green-800">
                                                <i class="fas fa-check-circle mr-1"></i> Confirmée
                                            </span>
                                        @elseif($order->status == 'cancelled')
                                            <span class="status-badge bg-red-100 text-red-800">
                                                <i class="fas fa-times-circle mr-1"></i> Annulée
                                            </span>
                                        @else
                                            <span class="status-badge bg-gray-100 text-gray-800">
                                                {{ ucfirst($order->status) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <form method="POST" action="{{ route('admin.orders.confirm', $order->id) }}" onsubmit="return confirm('Confirmer cette commande ?');">
                                                @csrf
                                                @method('PATCH')
                                                <button class="text-green-600 hover:text-green-900" type="submit">
                                                    <i class="fas fa-check-circle mr-1"></i> Confirmer
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.orders.cancel', $order->id) }}" onsubmit="return confirm('Annuler cette commande ?');">
                                                @csrf
                                                @method('PATCH')
                                                <button class="text-red-600 hover:text-red-900" type="submit">
                                                    <i class="fas fa-times-circle mr-1"></i> Annuler
                                                </button>
                                            </form>
                                            <button class="text-blue-600 hover:text-blue-900 details-toggle" type="button">
                                                <i class="fas fa-eye mr-1"></i> Détails
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <!-- Details Panel -->
                                <tr class="hidden details-panel">
                                    <td colspan="7" class="px-6 py-4 bg-gray-50">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <h3 class="text-sm font-medium text-gray-900 mb-2">Détails de la commande</h3>
                                                <div class="bg-gray-100 p-4 rounded-lg">
                                                    <div class="mb-2 flex justify-between">
                                                        <span class="text-gray-600">Plan:</span>
                                                        <span class="font-medium">{{ $order->plan->name ?? '-' }}</span>
                                                    </div>
                                                    <div class="mb-2 flex justify-between">
                                                        <span class="text-gray-600">Borne:</span>
                                                        <span class="font-medium">{{ $order->chargingPoint->name ?? '-' }}</span>
                                                    </div>
                                                    <div class="mb-2 flex justify-between">
                                                        <span class="text-gray-600">Début:</span>
                                                        <span class="font-medium">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600">Montant:</span>
                                                        <span class="font-medium">{{ $order->amount ?? '-' }} €</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <h3 class="text-sm font-medium text-gray-900 mb-2">Détails techniques</h3>
                                                <div class="json-details bg-gray-100 p-4 rounded-lg">
                                                    @if($order->details)
                                                        <pre>{{ json_encode(json_decode($order->details), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                    @else
                                                        <span class="text-gray-500">Aucun détail technique</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-8">Aucune commande en attente.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>
        // Toggle details panels
        document.querySelectorAll('.details-toggle').forEach(button => {
            button.addEventListener('click', function() {
                const panel = this.closest('tr').nextElementSibling;
                panel.classList.toggle('hidden');
            });
        });
    </script>
</body>
</html> 