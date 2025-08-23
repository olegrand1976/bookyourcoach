// Script de test pour vérifier la connectivité API
import axios from "axios";

async function testAPIConnection() {
    const apiUrl = "http://localhost:8081/api";

    console.log("Test de connectivité API...");
    console.log("URL de base:", apiUrl);

    try {
        // Test 1: Santé de l'API
        console.log("\n1. Test de santé de l'API...");
        const healthResponse = await axios.get(`${apiUrl}/auth/user`, {
            validateStatus: function (status) {
                return status < 500; // Accepter toutes les réponses sauf 5xx
            },
        });
        console.log("✓ API accessible - Status:", healthResponse.status);

        // Test 2: Connexion admin
        console.log("\n2. Test de connexion admin...");
        const loginResponse = await axios.post(`${apiUrl}/auth/login`, {
            email: "admin@bookyourcoach.com",
            password: "admin123",
        });

        console.log("✓ Connexion réussie");
        console.log("Token reçu:", loginResponse.data.token ? "Oui" : "Non");
        console.log("Utilisateur:", loginResponse.data.user.name);
        console.log("Rôle:", loginResponse.data.user.role);

        // Test 3: Endpoint admin avec token
        console.log("\n3. Test endpoint admin...");
        const statsResponse = await axios.get(`${apiUrl}/admin/stats`, {
            headers: {
                Authorization: `Bearer ${loginResponse.data.token}`,
            },
        });

        console.log("✓ Endpoint admin accessible");
        console.log("Stats:", statsResponse.data);

        console.log("\n🎉 Tous les tests sont passés avec succès !");
    } catch (error) {
        console.error("\n❌ Erreur détectée:");
        console.error("Message:", error.message);
        if (error.response) {
            console.error("Status:", error.response.status);
            console.error("Data:", error.response.data);
        }
    }
}

testAPIConnection();
