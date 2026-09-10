<?php

declare(strict_types=1);

return [

    'auth' => [
        'registered' => 'Registration completed.',
        'signed_in' => 'Signed in.',
        'signed_out' => 'Signed out.',
        'invalid_credentials' => 'These credentials do not match our records.',
        'unauthenticated' => 'Unauthenticated.',
        'forbidden' => 'This action is unauthorized.',
    ],

    'http' => [
        'not_found' => 'The requested resource was not found.',
        'too_many_requests' => 'Too many requests.',
        'validation_failed' => 'The given data was invalid.',
        'server_error' => 'The request could not be processed.',
    ],

    'workspaces' => [
        'created' => 'Workspace created.',
        'switched' => 'Current workspace changed.',
        'personal_exists' => 'You already have a personal workspace.',
        'name_required' => 'A company workspace requires a name.',
        'not_a_member' => 'You are not a member of this workspace.',
        'slug_format' => 'The slug may contain lowercase letters, digits and single dashes only.',
    ],

    'clients' => [
        'created' => 'Client created.',
        'updated' => 'Client updated.',
        'deleted' => 'Client deleted.',
        'name_required' => 'A client requires a name.',
    ],

    'objects' => [
        'created' => 'Object created.',
        'updated' => 'Object updated.',
        'deleted' => 'Object deleted.',
        'name_required' => 'An object requires a name.',
        'address_required' => 'An object requires an address.',
        'client_not_found' => 'This workspace has no such client.',
        'finish_before_start' => 'The finish date is earlier than the start date.',
        'actual_start_first' => 'Set the actual start date first.',
        'actual_start_required' => 'The object is in progress — set the actual start date.',
        'actual_finish_required' => 'The object is done — set the actual finish date.',
    ],

    'materials' => [
        'created' => 'Material added.',
        'updated' => 'Material updated.',
        'deleted' => 'Material removed.',
        'name_required' => 'A material requires a name.',
    ],

    'enums' => [
        'workspace_type' => [
            'personal' => 'Personal',
            'company' => 'Company',
        ],
        'membership_role' => [
            'owner' => 'Owner',
            'admin' => 'Administrator',
            'member' => 'Member',
        ],
        'client_type' => [
            'person' => 'Person',
            'company' => 'Company',
        ],
        'object_status' => [
            'planned' => 'Planned',
            'in_progress' => 'In progress',
            'paused' => 'Paused',
            'done' => 'Done',
        ],
        'material_status' => [
            'needed' => 'Needed',
            'ordered' => 'Ordered',
            'delivered' => 'Delivered',
            'used' => 'Used',
        ],
        'material_buyer' => [
            'contractor' => 'Contractor',
            'client' => 'Client',
        ],
        'membership_status' => [
            'active' => 'Active',
            'suspended' => 'Suspended',
        ],
    ],

];
