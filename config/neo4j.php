<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Neo4j Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour la connexion à Neo4j
    |
    */

    'default' => env('NEO4J_CONNECTION', 'default'),

    'connections' => [
        'default' => [
            'uri' => env('NEO4J_URI', 'bolt://neo4j:7687'),
            'username' => env('NEO4J_USERNAME', 'neo4j'),
            'password' => env('NEO4J_PASSWORD', 'neo4j_password_2024'),
            'database' => env('NEO4J_DATABASE', 'neo4j'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Synchronisation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour la synchronisation MySQL → Neo4j
    |
    */

    'sync' => [
        'batch_size' => env('NEO4J_SYNC_BATCH_SIZE', 100),
        'timeout' => env('NEO4J_SYNC_TIMEOUT', 300),
        'retry_attempts' => env('NEO4J_SYNC_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('NEO4J_SYNC_RETRY_DELAY', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Indexes Configuration
    |--------------------------------------------------------------------------
    |
    | Indexes à créer automatiquement dans Neo4j
    |
    */

    'indexes' => [
        'users' => [
            'email' => 'CREATE INDEX user_email_index IF NOT EXISTS FOR (u:User) ON (u.email)',
            'role' => 'CREATE INDEX user_role_index IF NOT EXISTS FOR (u:User) ON (u.role)',
        ],
        'clubs' => [
            'name' => 'CREATE INDEX club_name_index IF NOT EXISTS FOR (c:Club) ON (c.name)',
            'city' => 'CREATE INDEX club_city_index IF NOT EXISTS FOR (c:Club) ON (c.city)',
        ],
        'teachers' => [
            'specialty' => 'CREATE INDEX teacher_specialty_index IF NOT EXISTS FOR (t:Teacher) ON (t.specialty)',
        ],
    ],
];