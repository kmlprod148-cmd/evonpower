<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Désactiver le log des requêtes pour économiser la mémoire
        \DB::disableQueryLog();
        
        // Désactiver le debug en mode test
        config(['app.debug' => false]);
        
        // Désactiver les notifications en mode test
        config(['mail.default' => 'array']);
    }
}
