<?php

return [
    'office_name' => env('BARANGAY_OFFICE_NAME', 'Barangay Office'),

    'admin_email' => env('SEED_ADMIN_EMAIL'),
    'admin_password' => env('SEED_ADMIN_PASSWORD'),
    'admin_first_name' => env('SEED_ADMIN_FIRST_NAME', 'System'),
    'admin_last_name' => env('SEED_ADMIN_LAST_NAME', 'Administrator'),

    'document_types' => [
        [
            'document_type_name' => 'Barangay Clearance',
            'category' => 'clearance',
            'document_type_description' => null,
            'document_type_fee' => '50.00',
            'processing_days' => 5,
            'is_active' => true,
            'is_required_other_documents' => true,
        ],
        [
            'document_type_name' => 'Barangay Certification',
            'category' => 'certification',
            'document_type_description' => 'Manual RBI eligibility verification by the Secretary.',
            'document_type_fee' => '0.00',
            'processing_days' => null,
            'is_active' => true,
            'is_required_other_documents' => true,
        ],
        [
            'document_type_name' => 'Request for Use / Closure',
            'category' => 'use_closure',
            'document_type_description' => 'Street use or closure and equipment borrowing.',
            'document_type_fee' => '0.00',
            'processing_days' => null,
            'is_active' => true,
            'is_required_other_documents' => true,
        ],
    ],
];
