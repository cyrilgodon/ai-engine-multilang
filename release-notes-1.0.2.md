## 🐛 Version 1.0.2 - Correction Critique Activation Plugin

### Problème Résolu

**v1.0.0-1.0.1** : Le plugin ne pouvait PAS s'activer car Polylang Pro n'était pas détecté au moment de l'activation (ses fonctions PHP ne sont pas encore chargées).

**v1.0.2** : Le plugin s'active maintenant SANS PROBLÈME. La vérification de Polylang se fait au runtime via une notice admin.

### Changements

- **Hook d'activation simplifié** : Vérifie uniquement AI Engine (via `class_exists()`)
- **Vérification Polylang déplacée** : Au runtime via `admin_notices` après `plugins_loaded`
- **Notice admin intelligente** : Si Polylang manque, affiche une notice rouge explicative avec lien de téléchargement
- **Expérience utilisateur améliorée** : Plugin activable immédiatement, notice uniquement si dépendance manquante

### Technique

- `eai_ml_activate()` : Vérifie seulement `class_exists('Meow_MWAI_Core')` (AI Engine)
- `eai_ml_runtime_dependencies_check()` : Nouvelle fonction appelée via `admin_notices`
- Vérification `function_exists('pll_current_language')` APRÈS chargement complet des plugins

### Impact

- ✅ Le plugin s'active maintenant sans erreur
- ✅ Si Polylang manque, une notice claire s'affiche dans l'admin
- ✅ Compatible Polylang gratuit ET Polylang Pro
- ✅ Pas de blocage à l'activation

---

**IMPORTANT** : Cette version corrige le bug critique empêchant l'activation du plugin. Mettez à jour immédiatement si vous utilisez v1.0.0 ou v1.0.1.


