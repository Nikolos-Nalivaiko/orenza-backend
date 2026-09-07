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
        'membership_status' => [
            'active' => 'Active',
            'suspended' => 'Suspended',
        ],
    ],

];
