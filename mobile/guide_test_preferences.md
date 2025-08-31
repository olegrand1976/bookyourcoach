# 🧪 Guide de Test - Fonctionnalité Préférences

## 🚀 Application en cours de lancement

L'application Flutter BookYourCoach est en cours de compilation et sera accessible sur :
**http://localhost:8080**

## 📋 Étapes de Test

### 1. **Accès à l'Application**
- Ouvrez votre navigateur
- Allez sur `http://localhost:8080`
- Attendez le chargement complet de l'application

### 2. **Test de la Connexion Étudiant**
- Cliquez sur "Se connecter" ou "Login"
- Utilisez des identifiants de test :
  - Email : `student@test.com`
  - Mot de passe : `password`
- Ou créez un nouveau compte étudiant

### 3. **Navigation vers les Préférences**
- Une fois connecté en tant qu'étudiant
- Regardez la barre de navigation en bas
- Cliquez sur l'onglet **"Préférences"** (icône ⚙️)

### 4. **Test de la Configuration des Préférences**

#### **Disciplines Préférées**
- ✅ Sélectionnez plusieurs matières (ex: Mathématiques, Physique, Informatique)
- ✅ Vérifiez que les chips se colorent en bleu quand sélectionnés
- ✅ Testez la désélection en cliquant à nouveau

#### **Niveaux Préférés**
- ✅ Choisissez vos niveaux d'étude (ex: Lycée, Supérieur)
- ✅ Vérifiez la sélection multiple

#### **Formats Préférés**
- ✅ Sélectionnez vos formats de cours (ex: Cours particulier, Cours en ligne)
- ✅ Testez différentes combinaisons

#### **Prix Maximum**
- ✅ Utilisez le slider pour définir votre budget
- ✅ Vérifiez que la valeur s'affiche en temps réel

### 5. **Test des Actions**

#### **Sauvegarde**
- ✅ Cliquez sur "Sauvegarder" dans la barre d'action
- ✅ Vérifiez le message de confirmation
- ✅ Rechargez la page pour vérifier la persistance

#### **Réinitialisation**
- ✅ Cliquez sur "Réinitialiser"
- ✅ Confirmez dans la boîte de dialogue
- ✅ Vérifiez que tous les filtres sont effacés

### 6. **Test du Widget de Filtre**

#### **Dans les Autres Écrans**
- ✅ Allez dans "Cours disponibles"
- ✅ Cherchez un bouton de filtre ou un widget de préférences
- ✅ Testez l'application automatique des préférences

#### **Fonctionnalités du Widget**
- ✅ **Filtres multiples** : Testez les différentes catégories
- ✅ **Prix dynamique** : Ajustez le slider
- ✅ **Préférences par défaut** : Cliquez sur "Appliquer mes préférences"
- ✅ **Réinitialisation** : Testez le bouton "Réinitialiser"

## 🎯 Points de Test Spécifiques

### **Interface Utilisateur**
- [ ] Design cohérent avec le reste de l'application
- [ ] Responsive sur différentes tailles d'écran
- [ ] Animations fluides
- [ ] Messages d'erreur clairs

### **Fonctionnalités**
- [ ] Sélection multiple fonctionnelle
- [ ] Sauvegarde des préférences
- [ ] Application des filtres automatiques
- [ ] Réinitialisation complète

### **Performance**
- [ ] Chargement rapide de l'écran
- [ ] Réactivité des interactions
- [ ] Pas de lag lors des sélections

## 🐛 Bugs à Surveiller

### **Problèmes Potentiels**
1. **Sélection multiple** : Vérifiez qu'on peut sélectionner/désélectionner
2. **Sauvegarde** : Vérifiez que les données persistent
3. **Navigation** : Vérifiez que l'onglet Préférences est accessible
4. **Filtres** : Vérifiez l'application automatique dans les autres écrans

### **Messages d'Erreur**
- Si vous voyez des erreurs de compilation, notez-les
- Si l'application ne se lance pas, vérifiez la console
- Si les préférences ne se sauvegardent pas, vérifiez la console réseau

## 📊 Données de Test

### **Préférences de Test Recommandées**
```
Disciplines : Mathématiques, Physique, Informatique
Niveaux : Lycée, Supérieur
Formats : Cours particulier, Cours en ligne
Prix : 50€
```

### **Scénarios de Test**
1. **Configuration complète** : Tous les champs remplis
2. **Configuration minimale** : Seulement quelques disciplines
3. **Configuration vide** : Aucune préférence sélectionnée
4. **Modification** : Changer les préférences après sauvegarde

## 🎉 Résultats Attendus

### **Succès**
- ✅ Interface intuitive et responsive
- ✅ Sélection multiple fonctionnelle
- ✅ Sauvegarde persistante
- ✅ Filtrage automatique dans les autres écrans
- ✅ Messages de confirmation clairs

### **Améliorations Possibles**
- [ ] Animations plus fluides
- [ ] Plus d'options de personnalisation
- [ ] Recommandations intelligentes
- [ ] Notifications basées sur les préférences

## 📞 Support

Si vous rencontrez des problèmes :
1. Vérifiez la console du navigateur (F12)
2. Notez les étapes pour reproduire le bug
3. Décrivez le comportement attendu vs observé

**Bon test ! 🚀**
