# 🚀 Guide de Test Rapide - BookYourCoach

## 📱 Application en cours de lancement

L'application Flutter BookYourCoach est en cours de compilation et sera accessible sur :
**http://localhost:8080**

## 🎯 Test de la Fonctionnalité Préférences

### 1. **Accès à l'Application**
- Ouvrez votre navigateur
- Allez sur `http://localhost:8080`
- Attendez le chargement complet

### 2. **Test de Connexion Étudiant**
- Cliquez sur "Se connecter" ou "Login"
- Utilisez des identifiants de test :
  - Email : `student@test.com`
  - Mot de passe : `password`
- Ou créez un nouveau compte étudiant

### 3. **Navigation vers les Préférences**
- Une fois connecté en tant qu'étudiant
- Regardez la barre de navigation en bas
- Cliquez sur l'onglet **"Préférences"** (icône ⚙️)

### 4. **Test des Fonctionnalités**

#### **✅ Disciplines Préférées**
- Sélectionnez plusieurs matières (ex: Mathématiques, Physique, Informatique)
- Vérifiez que les chips se colorent en bleu
- Testez la désélection

#### **✅ Niveaux Préférés**
- Choisissez vos niveaux (ex: Lycée, Supérieur)
- Vérifiez la sélection multiple

#### **✅ Formats Préférés**
- Sélectionnez vos formats (ex: Cours particulier, Cours en ligne)
- Testez différentes combinaisons

#### **✅ Prix Maximum**
- Utilisez le slider pour définir votre budget
- Vérifiez que la valeur s'affiche en temps réel

### 5. **Test des Actions**

#### **💾 Sauvegarde**
- Cliquez sur "Sauvegarder" dans la barre d'action
- Vérifiez le message de confirmation
- Rechargez la page pour vérifier la persistance

#### **🔄 Réinitialisation**
- Cliquez sur "Réinitialiser"
- Confirmez dans la boîte de dialogue
- Vérifiez que tous les filtres sont effacés

## 🎨 Interface à Tester

### **Design**
- [ ] Interface moderne et intuitive
- [ ] Couleurs cohérentes (bleu, vert, gris)
- [ ] Typographie lisible
- [ ] Espacement approprié

### **Interactions**
- [ ] Sélection multiple fonctionnelle
- [ ] Feedback visuel immédiat
- [ ] Animations fluides
- [ ] Messages d'erreur clairs

### **Navigation**
- [ ] Onglet "Préférences" accessible
- [ ] Navigation fluide entre les écrans
- [ ] Boutons d'action évidents

## 🐛 Problèmes Potentiels

### **Si l'application ne se lance pas :**
1. Vérifiez la console du terminal
2. Assurez-vous que Flutter est installé
3. Vérifiez que Chrome est disponible

### **Si les préférences ne se sauvegardent pas :**
1. Vérifiez la console du navigateur (F12)
2. Vérifiez les erreurs réseau
3. Testez avec des données différentes

### **Si l'interface ne s'affiche pas correctement :**
1. Rechargez la page
2. Vérifiez la résolution d'écran
3. Testez sur un autre navigateur

## 📊 Données de Test Recommandées

### **Préférences de Test**
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
- ✅ Messages de confirmation clairs
- ✅ Design cohérent avec l'application

### **Fonctionnalités Implémentées**
- ✅ **20 disciplines** disponibles
- ✅ **5 niveaux** d'étude
- ✅ **5 formats** de cours
- ✅ **Slider de prix** dynamique
- ✅ **Sauvegarde** des préférences
- ✅ **Réinitialisation** complète

## 📞 Support

Si vous rencontrez des problèmes :
1. Vérifiez la console du navigateur (F12)
2. Notez les étapes pour reproduire le bug
3. Décrivez le comportement attendu vs observé

**Bon test ! 🚀**

---

## 🔗 Liens Utiles

- **Application** : http://localhost:8080
- **Documentation** : `test_preferences_feature.md`
- **Guide complet** : `guide_test_preferences.md`
