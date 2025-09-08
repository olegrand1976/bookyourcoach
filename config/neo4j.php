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

    'connection_string' => env('NEO4J_CONNECTION_STRING', 'bolt://neo4j:password123@neo4j:7687'),
    
    'database' => env('NEO4J_DATABASE', 'neo4j'),
    
    'username' => env('NEO4J_USERNAME', 'neo4j'),
    
    'password' => env('NEO4J_PASSWORD', 'password123'),
    
    'timeout' => env('NEO4J_TIMEOUT', 30),
    
    'retry_attempts' => env('NEO4J_RETRY_ATTEMPTS', 3),
    
    'sync_enabled' => env('NEO4J_SYNC_ENABLED', true),
    
    'sync_interval' => env('NEO4J_SYNC_INTERVAL', 3600), // en secondes
];
