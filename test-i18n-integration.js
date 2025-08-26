#!/usr/bin/env node

/**
 * Script de test pour vérifier l'intégration multilingue
 * Usage: node test-i18n-integration.js
 */

const fs = require("fs");
const path = require("path");

const LOCALES_DIR = path.join(__dirname, "frontend/locales");
const SUPPORTED_LANGUAGES = [
    "fr",
    "en",
    "nl",
    "de",
    "it",
    "es",
    "pt",
    "hu",
    "pl",
    "zh",
    "ja",
    "sv",
    "no",
    "fi",
    "da",
];

console.log("🌍 Test d'intégration multilingue - BookYourCoach\n");

// 1. Vérifier que tous les fichiers de langue existent
console.log("1. Vérification des fichiers de langue...");
const missingFiles = [];
const existingFiles = [];

SUPPORTED_LANGUAGES.forEach((lang) => {
    const filePath = path.join(LOCALES_DIR, `${lang}.json`);
    if (fs.existsSync(filePath)) {
        existingFiles.push(lang);
        console.log(`   ✅ ${lang}.json`);
    } else {
        missingFiles.push(lang);
        console.log(`   ❌ ${lang}.json`);
    }
});

console.log(
    `\n   Résultat: ${existingFiles.length}/${SUPPORTED_LANGUAGES.length} fichiers trouvés`
);

if (missingFiles.length > 0) {
    console.log(`   ⚠️  Fichiers manquants: ${missingFiles.join(", ")}`);
}

// 2. Vérifier la cohérence des clés entre les langues
console.log("\n2. Vérification de la cohérence des clés...");

const loadJsonFile = (lang) => {
    try {
        const filePath = path.join(LOCALES_DIR, `${lang}.json`);
        return JSON.parse(fs.readFileSync(filePath, "utf8"));
    } catch (error) {
        console.log(
            `   ❌ Erreur lors du chargement de ${lang}.json:`,
            error.message
        );
        return null;
    }
};

// Fonction récursive pour obtenir toutes les clés d'un objet
const getKeys = (obj, prefix = "") => {
    let keys = [];
    for (const key in obj) {
        const fullKey = prefix ? `${prefix}.${key}` : key;
        if (typeof obj[key] === "object" && obj[key] !== null) {
            keys = keys.concat(getKeys(obj[key], fullKey));
        } else {
            keys.push(fullKey);
        }
    }
    return keys;
};

const languageData = {};
const allKeys = new Set();

// Charger tous les fichiers et collecter les clés
existingFiles.forEach((lang) => {
    const data = loadJsonFile(lang);
    if (data) {
        languageData[lang] = data;
        const keys = getKeys(data);
        keys.forEach((key) => allKeys.add(key));
        console.log(`   📄 ${lang}: ${keys.length} clés`);
    }
});

// Vérifier les clés manquantes
console.log("\n3. Vérification des clés manquantes...");
let totalMissingKeys = 0;

existingFiles.forEach((lang) => {
    const data = languageData[lang];
    if (data) {
        const keys = getKeys(data);
        const keySet = new Set(keys);
        const missingKeys = Array.from(allKeys).filter(
            (key) => !keySet.has(key)
        );

        if (missingKeys.length > 0) {
            console.log(
                `   ⚠️  ${lang}: ${missingKeys.length} clés manquantes`
            );
            missingKeys.slice(0, 5).forEach((key) => {
                console.log(`      - ${key}`);
            });
            if (missingKeys.length > 5) {
                console.log(`      ... et ${missingKeys.length - 5} autres`);
            }
            totalMissingKeys += missingKeys.length;
        } else {
            console.log(`   ✅ ${lang}: toutes les clés présentes`);
        }
    }
});

// 4. Vérifier la configuration Nuxt
console.log("\n4. Vérification de la configuration Nuxt...");
const nuxtConfigPath = path.join(__dirname, "frontend/nuxt.config.ts");

if (fs.existsSync(nuxtConfigPath)) {
    const configContent = fs.readFileSync(nuxtConfigPath, "utf8");

    // Vérifier la présence du module i18n
    if (configContent.includes("@nuxtjs/i18n")) {
        console.log("   ✅ Module @nuxtjs/i18n configuré");
    } else {
        console.log("   ❌ Module @nuxtjs/i18n non trouvé");
    }

    // Vérifier les langues configurées
    SUPPORTED_LANGUAGES.forEach((lang) => {
        if (configContent.includes(`code: '${lang}'`)) {
            console.log(`   ✅ Langue ${lang} configurée`);
        } else {
            console.log(
                `   ⚠️  Langue ${lang} non configurée dans nuxt.config.ts`
            );
        }
    });
} else {
    console.log("   ❌ Fichier nuxt.config.ts non trouvé");
}

// 5. Résumé final
console.log("\n📊 RÉSUMÉ:");
console.log(
    `   • Fichiers de langue: ${existingFiles.length}/${SUPPORTED_LANGUAGES.length}`
);
console.log(`   • Clés de traduction totales: ${allKeys.size}`);
console.log(`   • Clés manquantes: ${totalMissingKeys}`);

if (
    existingFiles.length === SUPPORTED_LANGUAGES.length &&
    totalMissingKeys === 0
) {
    console.log("\n🎉 Intégration multilingue complète et cohérente !");
} else {
    console.log(
        "\n⚠️  Intégration multilingue incomplète - voir les détails ci-dessus"
    );
}

console.log("\n✨ Test terminé\n");
