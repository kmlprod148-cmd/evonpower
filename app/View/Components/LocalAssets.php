<?php

namespace App\View\Components;

use Illuminate\View\Component;

class LocalAssets extends Component
{
    public $library;
    public $type;

    public function __construct($library, $type = 'css')
    {
        $this->library = $library;
        $this->type = $type;
    }

    public function render()
    {
        $config = config("cdn-local.{$this->library}");
        
        if (!$config || !($config['enabled'] ?? true)) {
            return '';
        }

        $path = $config[$this->type] ?? null;
        
        if (!$path) {
            return '';
        }

        $url = asset($path);

        if ($this->type === 'css') {
            return view('components.local-css', ['url' => $url]);
        } else {
            return view('components.local-js', ['url' => $url]);
        }
    }
}

