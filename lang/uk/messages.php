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
        'slug_format' => 'Адреса може містити лише малі латинські літери, цифри та дефіси.',
    ],

    'clients' => [
        'created' => 'Замовника додано.',
        'updated' => 'Зміни збережено.',
        'deleted' => 'Замовника видалено.',
        'name_required' => 'Вкажіть, як звати замовника.',
    ],

    'objects' => [
        'created' => 'Обʼєкт створено.',
        'updated' => 'Зміни збережено.',
        'deleted' => 'Обʼєкт видалено.',
        'name_required' => 'Вкажіть назву обʼєкта.',
        'address_required' => 'Вкажіть адресу — без неї обʼєкт не знайти.',
        'client_not_found' => 'Такого замовника у просторі немає.',
        'finish_before_start' => 'Завершення раніше за початок.',
        'actual_start_first' => 'Спочатку вкажіть фактичний початок.',
        'actual_start_required' => 'Обʼєкт у роботі — вкажіть, коли фактично почали.',
        'actual_finish_required' => 'Обʼєкт завершено — вкажіть фактичну дату здачі.',
    ],

    'materials' => [
        'created' => 'Матеріал додано.',
        'updated' => 'Зміни збережено.',
        'deleted' => 'Матеріал прибрано.',
        'name_required' => 'Вкажіть назву матеріалу.',
    ],

    'services' => [
        'created' => 'Роботу додано.',
        'updated' => 'Зміни збережено.',
        'deleted' => 'Роботу прибрано.',
        'name_required' => 'Вкажіть назву роботи.',
        'no_team' => 'В особистому просторі виконавців немає.',
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
        'client_type' => [
            'person' => 'Особа',
            'company' => 'Компанія',
        ],
        'object_status' => [
            'planned' => 'Планується',
            'in_progress' => 'В роботі',
            'paused' => 'Призупинено',
            'done' => 'Завершено',
        ],
        'material_status' => [
            'needed' => 'Потрібно',
            'ordered' => 'Замовлено',
            'delivered' => 'Доставлено',
            'used' => 'Використано',
        ],
        'material_buyer' => [
            'contractor' => 'Підрядник',
            'client' => 'Замовник',
        ],
        'service_status' => [
            'planned' => 'Заплановано',
            'in_progress' => 'В роботі',
            'done' => 'Виконано',
        ],
        'membership_status' => [
            'active' => 'Активний',
            'suspended' => 'Заблокований',
        ],
    ],

];
