<?php

if (!function_exists('localization')) {
    function localization() {
        return app(\App\Helpers\LocalizationHelper::class);
    }
}
