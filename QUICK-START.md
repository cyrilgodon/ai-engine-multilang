# 🚀 Quick Start - AI Engine Multilang

**Guide de démarrage rapide** pour utiliser AI Engine Multilang en 5 minutes.

---

## ✅ Installation Express (3 étapes)

### 1️⃣ Installer les dépendances

Avant d'installer AI Engine Multilang, assure-toi d'avoir :

- ✅ **AI Engine Pro** (Meow Apps) : [https://ai-engine.meowapps.com/](https://ai-engine.meowapps.com/)
- ✅ **Polylang** (gratuit ou Pro) : [https://wordpress.org/plugins/polylang/](https://wordpress.org/plugins/polylang/)
- ✅ **AI Engine Elevatio** v2.6.0+ (recommandé) : [https://github.com/cyrilgodon/ai-engine-elevatio](https://github.com/cyrilgodon/ai-engine-elevatio)

### 2️⃣ Installer AI Engine Multilang

**Option A : Via WordPress Admin**
1. Télécharge `ai-engine-multilang.zip`
2. Va dans **Extensions > Ajouter > Téléverser**
3. Active le plugin

**Option B : Via FTP/SFTP**
```bash
# Uploader le dossier ai-engine-multilang/ dans :
wp-content/plugins/
```

### 3️⃣ C'est terminé !

✅ **Aucune configuration nécessaire**, le plugin fonctionne automatiquement.

---

## 🎯 Configuration des Quick Actions (Format Multilingue)

Pour que tes Quick Actions soient traduites, utilise ce format :

```
Label: Oui, démarre (facile) [fr]|Yes, start (easy) [en]|Sí, comienza (fácil) [es]
```

**Étapes dans AI Engine :**

1. Va dans **AI Engine > Chatbots > [Ton chatbot]**
2. Section **Quick Actions** (ou installe [MWAI Quick Actions](https://github.com/cyrilgodon/mwai-quick-actions))
3. Pour chaque Quick Action, écris le label au format multilingue :

```
┌─────────────────────────────────────────────────────────┐
│ Quick Action 1                                          │
├─────────────────────────────────────────────────────────┤
│ Label:                                                  │
│ Oui, démarre (facile) [fr]|Yes, start (easy) [en]|    │
│ Sí, comienza (fácil) [es]                              │
│                                                         │
│ Message:                                                │
│ Je veux démarrer en difficulté facile                  │
└─────────────────────────────────────────────────────────┘
```

4. Enregistre

✅ **Le plugin extraira automatiquement la bonne traduction selon la langue active !**

---

## 🌐 Test de Changement de Langue

### Scénario 1 : Avec conversation active

1. Démarre une conversation avec le chatbot (envoie un message)
2. Change la langue via le sélecteur Polylang (FR → EN)
3. La page recharge
4. 💬 **Popup s'affiche** : *"You changed the language. To continue in English, please start a new conversation with Reflexivo."*
5. Clique sur **"Start new conversation now"**
6. ✅ Le chatbot redémarre en anglais

### Scénario 2 : Sans conversation

1. Change la langue via Polylang (FR → EN)
2. Pas de popup, changement silencieux
3. ✅ Le chatbot est prêt en anglais

---

## 📊 Vérifier que ça Fonctionne

### ✅ Checklist de Vérification

1. **Polylang est actif** : Le sélecteur de langue est visible sur le site
2. **AI Engine fonctionne** : Le chatbot s'affiche normalement
3. **Les textes UI changent** : En changeant de langue, le bouton "Envoyer" devient "Send"
4. **Les Quick Actions changent** : Les labels des boutons sont traduits
5. **La popup s'affiche** : Changement de langue avec conversation active

### 🐛 En cas de problème

**Activer le mode debug :**

```php
// Dans wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

**Vérifier les logs :**

- **Fichier** : `wp-content/debug.log`
- **Chercher** : `[AI Engine Multilang`
- **Logs attendus** :
  ```
  [AI Engine Multilang v1.0.0] Plugin initialized | Polylang: fr
  [AI Engine Multilang v1.0.0] UI Translator: Hook registered (priority 10)
  [AI Engine Multilang v1.0.0] QA Translator: Hook registered (priority 20)
  [AI Engine Multilang v1.0.0] Conversation Handler: Script enqueued
  ```

**Console navigateur (F12) :**

```
[AI Engine Multilang v1.0.0] Initializing... {currentLang: "fr", version: "1.0.0"}
[AI Engine Multilang v1.0.0] No language change detected
```

---

## 🎨 Personnalisation Avancée

### Ajouter une Langue Supplémentaire

```php
// Dans functions.php de ton thème
add_filter( 'eai_ml_translations_ui', function( $translations ) {
    $translations['de'] = array( // Allemand
        'textSend' => 'Senden',
        'textClear' => 'Neu starten',
        'textInputPlaceholder' => 'Geben Sie Ihre Nachricht ein...',
        'startSentence' => 'Hallo! Ich bin Reflexivo, dein persönlicher Coach.',
    );
    return $translations;
}, 10 );
```

### Personnaliser les Textes du Popup

```php
add_filter( 'eai_ml_popup_translations', function( $translations ) {
    $translations['fr']['title'] = 'Attention !';
    $translations['fr']['message'] = 'Vous devez redémarrer pour changer de langue.';
    return $translations;
}, 10 );
```

---

## 📚 Prochaines Étapes

- 📖 Lire le **[README complet](README.md)** pour toutes les fonctionnalités
- 🐛 Consulter la **[section Dépannage](README.md#-dépannage)** si problème
- 🚀 Rejoindre le repo GitHub : [https://github.com/cyrilgodon/ai-engine-multilang](https://github.com/cyrilgodon/ai-engine-multilang)

---

**Prêt à utiliser ! 🎉**

Si tu as des questions, consulte la [documentation complète](README.md) ou ouvre une [issue sur GitHub](https://github.com/cyrilgodon/ai-engine-multilang/issues).

---

**Développé par Elevatio** 🚀  
https://elevatio.fr



