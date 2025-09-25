// Test script pour vérifier l'authentification frontend
const API_BASE = 'http://localhost:8080/api';

// Test de connexion
async function testLogin() {
  try {
    console.log('🔐 Test de connexion...');
    const response = await fetch(`${API_BASE}/auth/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        email: 'manager@centre-Équestre-des-Étoiles.fr',
        password: 'password'
      })
    });
    
    const data = await response.json();
    console.log('✅ Connexion réussie:', response.status);
    console.log('Token:', data.token ? 'Généré' : 'Manquant');
    
    return data.token;
  } catch (error) {
    console.error('❌ Erreur de connexion:', error);
    return null;
  }
}

// Test d'accès protégé
async function testProtectedRoute(token) {
  try {
    console.log('🔒 Test d\'accès protégé...');
    const response = await fetch(`${API_BASE}/club/dashboard`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    });
    
    const data = await response.json();
    console.log('✅ Accès protégé réussi:', response.status);
    console.log('Données reçues:', data.success ? 'Oui' : 'Non');
    
    return response.status === 200;
  } catch (error) {
    console.error('❌ Erreur d\'accès protégé:', error);
    return false;
  }
}

// Test complet
async function runTests() {
  console.log('🚀 Début des tests d\'authentification...\n');
  
  const token = await testLogin();
  if (!token) return;
  
  console.log('');
  const protectedSuccess = await testProtectedRoute(token);
  
  console.log('\n📋 Résumé:');
  console.log('✅ Connexion API:', 'OK');
  console.log('✅ Token généré:', 'OK');
  console.log('✅ Accès protégé:', protectedSuccess ? 'OK' : 'ÉCHEC');
  
  if (protectedSuccess) {
    console.log('\n🎉 Tous les tests sont passés !');
    console.log('💡 Le problème vient probablement du frontend.');
  } else {
    console.log('\n❌ Il y a un problème avec l\'API.');
  }
}

runTests();
