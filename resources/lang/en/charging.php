<?php

return [
    'title' => 'Charging',
    'charging_point' => 'Charging Point',
    'charging_points' => 'Charging Points',
    'status' => [
        'online' => 'Online',
        'offline' => 'Offline',
        'maintenance' => 'Maintenance',
        'error' => 'Error',
    ],
    'connector_types' => [
        'Type1' => 'Type 1',
        'Type2' => 'Type 2',
        'CCS' => 'CCS',
        'CHAdeMO' => 'CHAdeMO',
        'Schuko' => 'Schuko',
        'Other' => 'Other',
    ],
    'connection_types' => [
        'single_phase' => 'Single Phase',
        'three_phase' => 'Three Phase',
        'dc' => 'DC',
    ],
    'actions' => [
        'start_charging' => 'Start Charging',
        'stop_charging' => 'Stop Charging',
        'select_plan' => 'Select Plan',
        'generate_qr' => 'Generate QR Code',
        'download_qr' => 'Download QR Code',
    ],
    'messages' => [
        'charging_started' => 'Charging session started successfully',
        'charging_stopped' => 'Charging session stopped successfully',
        'plan_selected' => 'Pricing plan selected successfully',
        'qr_generated' => 'QR Code generated successfully',
    ],
    'errors' => [
        'unavailable' => 'Charging point is currently unavailable',
        'maintenance' => 'Charging point is under maintenance',
        'connection_failed' => 'Failed to connect to charging point',
        'invalid_plan' => 'Invalid pricing plan selection',
    ]
];