<?php

declare(strict_types=1);

return [

    'auth' => [
        'registered' => 'Акаунт створено.',
        'signed_in' => 'Вхід виконано.',
        'signed_out' => 'Ви вийшли.',
        'invalid_credentials' => 'Невірна пошта або пароль.',
        'unauthenticated' => 'Потрібно увійти.',
        'forbidden' => 'Ця дія вам недоступна.',
    ],

    'http' => [
        'not_found' => 'Ресурс не знайдено.',
        'too_many_requests' => 'Забагато запитів. Спробуйте трохи пізніше.',
        'validation_failed' => 'Перевірте заповнені поля.',
        'server_error' => 'Не вдалося обробити запит.',
    ],

    'workspaces' => [
        'created' => 'Простір створено.',
        'switched' => 'Простір змінено.',
        'personal_exists' => 'Особистий простір у вас уже є.',
        'name_required' => 'Простір компанії потребує назви.',
        'not_a_member' => 'Ви не учасник цього простору.',
    ],

    'enums' => [
        'workspace_type' => [
            'personal' => 'Особистий',
            'company' => 'Компанія',
        ],
        'membership_role' => [
            'owner' => 'Власник',
            'admin' => 'Адміністратор',
            'member' => 'Учасник',
        ],
        'membership_status' => [
            'active' => 'Активний',
            'suspended' => 'Заблокований',
        ],
    ],

];
