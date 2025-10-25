<?php

/**
 * Script de vérification de la connexion Redis avec phpredis
 * 
 * Ce script vérifie :
 * 1. Si l'extension phpredis est installée
 * 2. Si la connexion Redis fonctionne
 * 3. Si les opérations de base (set/get) fonctionnent
 */

echo "=== Vérification de la connexion Redis ===\n\n";

// 1. Vérifier si l'extension phpredis est installée
echo "1. Vérification de l'extension phpredis...\n";
if (extension_loaded('redis')) {
    echo "   ✅ Extension phpredis installée\n";
    echo "   Version: " . phpversion('redis') . "\n";
} else {
    echo "   ❌ Extension phpredis NON installée\n";
    echo "   Veuillez installer l'extension phpredis sur votre serveur\n";
    exit(1);
}

echo "\n";

// 2. Charger les variables d'environnement
echo "2. Chargement de la configuration...\n";
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    echo "   ❌ Fichier .env non trouvé\n";
    exit(1);
}

$envVars = [];
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
        list($key, $value) = explode('=', $line, 2);
        $envVars[trim($key)] = trim($value);
    }
}

$redisHost = $envVars['REDIS_HOST'] ?? '127.0.0.1';
$redisPort = $envVars['REDIS_PORT'] ?? '6379';
$redisPassword = $envVars['REDIS_PASSWORD'] ?? null;
$redisUser = $envVars['REDIS_USER'] ?? null;

echo "   Host: $redisHost\n";
echo "   Port: $redisPort\n";
echo "   Password: " . ($redisPassword ? "***" : "aucune") . "\n";
echo "   User: " . ($redisUser ?: "aucun") . "\n";

echo "\n";

// 3. Tester la connexion Redis
echo "3. Test de connexion Redis...\n";
try {
    $redis = new Redis();
    
    // Connexion avec authentification si nécessaire
    if ($redisUser && $redisPassword) {
        $connected = $redis->connect($redisHost, $redisPort);
        if (!$connected) {
            throw new Exception("Impossible de se connecter à Redis");
        }
        $authResult = $redis->auth([$redisUser, $redisPassword]);
        if (!$authResult) {
            throw new Exception("Échec de l'authentification Redis");
        }
    } elseif ($redisPassword) {
        $connected = $redis->connect($redisHost, $redisPort);
        if (!$connected) {
            throw new Exception("Impossible de se connecter à Redis");
        }
        $authResult = $redis->auth($redisPassword);
        if (!$authResult) {
            throw new Exception("Échec de l'authentification Redis");
        }
    } else {
        $connected = $redis->connect($redisHost, $redisPort);
        if (!$connected) {
            throw new Exception("Impossible de se connecter à Redis");
        }
    }
    
    echo "   ✅ Connexion Redis réussie\n";
    
    // 4. Tester les opérations de base
    echo "\n4. Test des opérations Redis...\n";
    
    $testKey = 'test_connection_' . time();
    $testValue = 'test_value_' . uniqid();
    
    // Test SET
    $setResult = $redis->set($testKey, $testValue);
    if ($setResult) {
        echo "   ✅ SET opération réussie\n";
    } else {
        throw new Exception("Échec de l'opération SET");
    }
    
    // Test GET
    $getValue = $redis->get($testKey);
    if ($getValue === $testValue) {
        echo "   ✅ GET opération réussie\n";
    } else {
        throw new Exception("Échec de l'opération GET");
    }
    
    // Test DEL
    $delResult = $redis->del($testKey);
    if ($delResult > 0) {
        echo "   ✅ DEL opération réussie\n";
    } else {
        throw new Exception("Échec de l'opération DEL");
    }
    
    // Test PING
    $pingResult = $redis->ping();
    if ($pingResult === '+PONG') {
        echo "   ✅ PING opération réussie\n";
    } else {
        echo "   ⚠️  PING réponse inattendue: $pingResult\n";
    }
    
    $redis->close();
    
} catch (Exception $e) {
    echo "   ❌ Erreur Redis: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== Résumé ===\n";
echo "✅ Extension phpredis: OK\n";
echo "✅ Connexion Redis: OK\n";
echo "✅ Opérations Redis: OK\n";
echo "\n🎉 Redis est correctement configuré avec phpredis !\n";
echo "\nN'oubliez pas de modifier votre .env de production :\n";
echo "REDIS_CLIENT=phpredis\n";
