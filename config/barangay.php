<?php

return [
    'admin_email' => env('SEED_ADMIN_EMAIL'),
    'admin_password' => env('SEED_ADMIN_PASSWORD'),
    'admin_first_name' => env('SEED_ADMIN_FIRST_NAME', 'System'),
    'admin_last_name' => env('SEED_ADMIN_LAST_NAME', 'Administrator'),

    'document_types' => [
        [
            'document_type_name' => 'Barangay Clearance',
            'document_type_description' => null,
            'document_type_fee' => '50.00',
            'processing_days' => 5,
            'is_active' => true,
            'is_required_other_documents' => false,
        ],
    ],
];
