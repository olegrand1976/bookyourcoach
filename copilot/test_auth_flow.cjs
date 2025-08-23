#!/usr/bin/env node

const axios = require("axios");

const API_BASE = "http://localhost:8081/api";

async function simulateBrowserAuthFlow() {
    console.log("🎯 Simulation complète du flux d'authentification navigateur");
    console.log("===========================================================");

    try {
        // Étape 1 : Connexion et récupération du token
        console.log("\n📋 Étape 1: Connexion utilisateur...");
        const loginResponse = await axios.post(`${API_BASE}/auth/login`, {
            email: "admin.secours@bookyourcoach.com",
            password: "secours123",
        });

        const initialToken = loginResponse.data.token;
        const initialUser = loginResponse.data.user;

        console.log("✅ Connexion réussie");
        console.log("🔑 Token:", initialToken.substring(0, 20) + "...");
        console.log("👤 User initial:", {
            id: initialUser.id,
            email: initialUser.email,
            role: initialUser.role,
            name: initialUser.name,
        });

        // Simulation du stockage frontend (équivalent au login dans le store)
        const userDataToStore = JSON.stringify(initialUser);
        console.log("\n📦 Simulation stockage localStorage...");
        console.log("📦 Données stockées:", userDataToStore);

        // Étape 2 : Simulation du rafraîchissement de page (initializeAuth)
        console.log(
            "\n🔄 Simulation rafraîchissement de page (initializeAuth)..."
        );

        // Récupération depuis localStorage (simulation)
        const storedUserData = JSON.parse(userDataToStore);
        console.log("📤 Données récupérées du localStorage:", {
            id: storedUserData.id,
            email: storedUserData.email,
            role: storedUserData.role,
            name: storedUserData.name,
        });

        // Vérification du token avec l'API (simulation fetchUser)
        console.log("\n🔍 Vérification du token avec /auth/user...");
        const userResponse = await axios.get(`${API_BASE}/auth/user`, {
            headers: {
                Authorization: `Bearer ${initialToken}`,
                Accept: "application/json",
            },
        });

        console.log(
            "📥 Réponse /auth/user:",
            JSON.stringify(userResponse.data, null, 2)
        );

        // Simulation de l'assignation du store (this.user = response.data.user || response.data)
        const assignedUser = userResponse.data.user || userResponse.data;
        console.log("\n🎯 User assigné après fetchUser:", {
            id: assignedUser.id,
            email: assignedUser.email,
            role: assignedUser.role,
            name: assignedUser.name,
        });

        // Comparaison finale
        console.log("\n📊 ANALYSE COMPARATIVE:");
        console.log("========================");
        console.log("🔵 Initial (login)    :", `role="${initialUser.role}"`);
        console.log("🟡 localStorage       :", `role="${storedUserData.role}"`);
        console.log("🟢 API /auth/user     :", `role="${assignedUser.role}"`);

        const roleChanged = initialUser.role !== assignedUser.role;
        console.log(
            "\n🎯 RÉSULTAT:",
            roleChanged ? "❌ ROLE CHANGÉ" : "✅ ROLE CONSERVÉ"
        );

        if (roleChanged) {
            console.log(
                "⚠️  PROBLÈME DÉTECTÉ: Le rôle a changé entre la connexion et la vérification!"
            );
            console.log(
                `   Initial: ${initialUser.role} → Final: ${assignedUser.role}`
            );
        } else {
            console.log(
                "✅ Aucun problème détecté dans le flux d'authentification"
            );
        }

        // Test supplémentaire : plusieurs appels consécutifs
        console.log(
            "\n📋 Test de stabilité: 3 appels consécutifs à /auth/user..."
        );
        for (let i = 1; i <= 3; i++) {
            const testResponse = await axios.get(`${API_BASE}/auth/user`, {
                headers: {
                    Authorization: `Bearer ${initialToken}`,
                    Accept: "application/json",
                },
            });
            const testUser = testResponse.data.user || testResponse.data;
            console.log(`🔍 Appel ${i}: role="${testUser.role}"`);
        }
    } catch (error) {
        console.error("❌ Erreur durant la simulation:", error.message);
        if (error.response) {
            console.error("📜 Status:", error.response.status);
            console.error("📜 Data:", error.response.data);
        }
    }
}

simulateBrowserAuthFlow();
