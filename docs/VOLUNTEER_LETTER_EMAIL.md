# 📧 Système d'Envoi de Lettres de Volontariat par Email

## Vue d'ensemble

Système complet permettant aux clubs d'envoyer les lettres de volontariat par email, soit individuellement à un enseignant, soit en masse à tous les enseignants affiliés.

## ✅ Fonctionnalités implémentées

### 1. **Envoi individuel**
- Bouton "Envoyer par Email" dans le modal de prévisualisation de la lettre
- Envoi immédiat à l'enseignant sélectionné
- Email professionnel avec lettre en pièce jointe (PDF)
- Confirmation de succès ou notification d'erreur

### 2. **Envoi en masse**
- Bouton "Envoyer à tous" sur la page principale
- Envoi groupé à tous les enseignants du club
- Rapport détaillé : envoyés, échecs, ignorés
- Gestion des erreurs par enseignant

### 3. **Suivi des envois**
- Enregistrement de chaque envoi dans la base de données
- Statut : `pending`, `sent`, `failed`
- Date et heure d'envoi
- Message d'erreur en cas d'échec
- Traçabilité complète (qui a envoyé, quand, à qui)

### 4. **Génération PDF automatique**
- PDF généré automatiquement côté serveur
- Format A4, mise en page professionnelle
- Inclus dans l'email en pièce jointe
- Suppression automatique après envoi

## 🗄️ Modifications de la base de données

### Table `volunteer_letter_sends`

```sql
CREATE TABLE volunteer_letter_sends (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    club_id BIGINT UNSIGNED NOT NULL,
    teacher_id BIGINT UNSIGNED NOT NULL,
    sent_by_user_id BIGINT UNSIGNED NULL,
    recipient_email VARCHAR(255) NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (sent_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_club_teacher (club_id, teacher_id),
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at)
);
```

### Champs expliqués
- **club_id** : Club qui envoie la lettre
- **teacher_id** : Enseignant destinataire
- **sent_by_user_id** : Utilisateur (admin du club) qui a initié l'envoi
- **recipient_email** : Email du destinataire (au moment de l'envoi)
- **status** : État de l'envoi (pending, sent, failed)
- **error_message** : Détail de l'erreur en cas d'échec
- **sent_at** : Date et heure effectives de l'envoi

## 📁 Fichiers créés/modifiés

### Backend

#### **Migration**
- `database/migrations/2025_10_28_212731_create_volunteer_letter_sends_table.php`

#### **Modèles**
- ✅ `app/Models/VolunteerLetterSend.php` - **NOUVEAU** - Modèle pour tracer les envois

#### **Contrôleurs**
- ✅ `app/Http/Controllers/Api/VolunteerLetterController.php` - **NOUVEAU** - Gestion des envois
  - `sendToTeacher($teacherId)` - Envoi individuel
  - `sendToAll()` - Envoi en masse
  - `history()` - Historique des envois

#### **Mail**
- ✅ `app/Mail/VolunteerLetterMail.php` - **NOUVEAU** - Classe Mail pour l'envoi
- ✅ `resources/views/emails/volunteer-letter.blade.php` - **NOUVEAU** - Template email HTML

#### **Templates PDF**
- ✅ `resources/views/pdf/volunteer-letter.blade.php` - **NOUVEAU** - Template PDF

#### **Routes**
- ✅ `routes/api.php` - Ajout de 3 nouvelles routes :
  ```php
  Route::post('/volunteer-letters/send/{teacherId}', [VolunteerLetterController::class, 'sendToTeacher']);
  Route::post('/volunteer-letters/send-all', [VolunteerLetterController::class, 'sendToAll']);
  Route::get('/volunteer-letters/history', [VolunteerLetterController::class, 'history']);
  ```

### Frontend

#### **Pages**
- ✅ `frontend/pages/club/volunteer-letter.vue` - Ajout des boutons d'envoi et logique

## 🚀 Utilisation

### Envoi individuel

1. Accéder à `/club/volunteer-letter`
2. Cliquer sur un enseignant dans la liste
3. Le modal s'ouvre avec la prévisualisation
4. Cliquer sur **"Envoyer par Email"** (bouton vert)
5. Confirmation : "Lettre envoyée avec succès !"
6. L'enseignant reçoit un email avec la lettre en PDF

### Envoi en masse

1. Accéder à `/club/volunteer-letter`
2. Cliquer sur **"Envoyer à tous"** (bouton vert en haut à droite)
3. Confirmer l'action dans la popup
4. Le système envoie à tous les enseignants
5. Toast de résumé : "X envoyés, Y échecs, Z ignorés"
6. Détails dans la console

## 📧 Contenu de l'email

### Objet
```
Note d'Information au Volontaire - [Nom du Club]
```

### Corps de l'email (HTML)

- **Header violet/rose** avec titre
- Message personnalisé avec le nom de l'enseignant
- Explication du contenu de la lettre
- Liste des 4 points couverts (informations, assurances, défraiements, discrétion)
- Rappel de l'obligation d'informer les organismes de paiement
- Signature du club
- Footer avec mention automatique

### Pièce jointe

- **Nom** : `Note_Information_Volontaire.pdf`
- **Format** : PDF A4
- **Taille** : ~100-200 KB
- **Contenu** : Lettre complète conforme à la loi

## 🔒 Sécurité et validations

### Backend

1. **Authentification** : Middleware `auth:sanctum` + `club`
2. **Autorisation** : Seuls les admins de club peuvent envoyer
3. **Vérification** : L'enseignant doit appartenir au club
4. **Validation** : Email valide requis pour l'enseignant
5. **Informations légales** : Vérification avant envoi

### Frontend

1. **Boutons désactivés** si envoi en cours
2. **Confirmation** pour envoi en masse
3. **Messages d'erreur** explicites
4. **Gestion des cas limites** (pas d'email, pas d'enseignant)

## 📊 Rapport d'envoi en masse

### Structure de la réponse

```json
{
  "success": true,
  "message": "Envoi terminé : 5 envoyés, 1 échecs, 2 ignorés",
  "results": {
    "total": 8,
    "sent": 5,
    "failed": 1,
    "skipped": 2,
    "details": [
      {
        "teacher": "Jean Dupont",
        "email": "jean@example.com",
        "status": "sent",
        "message": "Envoyé avec succès"
      },
      {
        "teacher": "Marie Martin",
        "status": "skipped",
        "message": "Pas d'adresse email"
      },
      {
        "teacher": "Paul Durant",
        "email": "paul@invalid.com",
        "status": "failed",
        "message": "Invalid email address"
      }
    ]
  }
}
```

### Statuts possibles

- **sent** : Envoi réussi
- **failed** : Échec d'envoi (erreur SMTP, email invalide, etc.)
- **skipped** : Ignoré (pas d'email, enseignant inactif)

## 🛠️ Configuration requise

### Laravel Mail

Configurer dans `.env` :

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre.email@example.com
MAIL_PASSWORD=votre_mot_de_passe_app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=votre.email@example.com
MAIL_FROM_NAME="Activibe"
```

### Package DomPDF

✅ Déjà installé via Composer :
```bash
composer require barryvdh/laravel-dompdf
```

### Dossier temporaire

Le dossier `storage/app/temp/` est créé automatiquement pour stocker les PDFs temporaires.

## 🐛 Gestion des erreurs

### Erreurs possibles

1. **Informations légales incomplètes**
   - Message : "Les informations légales du club sont incomplètes"
   - Solution : Compléter le profil du club

2. **Enseignant sans email**
   - Statut : `skipped`
   - Solution : Ajouter un email à l'enseignant

3. **Erreur SMTP**
   - Statut : `failed`
   - Solution : Vérifier la configuration mail

4. **Enseignant non affilié**
   - Message : "Enseignant introuvable ou non affilié à votre club"
   - Solution : Vérifier l'affiliation

### Logs

Tous les envois et erreurs sont loggés dans `storage/logs/laravel.log` :

```php
Log::info('Lettre de volontariat envoyée', [
    'club_id' => $club->id,
    'teacher_id' => $teacher->id,
    'email' => $teacher->user->email
]);

Log::error('Erreur envoi lettre à ' . $email, [
    'error' => $e->getMessage()
]);
```

## 📈 Statistiques (API disponible)

### Route : `GET /club/volunteer-letters/history`

Retourne les 100 derniers envois du club :

```json
{
  "success": true,
  "sends": [
    {
      "id": 1,
      "club_id": 1,
      "teacher_id": 5,
      "recipient_email": "teacher@example.com",
      "status": "sent",
      "sent_at": "2025-10-28 14:30:00",
      "teacher": {
        "id": 5,
        "user": {
          "name": "Jean Dupont"
        }
      },
      "sent_by": {
        "name": "Admin Club"
      }
    }
  ]
}
```

## 🎨 Design et UX

### Boutons

#### Envoi individuel (modal)
- **Couleur** : Vert/Emerald (`bg-emerald-600`)
- **Icône** : Enveloppe
- **État loading** : Spinner animé + texte "Envoi..."
- **Position** : Entre "Imprimer" et "Télécharger PDF"

#### Envoi en masse (page)
- **Couleur** : Gradient Vert/Teal (`from-emerald-500 to-teal-600`)
- **Icône** : Enveloppes multiples
- **État loading** : Spinner animé + texte "Envoi en cours..."
- **Position** : En haut à droite du header

### Notifications

- **Succès** : Toast vert ✅
- **Warning** : Toast jaune ⚠️
- **Erreur** : Toast rouge ❌
- **Info** : Toast bleu ℹ️

## 🚦 Tests recommandés

### Test 1 : Envoi individuel simple
1. Se connecter comme admin de club
2. Compléter les informations légales
3. Ajouter un enseignant avec email valide
4. Envoyer la lettre
5. Vérifier la réception de l'email
6. Vérifier le PDF en pièce jointe

### Test 2 : Envoi en masse
1. Ajouter 3-5 enseignants
2. Laisser un enseignant sans email
3. Envoyer à tous
4. Vérifier le rapport (X envoyés, 1 ignoré)
5. Vérifier la réception des emails

### Test 3 : Gestion des erreurs
1. Tester sans informations légales
2. Tester avec email invalide
3. Tester avec configuration SMTP incorrecte

## 📝 TODO et améliorations futures

- [ ] Page d'historique complète dans l'interface
- [ ] Filtres sur l'historique (date, statut, enseignant)
- [ ] Statistiques visuelles (graphiques)
- [ ] Renvoi en cas d'échec (bouton "Réessayer")
- [ ] Prévisualisation de l'email avant envoi
- [ ] Templates personnalisables par club
- [ ] Planification d'envoi (envoi différé)
- [ ] Multi-destinataires CC/BCC
- [ ] Notification de lecture (si supporté)

## 🔗 Liens utiles

- **Package DomPDF** : https://github.com/barryvdh/laravel-dompdf
- **Laravel Mail** : https://laravel.com/docs/10.x/mail
- **Loi du 3 juillet 2005** : Loi belge sur les droits des volontaires

---

**Dernière mise à jour** : 28 octobre 2025  
**Version** : 1.0.0  
**Statut** : ✅ Complété et opérationnel

