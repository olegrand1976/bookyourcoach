# 📅 Calendrier Rempli - Centre Équestre des Étoiles

**Date de création**: 24 octobre 2025  
**Période**: 6 mois (20 octobre 2025 → 24 avril 2026)  
**Statut**: ✅ **COMPLÉTÉ AVEC SUCCÈS**

---

## 🎯 Objectif

Remplir le calendrier du Centre Équestre des Étoiles avec :
- Des créneaux horaires réguliers
- Des enseignants qualifiés
- Des élèves variés (différents âges)
- Des cours planifiés sur 6 mois

---

## 📊 Résultats

### 📈 Statistiques Globales

- **Club**: Centre Équestre des Étoiles (ID: 3)
- **Créneaux horaires**: 8 créneaux actifs
- **Enseignants**: 5 enseignants
- **Élèves**: 28 élèves (13 existants + 15 nouveaux)
- **Cours créés**: 613 cours sur 6 mois
- **Période couverte**: 20/10/2025 → 24/04/2026

---

## 📅 Créneaux Horaires Disponibles

| Jour | Horaire | Description |
|------|---------|-------------|
| **Dimanche** | 09:00 - 17:00 | Matin et après-midi |
| **Lundi** | 14:00 - 18:00 | Après-midi |
| **Mardi** | 14:00 - 18:00 | Après-midi |
| **Mercredi** | 09:00 - 12:00 | Matin |
| **Mercredi** | 14:00 - 18:00 | Après-midi |
| **Jeudi** | 14:00 - 18:00 | Après-midi |
| **Vendredi** | 14:00 - 18:00 | Après-midi |
| **Samedi** | 09:00 - 17:00 | Toute la journée |

**Caractéristiques**:
- Type de cours: Cours individuel enfant
- Durée: 20 minutes par cours
- Prix: 18.00 €
- Maximum de 5 cours simultanés par créneau

---

## 👨‍🏫 Enseignants

### Enseignants Existants
1. **Marie Leroy**
   - Email: marie.leroy@centre-Équestre-des-Étoiles.fr
   - Spécialités: Équitation classique

2. **Jean Moreau**
   - Email: jean.moreau@centre-Équestre-des-Étoiles.fr
   - Spécialités: CSO, Dressage

### Nouveaux Enseignants Créés
3. **Sophie Rousseau**
   - Email: sophie.rousseau@centre-equestre-des-etoiles.fr
   - Spécialités: CSO, Dressage
   - Tarif horaire: 35.00 €

4. **Thomas Girard**
   - Email: thomas.girard@centre-equestre-des-etoiles.fr
   - Spécialités: Voltige, Poney
   - Tarif horaire: 35.00 €

5. **Emma Blanc**
   - Email: emma.blanc@centre-equestre-des-etoiles.fr
   - Spécialités: Initiation, Baby poney
   - Tarif horaire: 35.00 €

**Note**: Tous les comptes ont le mot de passe par défaut: `password`

---

## 👦 Élèves Créés

| Nom | Email | Âge | Niveau |
|-----|-------|-----|--------|
| Lucas Martin | lucas.martin@etoiles.com | 8 ans | Variable |
| Emma Dubois | emma.dubois@etoiles.com | 10 ans | Variable |
| Noah Bernard | noah.bernard@etoiles.com | 7 ans | Variable |
| Léa Thomas | lea.thomas@etoiles.com | 9 ans | Variable |
| Louis Robert | louis.robert@etoiles.com | 11 ans | Variable |
| Chloé Petit | chloe.petit@etoiles.com | 8 ans | Variable |
| Gabriel Richard | gabriel.richard@etoiles.com | 12 ans | Variable |
| Zoé Durand | zoe.durand@etoiles.com | 6 ans | Variable |
| Arthur Moreau | arthur.moreau@etoiles.com | 9 ans | Variable |
| Camille Simon | camille.simon@etoiles.com | 10 ans | Variable |
| Hugo Laurent | hugo.laurent@etoiles.com | 7 ans | Variable |
| Inès Lefebvre | ines.lefebvre@etoiles.com | 11 ans | Variable |
| Raphaël Michel | raphael.michel@etoiles.com | 8 ans | Variable |
| Manon Garcia | manon.garcia@etoiles.com | 9 ans | Variable |
| Tom Roux | tom.roux@etoiles.com | 10 ans | Variable |

**Total**: 15 nouveaux élèves  
**Niveaux**: Débutant, Intermédiaire, Avancé (assignés aléatoirement)  
**Mot de passe**: `password` pour tous

---

## 📚 Cours Créés

### Distribution des Cours
- **Total**: 613 cours sur 6 mois
- **Moyenne par semaine**: ~23 cours
- **Moyenne par jour ouvert**: ~3-4 cours
- **Statut**: Tous les cours sont "confirmés"
- **Paiement**: En attente

### Répartition Temporelle
Les cours sont répartis sur toute la période :
- **Octobre 2025**: ~40 cours
- **Novembre 2025**: ~100 cours
- **Décembre 2025**: ~100 cours
- **Janvier 2026**: ~100 cours
- **Février 2026**: ~100 cours
- **Mars 2026**: ~100 cours
- **Avril 2026**: ~73 cours

### Caractéristiques des Cours
- **Durée**: 20 minutes
- **Prix**: 18.00 €
- **Type**: Cours individuel enfant
- **Enseignant**: Assigné aléatoirement parmi les 5 enseignants
- **Élève**: Assigné aléatoirement parmi les élèves
- **Horaires**: Espacés de 30 minutes (permettant temps de préparation)

---

## 🔐 Accès Manager

**Email**: manager@centre-Équestre-des-Étoiles.fr  
**Mot de passe**: (déjà existant dans le système)  
**Rôle**: Club Manager

**Capacités**:
- Voir tous les cours du club
- Gérer les enseignants
- Gérer les élèves
- Voir les créneaux horaires
- Accéder au planning complet

---

## 🧪 Comment Tester

### 1. Connexion Manager
```
1. Accéder à http://localhost:3000/login
2. Email: manager@centre-Équestre-des-Étoiles.fr
3. Voir le dashboard club
4. Accéder au planning: /club/planning
```

### 2. Connexion Enseignant
```
1. Se connecter avec un compte enseignant (ex: marie.leroy@...)
2. Accéder au dashboard enseignant: /teacher/dashboard
3. Voir ses cours planifiés
4. Tester les demandes de remplacement
```

### 3. Visualiser le Calendrier
```
1. Depuis le dashboard club
2. Cliquer sur "Planning"
3. Sélectionner un créneau (ex: Samedi 09:00-17:00)
4. Voir la vue journalière avec tous les cours
```

### 4. Vérifications SQL
```sql
-- Total des cours
SELECT COUNT(*) FROM lessons WHERE club_id = 3 AND start_time >= NOW();

-- Cours par enseignant
SELECT t.id, u.name, COUNT(l.id) as nb_cours 
FROM teachers t 
INNER JOIN users u ON t.user_id = u.id 
LEFT JOIN lessons l ON t.id = l.teacher_id AND l.club_id = 3
WHERE u.email LIKE '%centre-equestre-des-etoiles%'
GROUP BY t.id, u.name;

-- Cours par jour de la semaine
SELECT DAYNAME(start_time) as jour, COUNT(*) as nb_cours
FROM lessons 
WHERE club_id = 3 AND start_time >= NOW()
GROUP BY DAYNAME(start_time), DAYOFWEEK(start_time)
ORDER BY DAYOFWEEK(start_time);

-- Prochains cours (10 premiers)
SELECT 
  DATE_FORMAT(l.start_time, '%d/%m/%Y %H:%i') as date_heure,
  ut.name as enseignant,
  us.name as eleve,
  l.price as prix
FROM lessons l
INNER JOIN teachers t ON l.teacher_id = t.id
INNER JOIN users ut ON t.user_id = ut.id
INNER JOIN students s ON l.student_id = s.id
INNER JOIN users us ON s.user_id = us.id
WHERE l.club_id = 3 AND l.start_time >= NOW()
ORDER BY l.start_time
LIMIT 10;
```

---

## 🚀 Commande de Génération

Pour regénérer ou créer pour un autre club :

```bash
# Syntaxe
docker-compose -f docker-compose.local.yml exec backend php artisan seed:club-calendar {club_id} {nb_mois}

# Exemple: Centre Équestre des Étoiles, 6 mois
docker-compose -f docker-compose.local.yml exec backend php artisan seed:club-calendar 3 6

# Exemple: Autre club, 12 mois
docker-compose -f docker-compose.local.yml exec backend php artisan seed:club-calendar 1 12
```

---

## 📝 Notes Importantes

### ⚠️ Points d'Attention
1. **Mot de passe par défaut**: Tous les nouveaux comptes ont le mot de passe `password`
2. **Emails de test**: Les emails des élèves sont des adresses de test (@etoiles.com)
3. **Dates de naissance**: Calculées automatiquement selon l'âge
4. **Niveau**: Assigné aléatoirement (débutant/intermédiaire/avancé)

### ✅ Avantages
- Planning réaliste avec espacement des cours
- Diversité des enseignants
- Variété d'âges des élèves
- Couverture complète de la semaine
- Facile à visualiser et tester

### 🔄 Pour Réinitialiser
Si besoin de tout supprimer et recommencer :

```sql
-- Supprimer les cours du club
DELETE FROM lessons WHERE club_id = 3;

-- Supprimer les élèves créés
DELETE FROM students WHERE club_id = 3 AND user_id IN (
  SELECT id FROM users WHERE email LIKE '%@etoiles.com'
);

-- Puis relancer la commande
```

---

## 🎉 Conclusion

Le calendrier du Centre Équestre des Étoiles est maintenant **complètement rempli** sur 6 mois avec :
- ✅ 613 cours planifiés
- ✅ 5 enseignants actifs
- ✅ 28 élèves inscrits
- ✅ 8 créneaux horaires configurés
- ✅ Système de remplacement opérationnel

**Le système est prêt pour les tests et démonstrations !** 🐴

---

**Créé le**: 24 octobre 2025  
**Par**: Script automatisé `SeedClubCalendar`  
**Contact Manager**: manager@centre-Équestre-des-Étoiles.fr

