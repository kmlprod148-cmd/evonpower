<?php

namespace App\View\Components;

use App\Services\ClientBalanceService;
use Illuminate\View\Component;

class ClientBalance extends Component
{
    public function __construct(
        public string $formatted = '',
        public string $variant = 'inline', // inline | compact | badge
        public bool $showLabel = true,
        public ?string $rechargeUrl = null,
    ) {
        if ($formatted === '') {
            $this->formatted = app(ClientBalanceService::class)->getFormatted();
        }
        $this->rechargeUrl ??= route('credit-recharge.index');
    }

    public function render()
    {
        return view('components.client-balance');
    }
}
