# Changelog

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/lang/fr/).

---

## [1.0.7] - 2025-11-18

### 🐛 Corrections de Bugs

- **Fix popup réaffichée en boucle** : La popup de changement de langue ne se réaffiche plus en boucle quand l'utilisateur clique sur "Terminer la discussion actuelle"
  - **Cause** : La dernière langue n'était pas mise à jour dans localStorage lors du clic sur "Terminer la discussion actuelle"
  - **Solution** : Ajout de `localStorage.setItem(LS_KEY, CURRENT_LANG)` dans le handler du bouton `btnFinish`
  - **Impact** : Plus de popup intempestive après avoir choisi de terminer la conversation dans l'ancienne langue
  - Fichier modifié : `assets/js/conversation-handler.js` (ligne 379)

---

## [1.0.6] - 2025-11-18

### ⚠️ BREAKING CHANGE

- **Traductions UI depuis configuration AI Engine** : Les traductions des textes UI (startSentence, textSend, etc.) ne sont plus codées en dur dans le plugin
  - Les textes doivent maintenant être configurés dans AI Engine avec le format `[fr]Texte FR[en]Text EN[es]Texto ES`
  - Le plugin parse automatiquement ces textes et extrait la langue active
  - **Migration requise** : Ajouter les tags de langue dans la configuration AI Engine (voir CONFIGURATION-EXEMPLES.md)

### ✨ Nouvelles Fonctionnalités

- **Detection de présence du chatbot** : La popup de changement de langue ne s'affiche que si un chatbot est présent sur la page
- **Récupération du nom du bot** : Le nom du chatbot (aiName) est maintenant extrait des paramètres AI Engine et affiché dans la popup
- **Différenciation de contexte** : Deux messages différents selon le scénario
  - Cas 1 : Changement de langue sur la page actuelle (< 10 secondes)
  - Cas 2 : Arrivée sur la page avec une langue différente depuis le dernier échange
- **Noms complets des langues** : Affichage en toutes lettres (français, anglais, espagnol, etc.) dans les messages de la popup
- **Boutons contextuels** : Les boutons affichent les noms des langues source et cible

### 📚 Documentation

- **CONFIGURATION-EXEMPLES.md** : Nouveau guide complet avec exemples de configuration des textes multilingues dans AI Engine
  - Exemples pour tous les champs supportés (startSentence, textSend, textClear, etc.)
  - Bonnes pratiques et pièges à éviter
  - Guide de migration depuis les traductions en dur

### 🔧 Modifications

- Refactorisation complète de `class-ui-translator.php` pour parser les textes depuis AI Engine au lieu de les avoir en dur
- Ajout de la fonction `parse_multilang_text()` pour extraire les traductions selon la langue active
- Support de 8 champs UI : textSend, textClear, textInputPlaceholder, startSentence, headerSubtitle, textCompliance, aiName, userName
- Logging amélioré avec détails sur les textes parsés

---

## [1.0.5] - 2025-11-18

### ✨ Nouvelles Fonctionnalités

- **Filtre de prompts multilingues** : Ajout d'un système complet de filtrage des prompts par langue
  - Économie jusqu'à 40% de tokens en envoyant uniquement le contenu de la langue active
  - Support de la syntaxe `[LANG:XX]...[/LANG:XX]` pour les blocs de langue
  - Placeholders `{{LANGUAGE}}` et `{{LANGUAGE_NAME}}` remplacés automatiquement
  - Cache intelligent avec transients WordPress (1h)
  - Logging complet avec métriques d'économie de tokens
  - Mode dégradé en cas d'erreur

- **Page d'administration** : Nouvelle interface de configuration dans Paramètres → Multilingue
  - Configuration des langues supportées (FR, EN, ES, DE, IT, PT)
  - Langue par défaut configurable
  - Activation/désactivation du filtrage de prompts
  - Configuration de la priorité du hook (pour compatibilité avec d'autres plugins)
  - Mode debug pour le développement
  - Documentation intégrée de la syntaxe multilingue

### 🔧 Modifications

- **Migration depuis AI Engine Elevatio** : Le filtre de prompts multilingues est maintenant dans ce plugin
  - Permet la réutilisation pour d'autres projets
  - Fonctionne de manière autonome (ne nécessite PAS AI Engine Elevatio)
  - Compatible avec AI Engine Elevatio si présent (interface `EAI_Pipeline_Nameable`)
  - Code adapté avec préfixe `EAI_ML_` au lieu de `EAI_`

### 📝 Notes techniques

- Nouvelle classe : `EAI_ML_Prompt_Filter` (filtre de prompts)
- Nouvelle classe : `EAI_ML_Admin_Settings` (page d'administration)
- Interface optionnelle : Implémente `EAI_Pipeline_Nameable` si disponible (compatibilité Elevatio)
- Hook : `mwai_ai_instructions` (priorité configurable, défaut: 5)

---

## [1.0.4] - 2025-11-18

### 🐛 Fixed (Correction Critique)

- **Suppression `Requires Plugins:`** : Le header WordPress natif ne fonctionne PAS avec les plugins premium
  - Polylang Pro et AI Engine Pro ne sont PAS sur WordPress.org
  - WordPress ne peut donc PAS détecter ces dépendances via `Requires Plugins:`
  - Le plugin ne s'activait jamais à cause de cette limitation
- **Vérification au runtime** : Retour à une vérification simple mais efficace
  - Vérification dans `plugins_loaded` (après chargement de tous les plugins)
  - Si dépendances manquantes : le plugin ne fait rien (graceful degradation)
  - Pas d'erreur, pas de plantage, pas de notice admin invasive
  - Log debug si WP_DEBUG activé
- **Activation toujours possible** : Le plugin s'active maintenant SANS VÉRIFICATION
  - L'utilisateur peut activer le plugin même si Polylang/AI Engine manquent
  - Le plugin reste simplement inactif jusqu'à installation des dépendances

### 📝 Leçon Réelle

Le header `Requires Plugins:` de WordPress est INUTILE pour les plugins premium car :
1. Il ne fonctionne QUE pour les plugins du repo WordPress.org
2. Les plugins premium (Polylang Pro, AI Engine Pro, etc.) ne sont PAS détectables
3. WordPress bloque l'activation même si le plugin premium est installé

**Solution pragmatique** : Vérification runtime + graceful degradation (pas d'erreur).

---

## [1.0.3] - 2025-11-18

### 🧹 Refactoring (Simplification Majeure)

- **Suppression du code de vérification custom** : Tout le système de vérification manuelle des dépendances a été supprimé
  - ❌ Supprimé `eai_ml_check_dependencies()` (150+ lignes de code inutile)
  - ❌ Supprimé `eai_ml_check_elevatio_compatibility()`
  - ❌ Supprimé `eai_ml_runtime_dependencies_check()` et son hook `admin_notices`
  - ❌ Supprimé toute la logique custom de vérification à l'activation
- **Utilisation du système natif WordPress** : Le header `Requires Plugins: ai-engine, polylang` gère TOUT automatiquement
  - WordPress affiche le message d'erreur si dépendances manquantes
  - WordPress empêche l'activation si plugins requis absents
  - Aucun code PHP nécessaire pour gérer les dépendances
- **Code simplifié** : Le plugin passe de ~250 lignes à ~100 lignes (60% de réduction)
- **Best practices WordPress** : Utilisation exclusive des systèmes natifs WordPress

### 📝 Impact

**Avant v1.0.3** : 250+ lignes de code custom pour gérer les dépendances  
**Après v1.0.3** : 1 ligne de header (`Requires Plugins:`) gère tout automatiquement

### 🎯 Leçon Apprise

Toujours utiliser les systèmes natifs WordPress AVANT de créer des solutions custom. Le header `Requires Plugins:` existe depuis WordPress 6.5 et rend obsolète tout code de vérification manuelle.

---

## [1.0.2] - 2025-11-18

### 🐛 Fixed (Corrections Critiques)

- **Vérification Polylang à l'activation** : Polylang n'est PLUS vérifié au hook d'activation
  - **Raison** : Les fonctions/classes de Polylang ne sont pas encore chargées au moment de l'activation
  - **Solution** : Vérification déplacée au runtime via `admin_notices` (après `plugins_loaded`)
  - Le plugin s'active maintenant SANS ERREUR même si Polylang n'est pas encore chargé
- **Notice admin intelligente** : Affichage d'une notice d'erreur dans l'admin si Polylang manque au runtime
  - Notice rouge avec lien de téléchargement Polylang
  - Vérification uniquement après chargement complet des plugins

### 📝 Technical Details

- Hook `eai_ml_activate()` : Vérifie uniquement AI Engine (via `class_exists('Meow_MWAI_Core')`)
- Nouvelle fonction `eai_ml_runtime_dependencies_check()` : Vérifie Polylang via `admin_notices`
- Amélioration expérience utilisateur : Plugin activable, puis notice explicative si dépendance manquante

### 🎯 Impact Utilisateur

**Avant v1.0.2** : Impossible d'activer le plugin → Message d'erreur bloquant  
**Après v1.0.2** : Plugin s'active → Notice admin si Polylang manque (non-bloquant)

---

## [1.0.1] - 2025-11-18

### 🐛 Fixed (Corrections)

- **Détection Polylang Pro** : Amélioration de la détection de Polylang et Polylang Pro à l'activation du plugin
  - Vérification multiple : `POLYLANG_VERSION`, `pll_current_language()`, classe `Polylang`, et plugins actifs
  - Support explicite de `polylang-pro/polylang.php` en plus de `polylang/polylang.php`
  - Chargement automatique de `plugin.php` pour utiliser `is_plugin_active()`
- **Message d'erreur** : Clarification du message d'erreur si Polylang manquant ("Polylang ou Polylang Pro")

### 📝 Technical Details

- Fonction `eai_ml_check_dependencies()` améliorée avec détection multi-méthodes
- Hook `eai_ml_activate()` charge maintenant `wp-admin/includes/plugin.php` si nécessaire
- Compatibilité assurée avec Polylang gratuit ET Polylang Pro

---

## [1.0.0] - 2025-11-18

### ✨ Added (Nouvelles fonctionnalités)

- 🌍 **Gestion multilingue des conversations** : Détection automatique du changement de langue Polylang
- 📢 **Traduction automatique textes UI** : Traduction de tous les textes de l'interface AI Engine
  - `textSend` : "Envoyer" / "Send"
  - `textClear` : "Tout recommencer" / "Start over"
  - `textInputPlaceholder` : Placeholder du champ de saisie
  - `startSentence` : Message de démarrage du chatbot
  - `headerSubtitle` : Sous-titre du header
- 🎯 **Traduction automatique Quick Actions** : Support du format `"Texte [fr]|Text [en]|Texto [es]"`
  - Parsing automatique des labels multilingues
  - Extraction de la traduction selon langue active
  - Trim automatique des espaces
- 💬 **Popup intelligente changement de langue**
  - Détection conversation active via localStorage AI Engine
  - Affichage modal avec 2 options
  - Traduction du popup selon nouvelle langue
- 🔄 **Réinitialisation conversation sécurisée**
  - Effacement du champ de saisie utilisateur
  - Trigger du bouton "Tout recommencer" natif AI Engine
  - Compatible avec React (pas de race condition)
- 💾 **Système de détection localStorage**
  - Mémorisation dernière langue utilisée
  - Comparaison avec langue Polylang actuelle
  - Pas de cookie = conformité RGPD automatique
- 📝 **Logs complets pour debug**
  - Logs console JavaScript avec version du plugin
  - Logs PHP debug.log avec préfixe `[AI Engine Multilang]`
  - Métriques : langue détectée, traductions appliquées, actions utilisateur
- 🌐 **Support multilingue**
  - Français (FR) : Traductions complètes
  - English (EN) : Traductions complètes
  - Español (ES) : Préparé (traductions à ajouter)
- 🎨 **Architecture modulaire**
  - Classe `EAI_ML_UI_Translator` : Gestion textes UI
  - Classe `EAI_ML_QA_Translator` : Gestion Quick Actions
  - Classe `EAI_ML_Conversation_Handler` : Gestion détection + popup
- 🔧 **Build système**
  - Dual build : DEV (.dev.min.js) + PROD (.min.js obfusqué)
  - Watch mode pour développement
  - Scripts NPM standardisés
- 📚 **Documentation complète**
  - README.md avec exemples d'utilisation
  - QUICK-START.md pour démarrage rapide
  - Inline documentation (PHPDoc + JSDoc)
- 🔒 **Gestion des dépendances robuste**
  - Vérification AI Engine au chargement
  - Vérification Polylang au chargement
  - Compatibilité AI Engine Elevatio v2.6.0+
  - Désactivation gracieuse si dépendances manquantes
- 🚀 **Plugin Update Checker**
  - Auto-update via GitHub (yahnis-elsts)
  - Compatible avec structure plugins Reflexivo

### 🔧 Technical Details

- **Hook `mwai_chatbot_params`** (priorité 10) : Interception paramètres UI
- **Hook `mwai_chatbot_shortcuts`** (priorité 20) : Interception Quick Actions
- **Hook `plugins_loaded`** (priorité 20) : Initialisation APRÈS AI Engine et Elevatio
- **Regex parsing** : `/([^|]+)\[(fr|en|es)\]/i` pour extraction traductions
- **localStorage keys** :
  - `eai_ml_last_language` : Dernière langue utilisée
  - `mwai-*` : Conversations AI Engine (détection)
  - `eai_ml_lang_alert_cooldown` : Cooldown popup fermée
- **Compatibilité navigateurs** : Chrome/Firefox/Safari/Edge modernes
- **Performance** : < 10ms pour détection changement langue

### 📦 Dependencies

- **PHP** : >= 7.4
- **WordPress** : >= 5.8
- **AI Engine Pro** : Latest version (required)
- **Polylang** : 2.0+ (required)
- **AI Engine Elevatio** : >= 2.6.0 (recommended)
- **NPM Packages** :
  - `esbuild` : ^0.19.0 (build JavaScript)
  - `javascript-obfuscator` : ^4.1.0 (obfuscation PROD)
  - `chokidar` : ^3.5.3 (watch mode)
- **Composer Packages** :
  - `yahnis-elsts/plugin-update-checker` : ^5.6 (auto-update GitHub)

### 🧪 Testing

- ✅ Tests manuels sur staging
- ✅ Scénario FR → EN avec conversation active
- ✅ Scénario EN → FR sans conversation
- ✅ Scénario popup fermée (cooldown)
- ✅ Scénario Quick Actions traduites
- ✅ Scénario textes UI traduits
- ✅ Compatibilité AI Engine Elevatio 2.6.8
- ✅ Compatibilité Polylang 3.5+

---

## [Unreleased]

### 🔮 À venir (Roadmap)

#### V1.1.0
- [ ] Support complet Español (ES)
- [ ] Support Deutsch (DE) - Allemand
- [ ] Option admin : Activer/désactiver popup
- [ ] Option admin : Personnaliser messages popup
- [ ] Statistiques : Nombre changements de langue par utilisateur

#### V1.2.0
- [ ] Support WPML (en plus de Polylang)
- [ ] Détection langue navigateur (fallback si ni Polylang ni WPML)
- [ ] Export/Import traductions Quick Actions (JSON)
- [ ] Interface admin : Vue d'ensemble traductions

#### V2.0.0
- [ ] Migration conversation : Traduire messages existants vers nouvelle langue (via LLM)
- [ ] Multi-bots : Gérer plusieurs chatbots indépendamment
- [ ] Préférences utilisateur : Mémoriser choix "Ne plus afficher"
- [ ] Analytics : Dashboard statistiques changements langue

---

## Historique des Versions

### Notation Semantic Versioning

- **MAJOR** (X.0.0) : Changements incompatibles avec versions précédentes
- **MINOR** (0.X.0) : Nouvelles fonctionnalités compatibles
- **PATCH** (0.0.X) : Corrections de bugs compatibles

---

**Document maintenu par :** Elevatio / Cyril Godon  
**Dernière mise à jour :** 2025-11-18

