# Changelog

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/lang/fr/).

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

