<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StatusBadge extends Component
{
    public string $colorClass;

    /**
     * Create a new component instance.
     *
     * @param string $status
     */
    public function __construct(public string $status)
    {
        $this->colorClass = $this->getColorClassForStatus($status);
    }

    /**
     * Get the color class based on the status.
     *
     * @param string $status
     * @return string
     */
    private function getColorClassForStatus(string $status): string
    {
        return match ($status) {
            'credit', 'completed' => 'bg-green-100 text-green-800',
            'debit', 'failed'    => 'bg-red-100 text-red-800',
            'pending'           => 'bg-yellow-100 text-yellow-800',
            default             => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View
     */
    public function render(): View
    {
        return view('components.status-badge');
    }
}