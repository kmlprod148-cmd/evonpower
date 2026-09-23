<?php

if (!function_exists("show_integrators_link")) {
    function show_integrators_link() {
        return true; // Toujours afficher
    }
}

if (!function_exists("integrators_route")) {
    function integrators_route() {
        return route("integrators.index");
    }
}

if (!function_exists("integrators_text")) {
    function integrators_text() {
        return "Intégrateurs";
    }
}
?>