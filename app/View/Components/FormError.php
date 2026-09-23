<?php

namespace App\View\Components;

use Illuminate\View\Component;

class FormError extends Component
{
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function render()
    {
        return view('components.form-error');
    }
}