# 🌍 AI Engine Multilang by Elevatio

**Version:** 1.0.0  
**Plugin WordPress** pour gérer le multilingue sur AI Engine avec Polylang. Détecte automatiquement les changements de langue et traduit l'interface du chatbot (textes UI, Quick Actions).

> ⚠️ **Note importante :** Ce plugin est développé par Elevatio et n'est **PAS** affilié à Meow Apps (AI Engine). Il s'agit d'une extension tierce pour AI Engine.

---

## 🎯 Fonctionnalités

### ✅ V1.0.0 - Gestion Multilingue Complète

- 🌍 **Détection automatique changement de langue** Polylang
- 🔄 **Traduction automatique textes UI** (Send, Clear, Placeholder, Start Sentence, etc.)
- 🎯 **Traduction automatique Quick Actions** (format `"Texte [fr]|Text [en]|Texto [es]"`)
- 💬 **Popup intelligente** : Alerte si changement de langue avec conversation active
- 🗑️ **Réinitialisation sécurisée** : Efface le champ de saisie + trigger bouton AI Engine
- 💾 **localStorage** : Détection changement entre sessions sans cookie RGPD
- 📝 **Logs complets** : Console + debug.log avec version du plugin

### 🌐 Langues Supportées

- 🇫🇷 Français (FR)
- 🇬🇧 English (EN)
- 🇪🇸 Español (ES) *(à venir)*

---

## ⚙️ Installation

### Pré-requis

- WordPress 5.8+
- PHP 7.4+
- **[AI Engine Pro](https://ai-engine.meowapps.com/)** (plugin Meow Apps)
- **[Polylang](https://wordpress.org/plugins/polylang/)** (gratuit ou Pro)
- **[AI Engine Elevatio](https://github.com/cyrilgodon/ai-engine-elevatio)** v2.6.0+ (recommandé)

### Installation Classique

1. Télécharger `ai-engine-multilang.zip`
2. Aller dans **Extensions > Ajouter > Téléverser**
3. Activer le plugin
4. ✅ **C'est tout !** Le plugin fonctionne automatiquement

### Installation Développeur

```bash
cd wp-content/plugins/
git clone https://github.com/cyrilgodon/ai-engine-multilang.git
cd ai-engine-multilang/
composer install
npm install
npm run build:all
```

---

## 🚀 Utilisation

### 1. Configuration des Textes UI (Automatique)

Le plugin traduit automatiquement les textes de l'interface AI Engine selon la langue Polylang active.

**Textes traduits :**
- `textSend` : "Envoyer" / "Send" / "Enviar"
- `textClear` : "Tout recommencer" / "Start over" / "Empezar de nuevo"
- `textInputPlaceholder` : "Tape ton message..." / "Type your message..." / "Escribe tu mensaje..."
- `startSentence` : Message de démarrage personnalisé
- `headerSubtitle` : Sous-titre du chatbot

**Aucune configuration nécessaire !** ✨

### 2. Configuration des Quick Actions (Format Spécial)

Pour que les Quick Actions soient traduites, utilise le format suivant dans l'interface AI Engine :

```
Label: Oui, démarre (facile) [fr]|Yes, start (easy) [en]|Sí, comienza (fácil) [es]
Message: Je veux démarrer en difficulté facile
```

**Format :**
```
Texte FR [fr]|English text [en]|Texto ES [es]
```

**Exemple complet :**

```
Quick Action 1: Démarrage facile
┌────────────────────────────────────────────────────────────┐
│ Label:                                                     │
│ Oui, démarre (facile) [fr]|Yes, start (easy) [en]|       │
│ Sí, comienza (fácil) [es]                                 │
│                                                            │
│ Message:                                                   │
│ Je veux démarrer en difficulté facile                     │
└────────────────────────────────────────────────────────────┘
```

### 3. Changement de Langue

**Scénario 1 : Avec conversation active**
1. Utilisateur change la langue via sélecteur Polylang (FR → EN)
2. Page recharge
3. Popup s'affiche : *"You changed the language. To continue in English, please start a new conversation with Reflexivo."*
4. 2 boutons :
   - **"Start new conversation now"** → Efface le champ + redémarre
   - **"Finish current one"** → Continue dans l'ancienne langue

**Scénario 2 : Sans conversation**
1. Utilisateur change la langue
2. Pas d'alerte, changement silencieux
3. Chatbot prêt dans la nouvelle langue

---

## 🔧 Configuration Avancée

### Ajouter une Langue

```php
// Dans functions.php ou plugin custom
add_filter( 'eai_ml_supported_languages', function( $languages ) {
    $languages[] = 'de'; // Ajouter l'allemand
    return $languages;
}, 10 );

add_filter( 'eai_ml_translations_ui', function( $translations ) {
    $translations['de'] = array(
        'textSend' => 'Senden',
        'textClear' => 'Neu starten',
        // ... autres traductions
    );
    return $translations;
}, 10 );
```

### Désactiver le Module (si besoin)

```php
// Dans wp-config.php
define( 'EAI_ML_DISABLE_MODULE', true );
```

---

## 🐛 Dépannage

### ❌ Popup ne s'affiche pas

**Vérifier :**
1. Polylang est actif (`pll_current_language()` retourne une valeur)
2. Console logs : "Language change detected"
3. localStorage : pas de cooldown actif (clé `eai_ml_lang_alert_cooldown`)
4. Conversation active détectée (clés `mwai-*` dans localStorage)

**Solution :**
```javascript
// Console navigateur
localStorage.removeItem('eai_ml_lang_alert_cooldown');
location.reload();
```

### ❌ Traductions ne s'appliquent pas

**Vérifier :**
1. AI Engine Multilang actif
2. Polylang retourne langue correcte : `pll_current_language()`
3. Logs PHP : `[AI Engine Multilang] Hook registered`
4. Quick Actions format correct : `"Texte [fr]|Text [en]"`

**Logs à chercher :**
```
[AI Engine Multilang v1.0.0] Plugin initialized | Polylang: fr
[AI Engine Multilang v1.0.0] UI Translator: Hook registered (priority 10)
[AI Engine Multilang v1.0.0] QA Translator: Hook registered (priority 20)
```

### ❌ Langue ne change pas après restart

**Vérifier :**
1. Polylang fonctionne (URL change : `/fr/` → `/en/`)
2. Filtrage multilingue AI Engine Elevatio actif
3. Documents de référence existent dans nouvelle langue

---

## 📊 Architecture Technique

### Composants Principaux

```
ai-engine-multilang/
├── ai-engine-multilang.php          # Bootstrap + dépendances
├── includes/
│   ├── class-ui-translator.php      # Traduction textes UI (hook mwai_chatbot_params)
│   ├── class-qa-translator.php      # Traduction Quick Actions (hook mwai_chatbot_shortcuts)
│   ├── class-conversation-handler.php # Détection changement langue + popup
│   └── assets/
│       └── js/
│           └── conversation-handler.js # Logique détection côté client
├── composer.json                     # Dépendances PHP (Plugin Update Checker)
├── package.json                      # Dépendances NPM (build JavaScript)
└── build.js                          # Build système (DEV + PROD)
```

### Hooks WordPress Utilisés

**Filtres AI Engine :**
- `mwai_chatbot_params` (priorité 10) : Traduction textes UI
- `mwai_chatbot_shortcuts` (priorité 20) : Traduction Quick Actions

**Hooks WordPress :**
- `plugins_loaded` (priorité 20) : Initialisation du plugin
- `wp_enqueue_scripts` : Chargement du JavaScript

---

## 🤝 Contribution

### Workflow Git

```bash
# Créer une feature branch
git checkout -b feat/nouvelle-fonctionnalite

# Développer et builder
npm run build:all

# Tester
# Tests manuels + vérifier logs

# Commiter
git add .
git commit -m "feat: Description de la feature"

# Pusher et créer PR
git push origin feat/nouvelle-fonctionnalite
```

### Conventions

- **Commits sémantiques** : `feat:`, `fix:`, `docs:`, `chore:`
- **Versioning** : SemVer (MAJOR.MINOR.PATCH)
- **Code** : WordPress Coding Standards (PHPCS)
- **Build** : Toujours `npm run build:all` avant commit

---

## 📜 Changelog

### [1.0.0] - 2025-11-18

#### Added
- 🌍 **Gestion multilingue des conversations** : Détection automatique changement langue Polylang
- 📢 **Traduction automatique textes UI** : Send, Clear, Placeholder, Start Sentence, Header
- 🎯 **Traduction automatique Quick Actions** : Format `"Texte [fr]|Text [en]"`
- 💬 **Popup intelligente** : Alerte si changement de langue avec conversation active
- 🔄 **Réinitialisation sécurisée** : Efface champ + trigger bouton AI Engine
- 💾 **Détection localStorage** : Mémorisation dernière langue sans cookie RGPD
- 📝 **Logs complets** : Console + debug.log avec version du plugin
- 🌐 **Support 2 langues** : FR, EN (ES à venir)

---

## 📞 Support

- **Bugs** : Ouvrir une issue sur [GitHub](https://github.com/cyrilgodon/ai-engine-multilang/issues)
- **Questions** : Consulter la [documentation complète](docs/)
- **Contact** : [contact@elevatio.fr](mailto:contact@elevatio.fr)

---

## 📄 Licence

GPL-2.0-or-later

---

**Développé pour Elevatio** 🚀  
**Par Cyril Godon**  
https://elevatio.fr


