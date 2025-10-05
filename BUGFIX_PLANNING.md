# 🐛 Corrections - Planning Club

**Date:** 5 octobre 2025  
**Route:** `club/planning`

---

## ✅ Problème 1 : Impossible de créer un cours (RÉSOLU)

### Erreur
```
🚀 [API INTERCEPTOR] Erreur détectée: 
Object { status: 500, message: 'Class "App\\Http\\Controllers\\Api\\Teacher" not found', path: "/lessons" }
```

### Cause
Import manquant du modèle `Teacher` dans `LessonController.php`

### Solution
Ajout de l'import :
```php
use App\Models\Teacher;
```

**Fichier modifié:** `app/Http/Controllers/Api/LessonController.php`

---

## ⚠️ Problème 2 : Décalage d'affichage des cours (À TESTER)

### Description
Les cours ne s'affichent pas au bon endroit dans le planning visuel.

### Causes Possibles

1. **Format de date inconsistant**
   - Frontend envoie : `"2025-10-05 17:00:00"` 
   - Backend retourne : à vérifier (peut-être avec timezone ou format ISO)

2. **Calcul de position**
   - La fonction `getLessonPositionWithColumns()` calcule la position par rapport à `hourRanges.value[0]`
   - Si `hourRanges` commence à 6h mais le créneau ouvert à 9h, il peut y avoir un décalage

3. **Z-index ou superposition**
   - Les créneaux ouverts (z-10) vs cours (z-20)
   - Vérifier que les cours sont bien visibles

---

## 🧪 Tests à Effectuer

### Test 1 : Création de Cours ✅
1. Connectez-vous en tant que club
2. Allez sur `/club/planning`
3. Cliquez sur un créneau horaire dans la zone verte (09:00-18:00)
4. Remplissez le formulaire :
   - Enseignant : Sélectionner
   - Élève : Sélectionner
   - Type de cours : "Cours individuel enfant"
   - Heure : 17:00
   - Durée : 15 min
   - Prix : 15€
5. Cliquez "Créer le cours"
6. **Résultat attendu :** Le cours est créé sans erreur 500

### Test 2 : Affichage du Cours ⚠️
1. Après création, vérifier que le cours s'affiche dans le planning
2. **Vérifier :**
   - Le cours apparaît à 17:00 (pas décalé)
   - Le cours est dans la zone verte du créneau ouvert
   - Le cours est cliquable et modifiable

### Test 3 : Multiple Cours
1. Créer 2-3 cours à des heures différentes (ex: 10:00, 14:00, 17:00)
2. Vérifier que tous s'affichent correctement
3. Vérifier qu'ils ne se superposent pas incorrectement

---

## 🔍 Diagnostic Supplémentaire

Si le décalage persiste après le test :

### Vérifier le Format de Retour API

```bash
# Tester l'API directement
curl -X GET "http://localhost:8080/api/lessons?date_from=2025-10-05&date_to=2025-10-05" \
  -H "Authorization: Bearer {votre_token}" \
  -H "Content-Type: application/json"
```

Observer le format de `start_time` dans la réponse :
- ✅ Format attendu : `"2025-10-05 17:00:00"` ou `"2025-10-05T17:00:00Z"`
- ❌ Problème : Si timezone différente (ex: `"2025-10-05T17:00:00+02:00"`)

### Vérifier les Logs Console Frontend

Après création d'un cours, dans la console :
1. `✅ Cours créé avec succès`
2. Puis rechargement des données
3. Observer les logs de `getLessonPositionWithColumns()`

Ajouter temporairement dans `planning.vue` (ligne ~1485) :
```javascript
console.log('🎯 Position du cours:', {
  lessonTime: `${startHour}:${startMinute}`,
  calendarStart: calendarStartHour,
  offsetMinutes,
  topPx: top,
  lesson
})
```

---

## 💡 Solution Potentielle au Décalage

Si le problème persiste, modifier `getLessonPositionWithColumns` pour utiliser le `start_time` du premier créneau ouvert au lieu de `hourRanges[0]` :

```javascript
// Dans getLessonPositionWithColumns
const calendarStartHour = computed(() => {
  if (availableSlots.value.length > 0) {
    // Utiliser l'heure du premier créneau ouvert
    const earliestSlot = availableSlots.value.reduce((min, slot) => {
      const slotStart = parseInt(slot.start_time.split(':')[0])
      return slotStart < min ? slotStart : min
    }, 24)
    return earliestSlot
  }
  return hourRanges.value[0] || 6
})
```

---

## 📝 Prochaines Étapes

1. ✅ **Tester la création de cours** (doit fonctionner maintenant)
2. ⚠️ **Observer l'affichage** (position correcte ?)
3. 🔧 **Ajuster si nécessaire** (selon les résultats du test)

---

## 🆘 Si le Problème Persiste

1. **Copier les logs console** après création d'un cours
2. **Faire une capture d'écran** du planning avec le cours affiché
3. **Vérifier la réponse API** (`/api/lessons`) pour voir le format exact
4. **Signaler** avec ces informations

---

**Dernière mise à jour:** 5 octobre 2025  
**Status:** ✅ Problème 1 résolu, Problème 2 en cours de test
