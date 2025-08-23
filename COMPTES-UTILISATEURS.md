# 🔐 COMPTES UTILISATEUR - BookYourCoach

## Configuration Base de Données

-   **Base de données** : MySQL (Docker) - Port 3307
-   **URL API** : http://localhost:8081
-   **Frontend** : http://localhost:3001

## ✅ COMPTES ADMINISTRATEUR FONCTIONNELS

### 🛡️ Comptes Validés et Testés

1. **Compte Principal** ⭐

    - Email: `admin@bookyourcoach.com`
    - Mot de passe: `admin123`
    - Nom: Administrateur
    - **Statut** : ✅ **Mot de passe réinitialisé et testé**

2. **Compte Alternatif Nouveau** 🆕

    - Email: `superadmin@bookyourcoach.com`
    - Mot de passe: `superadmin123`
    - Nom: Super Administrateur
    - **Statut** : ✅ **Nouvellement créé et testé**

3. **Autres Comptes Admin Disponibles**
    - `pdupre@example.net` - Christophe Robert
    - `carre.josette@example.org` - Julien Bourgeois
    - `o.legrand1976@gmail.com` - Olivier Legrand

### 👨‍🏫 Enseignants/Coaches

1. **Coach Principal**

    - Email: `coach@bookyourcoach.fr`
    - Mot de passe: `coach123`
    - Nom: Jean-Luc Moreau

2. **Autres Enseignants** (mot de passe par défaut à réinitialiser)
    - `sophie.martin@bookyourcoach.com` - Sophie Martin
    - `jean.dubois@bookyourcoach.com` - Jean Dubois
    - `marie.leroy@bookyourcoach.com` - Marie Leroy
    - `pierre.bernard@bookyourcoach.com` - Pierre Bernard

### 👩‍🎓 Élèves

1. **Élève Principal**

    - Email: `eleve@bookyourcoach.fr`
    - Mot de passe: `eleve123`
    - Nom: Emma Leclerc

2. **Autres Élèves** (mot de passe par défaut à réinitialiser)
    - `alice.durand@email.com` - Alice Durand
    - `bob.martin@email.com` - Bob Martin
    - `charlotte.dupont@email.com` - Charlotte Dupont
    - `david.laurent@email.com` - David Laurent
    - `emma.rousseau@email.com` - Emma Rousseau

## 🔧 Commandes Utiles

### Réinitialiser un mot de passe

```bash
docker-compose exec app php artisan tinker --execute="
\$user = App\Models\User::where('email', 'EMAIL@EXEMPLE.COM')->first();
\$user->password = Hash::make('NOUVEAU_MDP');
\$user->save();
echo 'Mot de passe mis à jour';
"
```

### Tester la connexion API

```bash
curl -X POST http://localhost:8081/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@bookyourcoach.com", "password": "admin123"}'
```

## ✅ Tests de Connexion Validés

-   ✅ Admin principal : `admin@bookyourcoach.com` / `admin123`
-   ✅ Admin alternatif : `admin@bookyourcoach.fr` / `admin123`
-   ✅ Coach : `coach@bookyourcoach.fr` / `coach123`
-   ✅ Élève : `eleve@bookyourcoach.fr` / `eleve123`

La base de données MySQL est maintenant configurée et opérationnelle avec des comptes de test fonctionnels.
