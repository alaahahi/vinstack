<?php

return [
    'vehicle_assigned' => [
        'intro' => 'A new vehicle has been added to your account at :company',
        'labels' => [
            'year' => 'Year',
            'make' => 'Make',
            'model' => 'Model',
            'vin' => 'VIN',
            'color' => 'Color',
            'lot' => 'Lot',
            'destination' => 'Destination',
            'status' => 'Status',
        ],
    ],
    'login_credentials' => [
        'welcome' => 'Welcome to :company!',
        'intro' => 'Your account has been created at :company. Login details:',
        'email' => 'Email: :value',
        'password' => 'Password: :value',
        'url' => 'Login URL: :value',
        'url_hint' => 'Tap the link to sign in automatically if you are logged out.',
    ],
    'vehicle_updated' => [
        'intro' => 'Vehicle status update at :company',
        'vehicle' => 'Vehicle: :value',
        'vin' => 'VIN: :value',
        'change' => 'Change: :previous → :next',
    ],
    'vehicle_images_added' => [
        'intro' => 'New photos were added to your vehicle at :company',
        'vehicle' => 'Vehicle: :value',
        'vin' => 'VIN: :value',
        'count' => 'Photos: :count',
        'stage' => 'Stage: :stage',
    ],
    'container_images_added' => [
        'intro' => 'New photos were added to your container at :company',
        'container' => 'Container: :value',
        'count' => 'Photos: :count',
    ],
];
