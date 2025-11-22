# Configuration des Textes Multilingues - Exemples

## 📋 Vue d'ensemble

Le plugin **AI Engine Multilang** parse automatiquement les textes configurés dans AI Engine avec le format `[fr]...[en]...[es]...` et extrait la traduction correspondant à la langue Polylang active.

**✅ Avantage** : Toutes les traductions sont centralisées dans la configuration AI Engine, pas dans le code du plugin.

---

## 🎯 Format de Base

```
[fr]Texte en français[en]English text[es]Texto en español
```

**Règles :**
- Les tags de langue sont entre crochets : `[fr]`, `[en]`, `[es]`, `[de]`, `[it]`, `[pt]`
- Pas d'espace après le tag de langue
- Pas de séparateur obligatoire entre les langues (le tag suivant délimite automatiquement)
- L'ordre des langues n'a pas d'importance

---

## 📝 Exemples de Configuration dans AI Engine

### 1. **Start Sentence** (Message de bienvenue)

Dans **AI Engine → Chatbots → [Votre Bot] → General → Start Sentence** :

```
[fr]Bonjour ! Je suis Reflexivo, ton coach personnel. Comment puis-je t'aider aujourd'hui ?[en]Hello! I am Reflexivo, your personal coach. How can I help you today?[es]¡Hola! Soy Reflexivo, tu coach personal. ¿Cómo puedo ayudarte hoy?
```

---

### 2. **Text Send** (Bouton d'envoi)

Dans **AI Engine → Chatbots → [Votre Bot] → UI → Text Send** :

```
[fr]Envoyer[en]Send[es]Enviar
```

---

### 3. **Text Clear** (Bouton tout recommencer)

Dans **AI Engine → Chatbots → [Votre Bot] → UI → Text Clear** :

```
[fr]Tout recommencer[en]Start over[es]Empezar de nuevo
```

---

### 4. **Text Input Placeholder** (Placeholder du champ de saisie)

Dans **AI Engine → Chatbots → [Votre Bot] → UI → Text Input Placeholder** :

```
[fr]Écris ton message...[en]Type your message...[es]Escribe tu mensaje...
```

---

### 5. **Header Subtitle** (Sous-titre du header)

Dans **AI Engine → Chatbots → [Votre Bot] → UI → Header Subtitle** :

```
[fr]Ton coach personnel[en]Your personal coach[es]Tu coach personal
```

---

### 6. **AI Name** (Nom du bot)

Dans **AI Engine → Chatbots → [Votre Bot] → General → AI Name** :

```
Reflexivo
```

**Note :** Le nom du bot peut rester identique dans toutes les langues, ou être traduit :

```
[fr]Reflexivo[en]Reflexivo[es]Reflexivo
```

---

### 7. **Text Compliance** (Texte de conformité RGPD)

Dans **AI Engine → Chatbots → [Votre Bot] → UI → Text Compliance** :

```
[fr]En utilisant ce chatbot, vous acceptez notre politique de confidentialité.[en]By using this chatbot, you accept our privacy policy.[es]Al utilizar este chatbot, aceptas nuestra política de privacidad.
```

---

## 🔧 Champs Supportés

Le plugin parse automatiquement ces champs s'ils contiennent des tags multilingues :

| Champ | Description |
|-------|-------------|
| `textSend` | Texte du bouton d'envoi |
| `textClear` | Texte du bouton "Tout recommencer" |
| `textInputPlaceholder` | Placeholder du champ de saisie |
| `startSentence` | Message de bienvenue initial |
| `headerSubtitle` | Sous-titre du header du chatbot |
| `textCompliance` | Texte de conformité RGPD |
| `aiName` | Nom du bot |
| `userName` | Nom de l'utilisateur (si applicable) |

---

## 🌍 Langues Supportées

Le plugin détecte automatiquement ces codes de langue :

- `[fr]` - Français
- `[en]` - Anglais
- `[es]` - Espagnol
- `[de]` - Allemand
- `[it]` - Italien
- `[pt]` - Portugais

---

## ✅ Comportement

### Si le texte contient des tags multilingues
Le plugin extrait automatiquement la traduction correspondant à la langue Polylang active.

**Exemple :**
- Langue active : `en`
- Texte configuré : `[fr]Bonjour[en]Hello[es]Hola`
- Résultat affiché : `Hello`

### Si le texte ne contient PAS de tags
Le texte est affiché tel quel, sans modification.

**Exemple :**
- Texte configuré : `Welcome`
- Résultat affiché : `Welcome` (dans toutes les langues)

---

## 🚫 Ce qui ne fonctionne PAS

### ❌ Tags mal fermés
```
[fr]Bonjour [en]Hello
```
**Problème :** Espace après `[fr]Bonjour` qui est inclus dans l'extraction.

### ❌ Tags invalides
```
[FR]Bonjour[EN]Hello
```
**Problème :** Les tags doivent être en minuscules.

### ❌ Langues non supportées
```
[fr]Bonjour[ja]こんにちは
```
**Problème :** Le japonais (`ja`) n'est pas dans la liste des langues supportées.

---

## 📊 Vérification et Debug

### Activer le mode debug
Dans `wp-config.php` :

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

### Logs générés
Le plugin log automatiquement dans `wp-content/debug.log` :

```
[AI Engine Multilang v1.0.5] UI Translator: Parsed multilang text for lang "en" | Result: "Hello"
[AI Engine Multilang v1.0.5] UI Translator: Parsed 5 multilang texts for lang "en"
```

---

## 💡 Bonnes Pratiques

1. **Toujours fournir toutes les langues** pour éviter les textes manquants :
   ```
   [fr]Texte FR[en]Text EN[es]Texto ES
   ```

2. **Pas d'espace inutile** après les tags :
   ```
   ✅ [fr]Bonjour[en]Hello
   ❌ [fr] Bonjour [en] Hello
   ```

3. **Tester dans chaque langue** après configuration pour vérifier l'affichage.

4. **Centraliser les traductions** : Ne pas mélanger textes traduits et textes fixes.

---

## 🔄 Migration depuis Traductions en Dur

Si vous aviez des traductions en dur dans le code, voici comment migrer :

### Avant (traductions en dur dans le code)
```php
'en' => array(
    'startSentence' => 'Hello! I am Reflexivo...',
),
```

### Après (configuration dans AI Engine)
Dans **AI Engine → Chatbots → Start Sentence** :
```
[fr]Bonjour ! Je suis Reflexivo...[en]Hello! I am Reflexivo...
```

---

## 📚 Voir Aussi

- [START-HERE.md](START-HERE.md) - Guide de démarrage complet
- [SPECS.md](SPECS.md) - Spécifications techniques détaillées
- [QUICK-START.md](QUICK-START.md) - Guide de démarrage rapide


