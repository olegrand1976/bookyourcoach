<?php

/**
 * Script de diagnostic pour les problèmes de connexion Redis
 * Ce script teste différentes configurations Redis pour identifier le problème
 */

echo "=== Diagnostic de connexion Redis ===\n\n";

// Charger les variables d'environnement
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    echo "❌ Fichier .env non trouvé\n";
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
$redisUsername = $envVars['REDIS_USERNAME'] ?? null;

echo "Configuration détectée :\n";
echo "  Host: $redisHost\n";
echo "  Port: $redisPort\n";
echo "  Password: " . ($redisPassword ? "***" : "aucune") . "\n";
echo "  REDIS_USER: " . ($redisUser ?: "non défini") . "\n";
echo "  REDIS_USERNAME: " . ($redisUsername ?: "non défini") . "\n";

echo "\n=== Tests de connexion ===\n";

// Test 1: Connexion sans authentification
echo "\n1. Test sans authentification...\n";
try {
    $redis = new Redis();
    $connected = $redis->connect($redisHost, $redisPort, 5); // 5 secondes timeout
    if ($connected) {
        echo "   ✅ Connexion réussie sans auth\n";
        $redis->close();
    } else {
        echo "   ❌ Échec de connexion sans auth\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
}

// Test 2: Connexion avec mot de passe seulement
if ($redisPassword) {
    echo "\n2. Test avec mot de passe seulement...\n";
    try {
        $redis = new Redis();
        $connected = $redis->connect($redisHost, $redisPort, 5);
        if ($connected) {
            $authResult = $redis->auth($redisPassword);
            if ($authResult) {
                echo "   ✅ Authentification par mot de passe réussie\n";
                $redis->close();
            } else {
                echo "   ❌ Échec d'authentification par mot de passe\n";
            }
        } else {
            echo "   ❌ Échec de connexion\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Erreur: " . $e->getMessage() . "\n";
    }
}

// Test 3: Connexion avec utilisateur et mot de passe (ACL)
if ($redisUser && $redisPassword) {
    echo "\n3. Test avec utilisateur et mot de passe (ACL)...\n";
    try {
        $redis = new Redis();
        $connected = $redis->connect($redisHost, $redisPort, 5);
        if ($connected) {
            $authResult = $redis->auth([$redisUser, $redisPassword]);
            if ($authResult) {
                echo "   ✅ Authentification ACL réussie\n";
                $redis->close();
            } else {
                echo "   ❌ Échec d'authentification ACL\n";
            }
        } else {
            echo "   ❌ Échec de connexion\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Erreur: " . $e->getMessage() . "\n";
    }
}

// Test 4: Test avec REDIS_USERNAME si différent de REDIS_USER
if ($redisUsername && $redisUsername !== $redisUser) {
    echo "\n4. Test avec REDIS_USERNAME...\n";
    try {
        $redis = new Redis();
        $connected = $redis->connect($redisHost, $redisPort, 5);
        if ($connected) {
            $authResult = $redis->auth([$redisUsername, $redisPassword]);
            if ($authResult) {
                echo "   ✅ Authentification avec REDIS_USERNAME réussie\n";
                $redis->close();
            } else {
                echo "   ❌ Échec d'authentification avec REDIS_USERNAME\n";
            }
        } else {
            echo "   ❌ Échec de connexion\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Erreur: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Recommandations ===\n";

if (!$redisUsername && $redisUser) {
    echo "⚠️  Problème détecté : Vous avez REDIS_USER mais Laravel cherche REDIS_USERNAME\n";
    echo "   Solution : Renommez REDIS_USER en REDIS_USERNAME dans votre .env\n";
    echo "   Ou ajoutez : REDIS_USERNAME=$redisUser\n";
}

if ($redisPassword && !$redisUser && !$redisUsername) {
    echo "ℹ️  Vous utilisez seulement un mot de passe Redis (mode classique)\n";
    echo "   Assurez-vous que votre serveur Redis accepte l'authentification par mot de passe\n";
}

if ($redisUser || $redisUsername) {
    echo "ℹ️  Vous utilisez l'authentification ACL Redis\n";
    echo "   Assurez-vous que votre serveur Redis supporte les ACLs (Redis 6.0+)\n";
}

echo "\n=== Configuration recommandée pour votre .env ===\n";
echo "REDIS_CLIENT=phpredis\n";
echo "REDIS_HOST=$redisHost\n";
echo "REDIS_PORT=$redisPort\n";
if ($redisPassword) {
    echo "REDIS_PASSWORD=$redisPassword\n";
}
if ($redisUser) {
    echo "REDIS_USERNAME=$redisUser\n";
}
echo "REDIS_DB=0\n";
