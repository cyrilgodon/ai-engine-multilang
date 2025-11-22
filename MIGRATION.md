# 🔄 Guide de Migration - Filtre de Prompts Multilingues

**Date :** 2025-11-18  
**Version :** 1.0.5

---

## 📋 Vue d'ensemble

Le **filtre de prompts multilingues** a été migré depuis le plugin **AI Engine Elevatio** vers **AI Engine Multilang**.

Cette migration permet :
- ✅ Réutilisation du système multilingue dans d'autres projets
- ✅ Fonctionnement autonome (ne nécessite PAS AI Engine Elevatio)
- ✅ Compatibilité maintenue avec AI Engine Elevatio (interface `EAI_Pipeline_Nameable`)
- ✅ Interface d'administration pour configuration facile
- ✅ Activation/désactivation simple

---

## 🎯 Ce qui change

### Avant (AI Engine Elevatio)

Le filtre était dans :
```
ai-engine-elevatio/includes/class-multilingual-prompt-filter.php
```

Et chargé automatiquement avec priorité fixe à 5 :
```php
add_filter( 'mwai_ai_instructions', array( $filter, 'filter_prompt' ), 5, 2 );
```

### Après (AI Engine Multilang)

Le filtre est maintenant dans :
```
ai-engine-multilang/includes/class-prompt-filter.php
```

Avec configuration via interface admin : **Paramètres → Multilingue**
- Activation/désactivation
- Priorité du hook configurable
- Mode debug optionnel

---

## 🚀 Étapes de migration (Pour AI Engine Elevatio)

### 1. Supprimer le code du filtre

Dans `ai-engine-elevatio/ai-engine-elevatio.php`, **supprimer** :

```php
// SUPPRIMER CETTE SECTION
function eai_load_multilingual_prompt_filter() {
	require_once EAI_PLUGIN_DIR . 'includes/interface-pipeline-nameable.php';
	require_once EAI_PLUGIN_DIR . 'includes/class-multilingual-prompt-filter.php';
	
	$filter = EAI_Multilingual_Prompt_Filter::get_instance();
	add_filter( 'mwai_ai_instructions', array( $filter, 'filter_prompt' ), 5, 2 );
}
add_action( 'plugins_loaded', 'eai_load_multilingual_prompt_filter', 3 );
```

### 2. Supprimer les fichiers

Supprimer :
- `includes/class-multilingual-prompt-filter.php`
- `docs/MULTILINGUAL-PROMPT-FILTER.md` (si existe)
- `README-MULTILINGUAL-FILTER.md` (si existe)
- `tests/test-multilingual-filter.php` (si existe)

### 3. Garder l'interface (optionnel)

Si d'autres classes dans AI Engine Elevatio utilisent `EAI_Pipeline_Nameable`, **garder** :
```
includes/interface-pipeline-nameable.php
```

---

## 🔧 Configuration post-migration

### 1. Activer AI Engine Multilang

Aller dans **Extensions** → Activer **AI Engine Multilang by Elevatio**

### 2. Configurer les langues

Aller dans **Paramètres → Multilingue** :

1. **Langues supportées** : Cocher les langues actives (FR, EN, ES, etc.)
2. **Langue par défaut** : Choisir la langue fallback
3. **Filtrage de prompts** : Vérifier que c'est activé ✅
4. **Priorité du hook** : Laisser à 5 (valeur par défaut)
5. **Mode debug** : Activer si besoin de logs détaillés

### 3. Tester

1. Créer un prompt avec syntaxe multilingue :
   ```
   [LANG:FR]
   Bonjour ! Votre langue est {{LANGUAGE_NAME}}.
   [/LANG:FR]
   
   [LANG:EN]
   Hello! Your language is {{LANGUAGE_NAME}}.
   [/LANG:EN]
   ```

2. Changer de langue avec Polylang
3. Vérifier que seul le contenu de la langue active est envoyé

---

## 🧪 Tests de régression

### Avant le déploiement

1. **Test : Filtrage basique**
   - Prompt avec `[LANG:FR]` et `[LANG:EN]`
   - Vérifier que seule la langue active est conservée

2. **Test : Placeholders**
   - Utiliser `{{LANGUAGE}}` et `{{LANGUAGE_NAME}}`
   - Vérifier le remplacement correct

3. **Test : Cache**
   - Même prompt → doit utiliser le cache (check debug.log)
   - Changement de langue → doit recalculer

4. **Test : Compatibilité Elevatio**
   - Si Elevatio est présent, vérifier le pipeline de test
   - Le filtre doit apparaître dans "Tests & Validation"

5. **Test : Mode dégradé**
   - Syntaxe invalide → doit retourner le prompt complet
   - Pas d'erreur fatale

### Après le déploiement

1. Vérifier les logs (mode debug activé)
2. Comparer les métriques de tokens (doit économiser ~40%)
3. Tester avec plusieurs bots et langues

---

## 🔗 Compatibilité

### AI Engine Elevatio présent

Si AI Engine Elevatio est installé :
- ✅ Interface `EAI_Pipeline_Nameable` détectée automatiquement
- ✅ Le filtre apparaît dans le pipeline de test d'Elevatio
- ✅ Nom, icône et description affichés correctement

### AI Engine Elevatio absent

Si AI Engine Elevatio n'est PAS installé :
- ✅ Interface stub créée automatiquement
- ✅ Le filtre fonctionne normalement
- ✅ Pas d'erreur, pas de dépendance

---

## 📊 Métriques attendues

Avec le filtre multilingue actif :
- **Économie de tokens** : 30-40% par requête multilingue
- **Temps de réponse** : Identique (filtrage côté serveur)
- **Cache hit rate** : ~90% après période de chauffe

---

## 🆘 Troubleshooting

### Le filtre ne s'applique pas

1. **Vérifier l'activation** : Paramètres → Multilingue → "Activer le filtrage"
2. **Vérifier les dépendances** : Polylang et AI Engine installés ?
3. **Vérifier les logs** : Activer le mode debug

### Conflits de priorité

Si un autre plugin modifie `mwai_ai_instructions` :
1. Aller dans **Paramètres → Multilingue**
2. Ajuster la **Priorité du hook** (essayer 3 ou 10)
3. Tester

### Cache ne se met pas à jour

1. Désactiver temporairement le cache :
   - Modifier `class-prompt-filter.php`
   - Commenter les lignes avec `set_transient` et `get_transient`
2. Ou attendre 1h (expiration automatique)

---

## 📝 Checklist de migration

- [ ] Code du filtre supprimé d'AI Engine Elevatio
- [ ] Fichiers supprimés d'AI Engine Elevatio
- [ ] AI Engine Multilang v1.0.5+ activé
- [ ] Configuration des langues faite
- [ ] Tests de régression passés
- [ ] Métriques vérifiées
- [ ] Documentation mise à jour

---

## 🎓 Pour en savoir plus

- **Documentation complète** : Voir `README.md`
- **Spécifications** : Voir `SPECS.md`
- **Guide rapide** : Voir `QUICK-START.md`
- **Changelog** : Voir `CHANGELOG.md`


