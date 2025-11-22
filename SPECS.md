# 📋 Spécifications Techniques - AI Engine Multilang by Elevatio

**Version:** 1.0.0  
**Date:** 2025-11-18  
**Auteur:** Elevatio / Cyril Godon

---

## 🎯 Objectif du Plugin

Permettre au chatbot AI Engine de **détecter automatiquement les changements de langue Polylang** et d'adapter l'interface (textes UI + Quick Actions) en conséquence. Si une conversation est en cours lors d'un changement de langue, l'utilisateur doit être alerté et invité à redémarrer une nouvelle discussion.

---

## 🏗️ Architecture Globale

### Vue d'Ensemble

```
┌──────────────────────────────────────────────────────────────┐
│                        WORDPRESS                             │
│  ┌────────────────────────────────────────────────────────┐  │
│  │                    POLYLANG                            │  │
│  │  pll_current_language() → 'fr' / 'en' / 'es'          │  │
│  └───────────────────┬────────────────────────────────────┘  │
│                      │ (PHP)                                 │
│  ┌───────────────────▼────────────────────────────────────┐  │
│  │            AI ENGINE MULTILANG                         │  │
│  │  ┌──────────────────────────────────────────────────┐  │  │
│  │  │  1. UI_Translator (hook mwai_chatbot_params)    │  │  │
│  │  │     → Traduit textSend, textClear, etc.         │  │  │
│  │  ├──────────────────────────────────────────────────┤  │  │
│  │  │  2. QA_Translator (hook mwai_chatbot_shortcuts) │  │  │
│  │  │     → Traduit labels Quick Actions              │  │  │
│  │  ├──────────────────────────────────────────────────┤  │  │
│  │  │  3. Conversation_Handler (wp_localize_script)   │  │  │
│  │  │     → Injecte langue vers JavaScript            │  │  │
│  │  └──────────────────────────────────────────────────┘  │  │
│  └───────────────────┬────────────────────────────────────┘  │
│                      │ (wp_localize_script)                  │
│                      ▼                                        │
│  ┌────────────────────────────────────────────────────────┐  │
│  │            JAVASCRIPT (Client-Side)                    │  │
│  │  ┌──────────────────────────────────────────────────┐  │  │
│  │  │  conversation-handler.js                         │  │  │
│  │  │  1. Récupère langue actuelle (eaiMLData)        │  │  │
│  │  │  2. Compare avec localStorage (dernière langue) │  │  │
│  │  │  3. Détecte changement de langue                │  │  │
│  │  │  4. Vérifie si conversation active (mwai-* keys)│  │  │
│  │  │  5. Affiche popup si besoin                     │  │  │
│  │  │  6. Trigger restart conversation (clic bouton)  │  │  │
│  │  └──────────────────────────────────────────────────┘  │  │
│  └────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────┘
```

### Composants Principaux

1. **PHP Backend (3 classes)**
   - `EAI_ML_UI_Translator` : Traduction textes UI
   - `EAI_ML_QA_Translator` : Traduction Quick Actions
   - `EAI_ML_Conversation_Handler` : Injection données + enqueue JS

2. **JavaScript Frontend**
   - `conversation-handler.js` : Détection changement + popup

3. **Storage**
   - `localStorage` : Mémorisation dernière langue
   - Pas de base de données (stateless)

---

## 📦 Dépendances

### Obligatoires (Hard Dependencies)

1. **AI Engine Pro** (Meow Apps)
   - Fournit les hooks `mwai_chatbot_params` et `mwai_chatbot_shortcuts`
   - Version minimale : Toute version récente avec React chatbot
   - Vérification : `class_exists( 'Meow_MWAI_Core' )`

2. **Polylang** (WP Syntex)
   - Fournit `pll_current_language()` pour détecter la langue
   - Version minimale : 2.0+
   - Vérification : `function_exists( 'pll_current_language' )`

### Recommandées (Soft Dependencies)

3. **AI Engine Elevatio** v2.6.0+
   - Fournit le filtrage multilingue des prompts
   - Pas obligatoire mais complémentaire
   - Vérification : `defined( 'EAI_VERSION' )` et `version_compare( EAI_VERSION, '2.6.0', '>=' )`

---

## 🔧 Fonctionnalités Détaillées

### 1. Traduction Automatique des Textes UI

**Hook utilisé :** `mwai_chatbot_params` (priorité 10)

**Classe :** `EAI_ML_UI_Translator`

**Fonctionnement :**

```php
add_filter( 'mwai_chatbot_params', 'eai_ml_translate_ui_texts', 10, 1 );

function eai_ml_translate_ui_texts( $params ) {
    $lang = pll_current_language(); // 'fr', 'en', 'es'
    
    // Si FR → pas de surcharge (valeurs par défaut AI Engine)
    if ( $lang === 'fr' ) {
        return $params;
    }
    
    // Charger les traductions pour la langue active
    $translations = get_translations_for_lang( $lang );
    
    // Merger avec params existants
    return array_merge( $params, $translations );
}
```

**Textes traduits :**

| Clé                      | FR (défaut AI Engine) | EN                    | ES                      |
|--------------------------|-----------------------|-----------------------|-------------------------|
| `textSend`               | Envoyer               | Send                  | Enviar                  |
| `textClear`              | Tout recommencer      | Start over            | Empezar de nuevo        |
| `textInputPlaceholder`   | Tape ton message...   | Type your message...  | Escribe tu mensaje...   |
| `startSentence`          | Message de démarrage  | Welcome message       | Mensaje de bienvenida   |
| `headerSubtitle`         | Sous-titre header     | Header subtitle       | Subtítulo header        |

**Extensibilité :**

```php
add_filter( 'eai_ml_translations_ui', function( $translations ) {
    $translations['de'] = array( /* Allemand */ );
    return $translations;
}, 10 );
```

---

### 2. Traduction Automatique des Quick Actions

**Hook utilisé :** `mwai_chatbot_shortcuts` (priorité 20)

**Classe :** `EAI_ML_QA_Translator`

**Format des labels :**

```
Texte français [fr]|English text [en]|Texto español [es]
```

**Exemples :**

```
Oui, démarre (facile) [fr]|Yes, start (easy) [en]|Sí, comienza (fácil) [es]
Aide-moi à réfléchir [fr]|Help me think [en]|Ayúdame a pensar [es]
```

**Parsing Regex :**

```javascript
// Pattern pour extraire la traduction pour langue active
const pattern = /([^|]+)\[(fr|en|es)\]/i;

// Exemple pour 'en' :
"Oui, démarre [fr]|Yes, start [en]|Sí [es]"
  → matches[1] = "Yes, start "
  → trim() = "Yes, start"
```

**Fonctionnement :**

```php
add_filter( 'mwai_chatbot_shortcuts', 'eai_ml_translate_qa_labels', 20, 2 );

function eai_ml_translate_qa_labels( $shortcuts, $args ) {
    $lang = pll_current_language();
    $pattern = '/([^|]+)\[' . $lang . '\]/i';
    
    foreach ( $shortcuts as &$shortcut ) {
        if ( preg_match( $pattern, $shortcut['label'], $matches ) ) {
            $shortcut['label'] = trim( $matches[1] );
        } else {
            // Fallback FR si langue non trouvée
            if ( preg_match( '/([^|]+)\[fr\]/i', $shortcut['label'], $fb ) ) {
                $shortcut['label'] = trim( $fb[1] );
            }
        }
    }
    
    return $shortcuts;
}
```

**Structure d'une Quick Action :**

```php
array(
    'label' => 'Texte traduit',     // Visible par l'utilisateur
    'prompt' => 'Message à envoyer', // Message envoyé au bot
    'icon' => 'icon-name',           // (optionnel)
)
```

---

### 3. Détection Changement de Langue + Popup

**Classe PHP :** `EAI_ML_Conversation_Handler`

**JavaScript :** `conversation-handler.js`

#### 3.1. Injection des Données PHP → JavaScript

```php
wp_localize_script( 'eai-ml-conversation-handler', 'eaiMLData', array(
    'currentLang'    => pll_current_language(), // 'fr', 'en', 'es'
    'pluginVersion'  => EAI_ML_VERSION,         // '1.0.0'
    'isDebug'        => WP_DEBUG,               // true/false
    'translations'   => array(                  // Textes popup par langue
        'fr' => array(
            'title'         => 'Changement de langue détecté',
            'message'       => 'Vous avez changé la langue...',
            'btnNewConv'    => 'Démarrer nouvelle discussion',
            'btnFinishCurr' => 'Terminer la discussion actuelle',
        ),
        'en' => array( /* ... */ ),
        'es' => array( /* ... */ ),
    ),
    'localStorageKey' => 'eai_ml_last_language',
) );
```

#### 3.2. Détection Changement (JavaScript)

**Algorithme :**

```javascript
function detectLanguageChange() {
    const currentLang = window.eaiMLData.currentLang; // Ex: 'en'
    const lastLang = localStorage.getItem('eai_ml_last_language'); // Ex: 'fr'
    
    // 1. Première visite : stocker et sortir
    if (!lastLang) {
        localStorage.setItem('eai_ml_last_language', currentLang);
        return;
    }
    
    // 2. Pas de changement : sortir
    if (lastLang === currentLang) {
        return;
    }
    
    // 3. Changement détecté : vérifier conversation active
    const hasActiveConv = checkActiveConversation(); // Voir 3.3
    
    if (!hasActiveConv) {
        // Pas de conversation → changement silencieux
        localStorage.setItem('eai_ml_last_language', currentLang);
        return;
    }
    
    // 4. Conversation active → afficher popup
    showLanguageChangePopup();
}
```

#### 3.3. Détection Conversation Active

**Méthode :**

AI Engine stocke les conversations dans `localStorage` avec des clés commençant par `mwai-`.

```javascript
function checkActiveConversation() {
    const keys = Object.keys(localStorage);
    const mwaiKeys = keys.filter(key => key.startsWith('mwai-'));
    return mwaiKeys.length > 0;
}
```

**Exemples de clés AI Engine :**

```
mwai-chatbot-123456-messages
mwai-chatbot-123456-context
mwai-chatbot-123456-timestamp
```

#### 3.4. Popup de Changement de Langue

**Design :**

```
┌──────────────────────────────────────────────────┐
│  Changement de langue détecté                    │
├──────────────────────────────────────────────────┤
│                                                  │
│  Vous avez changé la langue. Pour continuer en  │
│  français, veuillez démarrer une nouvelle       │
│  discussion avec Reflexivo.                      │
│                                                  │
│        ┌─────────────────┐  ┌─────────────────┐ │
│        │ Terminer actuelle│  │Nouvelle discussion│ │
│        └─────────────────┘  └─────────────────┘ │
│                                    (primary)     │
└──────────────────────────────────────────────────┘
```

**Comportement des boutons :**

1. **"Terminer la discussion actuelle"** :
   - Ferme la popup
   - Active cooldown 5 minutes (pas de re-popup)
   - Utilisateur continue dans l'ancienne langue

2. **"Démarrer nouvelle discussion"** :
   - Trigger le bouton "Clear" / "Start over" d'AI Engine
   - Efface le champ de saisie
   - Met à jour la dernière langue dans localStorage
   - Ferme la popup

**Cooldown Popup :**

```javascript
const COOLDOWN_KEY = 'eai_ml_lang_alert_cooldown';
const COOLDOWN_DURATION = 5 * 60 * 1000; // 5 minutes

function activateCooldown() {
    const cooldownEnd = Date.now() + COOLDOWN_DURATION;
    localStorage.setItem(COOLDOWN_KEY, cooldownEnd.toString());
}

function isCooldownActive() {
    const cooldownEnd = localStorage.getItem(COOLDOWN_KEY);
    if (!cooldownEnd) return false;
    
    const remaining = parseInt(cooldownEnd, 10) - Date.now();
    if (remaining > 0) return true;
    
    // Expiré, nettoyer
    localStorage.removeItem(COOLDOWN_KEY);
    return false;
}
```

#### 3.5. Réinitialisation Conversation (React-safe)

**Problème :** AI Engine utilise React, on ne peut pas manipuler le DOM directement.

**Solution :** Trigger le bouton natif "Clear" / "Start over" d'AI Engine.

```javascript
function restartConversation() {
    // 1. Trouver le bouton Clear d'AI Engine
    const clearButtonSelectors = [
        '.mwai-chatbot .mwai-clear-button',
        '.mwai-chatbot .mwai-reset-button',
        '.mwai-chatbot button[aria-label*="clear"]',
    ];
    
    let clearButton = null;
    for (const selector of clearButtonSelectors) {
        clearButton = document.querySelector(selector);
        if (clearButton) break;
    }
    
    // 2. Cliquer sur le bouton (trigger React)
    if (clearButton) {
        clearButton.click();
    } else {
        // Fallback : vider uniquement le champ de saisie
        const inputField = document.querySelector('.mwai-chatbot input[type="text"]');
        if (inputField) inputField.value = '';
    }
    
    // 3. Mettre à jour la dernière langue
    localStorage.setItem('eai_ml_last_language', window.eaiMLData.currentLang);
}
```

---

## 📊 Scénarios d'Utilisation

### Scénario 1 : Changement FR → EN (Avec Conversation)

1. **État initial** :
   - Langue : FR
   - Conversation active : 5 messages échangés
   - `localStorage['eai_ml_last_language']` = `"fr"`

2. **Action utilisateur** :
   - Utilisateur clique sur sélecteur Polylang : FR → EN
   - Page recharge (`pll_current_language()` retourne `"en"`)

3. **Détection JavaScript** :
   ```javascript
   currentLang = "en"
   lastLang = "fr" (localStorage)
   → Changement détecté !
   ```

4. **Vérification conversation** :
   ```javascript
   localStorage.getItem('mwai-chatbot-123456-messages') !== null
   → Conversation active détectée !
   ```

5. **Affichage popup** :
   - Textes en anglais (langue cible)
   - 2 boutons proposés

6. **Choix utilisateur A** : Clic "Start new conversation"
   - Trigger bouton Clear AI Engine
   - `localStorage['eai_ml_last_language']` = `"en"`
   - Chatbot redémarre en anglais

7. **Choix utilisateur B** : Clic "Finish current one"
   - Popup se ferme
   - Cooldown 5 minutes activé
   - Conversation continue en français (ancienne langue)

### Scénario 2 : Changement EN → ES (Sans Conversation)

1. **État initial** :
   - Langue : EN
   - Pas de conversation active
   - `localStorage['eai_ml_last_language']` = `"en"`

2. **Action utilisateur** :
   - Changement langue : EN → ES

3. **Détection JavaScript** :
   ```javascript
   currentLang = "es"
   lastLang = "en"
   → Changement détecté !
   ```

4. **Vérification conversation** :
   ```javascript
   Object.keys(localStorage).filter(k => k.startsWith('mwai-')).length === 0
   → Pas de conversation active
   ```

5. **Changement silencieux** :
   - Pas de popup
   - `localStorage['eai_ml_last_language']` = `"es"`
   - Chatbot prêt en espagnol

### Scénario 3 : Première Visite

1. **État initial** :
   - Langue : FR (détectée par Polylang)
   - Pas de `localStorage['eai_ml_last_language']`

2. **Détection JavaScript** :
   ```javascript
   currentLang = "fr"
   lastLang = null
   → Première visite
   ```

3. **Initialisation** :
   - `localStorage['eai_ml_last_language']` = `"fr"`
   - Pas d'alerte
   - Chatbot s'affiche en français

---

## 🧪 Tests et Validation

### Tests Manuels Requis

| # | Test | Résultat Attendu |
|---|------|------------------|
| 1 | Première visite FR | Chatbot en français, `localStorage` initialisé |
| 2 | Changement FR → EN sans conversation | Changement silencieux, textes UI en anglais |
| 3 | Changement FR → EN avec conversation | Popup s'affiche en anglais, 2 boutons |
| 4 | Clic "Start new conversation" | Conversation redémarre, champ vidé |
| 5 | Clic "Finish current one" | Popup ferme, cooldown actif, conversation FR continue |
| 6 | Changement langue pendant cooldown | Pas de nouvelle popup |
| 7 | Quick Action avec format multilingue | Label traduit selon langue active |
| 8 | Quick Action sans format multilingue | Label inchangé |
| 9 | Langue non supportée (ex: DE) | Fallback FR pour QA, textes UI par défaut |
| 10 | Désactivation Polylang | Plugin fonctionne en FR par défaut |

### Tests Automatisés (À Implémenter V1.1)

```php
// PHPUnit : Tests unitaires PHP
class EAI_ML_UI_Translator_Test extends WP_UnitTestCase {
    public function test_translate_ui_texts_fr() {
        // Mock pll_current_language() → 'fr'
        // Assert: params inchangés
    }
    
    public function test_translate_ui_texts_en() {
        // Mock pll_current_language() → 'en'
        // Assert: textSend = 'Send'
    }
}

// Jest : Tests unitaires JavaScript
describe('conversation-handler.js', () => {
    it('should detect language change', () => {
        localStorage.setItem('eai_ml_last_language', 'fr');
        window.eaiMLData.currentLang = 'en';
        // Assert: detectLanguageChange() returns true
    });
});
```

---

## 📝 Logs et Debug

### Logs PHP (debug.log)

**Activation :**

```php
// wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

**Format des logs :**

```
[AI Engine Multilang v1.0.0] Plugin initialized | Polylang: fr | Elevatio: 2.6.8
[AI Engine Multilang v1.0.0] UI Translator: Hook registered (priority 10) | Lang: fr
[AI Engine Multilang v1.0.0] QA Translator: Hook registered (priority 20)
[AI Engine Multilang v1.0.0] QA Translator: "Oui, démarre [fr]|Yes [en]" → "Yes" (lang: en)
[AI Engine Multilang v1.0.0] Conversation Handler: Script enqueued (conversation-handler.dev.min.js)
```

### Logs JavaScript (Console)

**Activation :** Automatique si `WP_DEBUG = true`

**Format :**

```javascript
[AI Engine Multilang v1.0.0] Initializing... {currentLang: "fr", version: "1.0.0", isDebug: true}
[AI Engine Multilang v1.0.0] No language change detected
[AI Engine Multilang v1.0.0] Language change detected: fr → en
[AI Engine Multilang v1.0.0] Active conversation detected: ["mwai-chatbot-123-messages", "mwai-chatbot-123-context"]
[AI Engine Multilang v1.0.0] Popup displayed
[AI Engine Multilang v1.0.0] User chose to restart conversation
[AI Engine Multilang v1.0.0] Clear button clicked
[AI Engine Multilang v1.0.0] Last language updated to: en
```

---

## 🔒 Sécurité

### Validation des Données

1. **Langue Polylang** :
   - Validée par Polylang (codes ISO 639-1)
   - Pas de sanitization nécessaire côté plugin

2. **localStorage** :
   - Lecture/écriture côté client uniquement
   - Pas de données sensibles stockées
   - Codes langue (2 chars) non-exploitables

3. **Traductions** :
   - Définies côté PHP (pas de XSS)
   - Échappement automatique par `wp_localize_script()`

### Permissions WordPress

- ✅ **Pas d'options admin** : Aucune interface de configuration
- ✅ **Pas de capabilities requises** : Fonctionne pour tous les utilisateurs
- ✅ **Pas de nonce nécessaires** : Aucune action côté serveur

---

## 🚀 Performance

### Optimisations

1. **Singleton Pattern** :
   - Classes instanciées une seule fois
   - Pas de re-création inutile

2. **Hook Priorités** :
   - UI Translator : priorité 10 (avant autres filtres)
   - QA Translator : priorité 20 (après autres filtres)

3. **JavaScript Optimisé** :
   - Build DEV : Minified (pas obfusqué)
   - Build PROD : Minified + Obfuscated
   - Chargé dans le footer (non-bloquant)

4. **Pas de requêtes DB** :
   - Aucune lecture/écriture en base de données
   - Tout géré en mémoire (PHP) et localStorage (JS)

### Métriques

| Métrique | Valeur |
|----------|--------|
| Temps exécution PHP | < 1ms |
| Temps exécution JS | < 10ms |
| Taille JS (DEV) | ~8KB |
| Taille JS (PROD) | ~6KB (obfusqué) |
| Requêtes HTTP | 0 (tout inline) |
| Requêtes DB | 0 |

---

## 📦 Build et Déploiement

### Build Système

**Scripts NPM :**

```json
{
  "scripts": {
    "watch": "node build.js --watch",
    "build:dev": "node build.js --dev",
    "build:prod": "node build.js --prod",
    "build:all": "node build.js --all",
    "clean": "node build.js --clean"
  }
}
```

**Fichiers générés :**

- `assets/js/conversation-handler.dev.min.js` (DEV : minified, pas obfusqué)
- `assets/js/conversation-handler.min.js` (PROD : minified + obfuscated)

**Workflow :**

```bash
# Développement
npm run watch   # Auto-rebuild à chaque modif

# Build final avant commit
npm run build:all

# Nettoyage
npm run clean
```

### Déploiement GitHub

1. Commit + push vers `main`
2. Créer release GitHub avec tag `1.0.0`
3. Plugin Update Checker détecte la nouvelle version
4. Utilisateurs reçoivent notification de mise à jour

Voir **[GITHUB-UPDATES-WORKFLOW.md](GITHUB-UPDATES-WORKFLOW.md)** pour détails.

---

## 🔮 Roadmap

### V1.1.0 (Q1 2025)

- [ ] Support complet Español (ES)
- [ ] Support Deutsch (DE)
- [ ] Option admin : Personnaliser messages popup
- [ ] Tests PHPUnit + Jest

### V1.2.0 (Q2 2025)

- [ ] Support WPML (en plus de Polylang)
- [ ] Export/Import traductions Quick Actions (JSON)
- [ ] Interface admin : Vue d'ensemble traductions

### V2.0.0 (Q3 2025)

- [ ] Migration conversation : Traduire messages existants (via LLM)
- [ ] Multi-bots : Gérer plusieurs chatbots indépendamment
- [ ] Analytics : Dashboard statistiques changements langue

---

## 📞 Support Technique

### Issues Connues (V1.0.0)

1. **Popup ne s'affiche pas si JavaScript désactivé** :
   - Pas de solution (JavaScript requis)
   - Comportement degraded : changement silencieux

2. **Sélecteur Polylang via cookie** :
   - Si Polylang utilise cookie + cache agressif, peut causer délai détection
   - Solution : Désactiver cache pour pages avec chatbot

3. **Quick Actions sans format multilingue** :
   - Labels restent inchangés (pas de fallback automatique)
   - Solution utilisateur : Ajouter tags `[fr]|[en]` manuellement

### Liens Utiles

- **Documentation complète** : [README.md](README.md)
- **Guide rapide** : [QUICK-START.md](QUICK-START.md)
- **Issues GitHub** : https://github.com/cyrilgodon/ai-engine-multilang/issues
- **Contact** : contact@elevatio.fr

---

**Document maintenu par :** Elevatio / Cyril Godon  
**Dernière mise à jour :** 2025-11-18  
**Version du plugin :** 1.0.0



