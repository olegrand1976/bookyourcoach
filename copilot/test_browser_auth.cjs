#!/usr/bin/env node

const axios = require('axios');

const API_BASE = 'http://localhost:8000/api';
const FRONTEND_BASE = 'http://localhost:3001';

async function testAuthentication() {
    console.log('🔍 Test d\'authentification dans le navigateur virtuel');
    console.log('================================================');

    try {
        // Étape 1 : Connexion pour obtenir un token
        console.log('\n📋 Étape 1: Connexion...');
        const loginResponse = await axios.post(`${API_BASE}/auth/login`, {
            email: 'admin.secours@bookyourcoach.com',
            password: 'secours123'
        });

        const token = loginResponse.data.token;
        console.log('✅ Token obtenu:', token.substring(0, 20) + '...');
        console.log('✅ User data:', {
            id: loginResponse.data.user.id,
            email: loginResponse.data.user.email,
            role: loginResponse.data.user.role
        });

        // Étape 2 : Simulation de stockage comme le fait le frontend
        console.log('\n📋 Étape 2: Simulation du stockage frontend...');
        const userToStore = loginResponse.data.user;
        const userDataString = JSON.stringify(userToStore);
        console.log('📦 User data à stocker:', userDataString);
        console.log('📦 Role dans les données:', userToStore.role);

        // Étape 3 : Simulation de récupération (comme au refresh)
        console.log('\n📋 Étape 3: Simulation récupération localStorage...');
        const parsedUser = JSON.parse(userDataString);
        console.log('📤 User data récupéré:', {
            id: parsedUser.id,
            email: parsedUser.email,
            role: parsedUser.role
        });

        // Étape 4 : Appel API pour vérifier le token (comme fetchUser)
        console.log('\n📋 Étape 4: Vérification token avec API...');
        const userResponse = await axios.get(`${API_BASE}/auth/user`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });

        console.log('📥 Réponse API complète:', JSON.stringify(userResponse.data, null, 2));
        console.log('📥 User de l\'API:', {
            id: userResponse.data.user.id,
            email: userResponse.data.user.email,
            role: userResponse.data.user.role
        });

        // Étape 5 : Comparaison des données
        console.log('\n📋 Étape 5: Comparaison des données...');
        console.log('Local storage role:', parsedUser.role);
        console.log('API response role:', userResponse.data.user.role);
        console.log('Roles identiques?', parsedUser.role === userResponse.data.user.role ? '✅ OUI' : '❌ NON');

        // Étape 6 : Test de la logique d'assignation
        console.log('\n📋 Étape 6: Test logique d\'assignation...');
        // Simulation de la logique du store: this.user = response.data.user || response.data
        const assignedUser = userResponse.data.user || userResponse.data;
        console.log('User assigné après logique:', {
            id: assignedUser.id,
            email: assignedUser.email,
            role: assignedUser.role
        });

    } catch (error) {
        console.error('❌ Erreur durant le test:', error.message);
        if (error.response) {
            console.error('Response status:', error.response.status);
            console.error('Response data:', error.response.data);
        }
    }
}

testAuthentication();
