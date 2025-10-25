# 🔐 Identifiants de Test - 3 Enseignants

**Club**: Centre Équestre des Étoiles  
**URL**: http://localhost:3000/login

---

## 👤 Enseignant 1 - Marie Leroy

```
Email: marie.leroy@centre-Équestre-des-Étoiles.fr
Mot de passe: password
```

**Dashboard**: http://localhost:3000/teacher/dashboard

**Ce que vous verrez**:
- 📤 2 demandes envoyées (en attente de réponse)
- 📥 1 demande reçue à traiter (de Sophie) ⚠️
- 1 demande acceptée (par Jean) ✅

**Action à tester**: Accepter ou refuser la demande de Sophie

---

## 👤 Enseignant 2 - Jean Moreau

```
Email: jean.moreau@centre-Équestre-des-Étoiles.fr
Mot de passe: password
```

**Dashboard**: http://localhost:3000/teacher/dashboard

**Ce que vous verrez**:
- 📤 2 demandes envoyées (1 en attente, 1 acceptée)
- 📥 1 demande reçue à traiter (de Marie) ⚠️

**Action à tester**: Accepter ou refuser la demande de Marie

---

## 👤 Enseignant 3 - Sophie Rousseau

```
Email: sophie.rousseau@centre-equestre-des-etoiles.fr
Mot de passe: password
```

**Dashboard**: http://localhost:3000/teacher/dashboard

**Ce que vous verrez**:
- 📤 1 demande envoyée (en attente)
- 📥 2 demandes reçues à traiter (de Marie et Jean) ⚠️

**Action à tester**: Accepter ou refuser 2 demandes

---

## 📊 Résumé des Demandes

| # | De | À | Date du cours | Raison | Statut |
|---|---|---|---------------|--------|--------|
| 1 | Marie | Jean | 26/10/2025 09:00 | Rendez-vous médical | 🟡 EN ATTENTE |
| 2 | Jean | Sophie | 28/10/2025 09:00 | Urgence familiale | 🟡 EN ATTENTE |
| 3 | Sophie | Marie | 28/10/2025 15:30 | Indisponibilité | 🟡 EN ATTENTE |
| 4 | Marie | Sophie | 28/10/2025 09:00 | Problème de santé | 🟡 EN ATTENTE |
| 5 | Jean | Marie | 28/10/2025 14:30 | Conflit horaire | 🟢 ACCEPTÉE |

---

## 🧪 Test Rapide (5 minutes)

### Test 1: Voir les notifications
1. Connectez-vous avec **Sophie** (2 demandes en attente)
2. Vous devriez voir un bandeau orange en haut
3. Détails des 2 demandes affichés

### Test 2: Accepter une demande
1. Toujours connecté avec **Sophie**
2. Cliquez sur "✓ Accepter" pour la demande de Marie
3. Le cours est maintenant dans votre liste
4. Le bandeau affiche maintenant "1 demande"

### Test 3: Refuser une demande
1. Connectez-vous avec **Jean**
2. Cliquez sur "✗ Refuser" pour la demande de Marie
3. Le bandeau disparaît
4. Le cours reste à Marie

### Test 4: Voir les détails d'un cours
1. N'importe quel enseignant
2. Cliquez sur "👁️ Voir" dans le tableau
3. Modale avec tous les détails
4. Notez l'âge de l'élève affiché

---

## 💡 Points à Vérifier

### Dashboard
- ✅ Statistiques (cours du jour, total, remplacements)
- ✅ Bandeau de notifications orange si demandes en attente
- ✅ Nombre exact de demandes
- ✅ Détails des demandes (qui, quand, pourquoi)
- ✅ Boutons Accepter/Refuser fonctionnels

### Tableau des Cours
- ✅ Club affiché
- ✅ Date et heure formatées
- ✅ Type de cours avec durée et prix
- ✅ Élève avec âge (ex: "Lucas, 8 ans")
- ✅ Statut avec badge coloré
- ✅ Actions (Voir / Remplacer)

### Modales
- ✅ Fiche détaillée du cours complète
- ✅ Formulaire de demande de remplacement
- ✅ Liste des enseignants disponibles
- ✅ Raisons prédéfinies
- ✅ Zone de notes

---

## 🔄 Réinitialiser les Tests

Si vous voulez recommencer les tests à zéro :

```bash
# Supprimer toutes les demandes
docker-compose -f docker-compose.local.yml exec -T mysql-local \
  mysql -u root -prootpassword book_your_coach_local \
  -e "DELETE FROM lesson_replacements;"

# Recréer les demandes de test
docker-compose -f docker-compose.local.yml exec backend \
  php artisan test:replacements 3
```

---

## 📱 URLs Utiles

- **Login**: http://localhost:3000/login
- **Dashboard Enseignant**: http://localhost:3000/teacher/dashboard
- **Dashboard Club (manager)**: http://localhost:3000/club/dashboard
- **Planning Club**: http://localhost:3000/club/planning
- **phpMyAdmin**: http://localhost:8082 (user: root, pass: rootpassword)

---

## 🎉 C'est Tout !

Vous avez maintenant :
- ✅ 3 comptes enseignants prêts
- ✅ 5 demandes de remplacement (4 en attente, 1 acceptée)
- ✅ 618 cours sur 6 mois
- ✅ 29 élèves avec âges
- ✅ Système complet fonctionnel

**Bon test !** 🚀

---

**Note**: Tous les mots de passe sont "password" pour simplifier les tests.

