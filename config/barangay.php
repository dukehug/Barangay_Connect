<?php

return [
    'office_name' => env('BARANGAY_OFFICE_NAME', 'Barangay 663 Zone 71 District V Manila'),

    'admin_email' => env('SEED_ADMIN_EMAIL'),
    'admin_password' => env('SEED_ADMIN_PASSWORD'),
    'admin_first_name' => env('SEED_ADMIN_FIRST_NAME', 'System'),
    'admin_last_name' => env('SEED_ADMIN_LAST_NAME', 'Administrator'),

    'hotlines' => [
        'barangay' => [
            ['label' => '0912 345 6789', 'number' => '09123456789'],
            ['label' => '0967 331 6819', 'number' => '09673316819'],
        ],
        'national' => [
            [
                'name' => 'NDRRMC',
                'numbers' => [
                    ['label' => '(02) 8911-5061 to 65', 'number' => '0289115061'],
                ],
            ],
            [
                'name' => 'PNP',
                'numbers' => [
                    ['label' => '117', 'number' => '117'],
                    ['label' => '(02) 8722-0650', 'number' => '0287220650'],
                ],
            ],
            [
                'name' => 'Philippine Red Cross',
                'numbers' => [
                    ['label' => '143', 'number' => '143'],
                    ['label' => '(02) 8527-8385 to 95', 'number' => '0285278385'],
                ],
            ],
        ],
    ],

    'document_types' => [
        [
            'document_type_name' => 'Barangay Clearance',
            'category' => 'clearance',
            'document_type_description' => 'Barangay Clearance for employment, business, or other purposes.',
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
