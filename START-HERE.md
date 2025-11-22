# 🚀 START HERE - AI Engine Multilang by Elevatio

**Plugin complet et déployé en v1.0.7 !** 🎉

## ⚡ Version 1.0.7 - Fix Popup Réaffichée

✅ **CORRECTION APPLIQUÉE** : La popup ne se réaffiche plus en boucle
- Fix du bug où la popup s'affichait à chaque rechargement même si le bot était dans la bonne langue
- La langue est maintenant correctement mise à jour dans localStorage même si l'utilisateur choisit "Terminer la discussion actuelle"
- Plus de popup intempestive ! 🎉

---

## ⚡ Version 1.0.1 - Correction Polylang Pro

✅ **CORRECTION APPLIQUÉE** : Le plugin détecte maintenant correctement Polylang Pro à l'activation
- Support complet de `polylang-pro/polylang.php`
- Détection multi-méthodes robuste (constante, fonction, classe, plugins actifs)
- Message d'erreur clarifié

---

## ✅ Ce qui a été créé

### 📂 Structure Complète

```
ai-engine-multilang/
├── 📄 ai-engine-multilang.php           ✅ Fichier principal du plugin
├── 📂 includes/
│   ├── class-ui-translator.php          ✅ Traduction textes UI
│   ├── class-qa-translator.php          ✅ Traduction Quick Actions
│   ├── class-conversation-handler.php   ✅ Détection changement + popup
│   └── index.php                        ✅ Sécurité
├── 📂 assets/js/
│   ├── conversation-handler.js          ✅ Source JavaScript
│   ├── conversation-handler.dev.min.js  ✅ Build DEV (5KB)
│   ├── conversation-handler.min.js      ✅ Build PROD (13KB obfusqué)
│   └── index.php                        ✅ Sécurité
├── 📂 vendor/                           ✅ Plugin Update Checker (GitHub auto-update)
├── 📂 node_modules/                     ✅ Dépendances build (ne pas commiter)
├── 📄 build.js                          ✅ Système de build DEV/PROD
├── 📄 composer.json                     ✅ Dépendances PHP
├── 📄 package.json                      ✅ Dépendances NPM
├── 📄 uninstall.php                     ✅ Nettoyage désinstallation
├── 📄 .gitignore                        ✅ Git configuration
├── 📖 README.md                         ✅ Documentation utilisateur complète
├── 📖 CHANGELOG.md                      ✅ Historique des versions
├── 📖 QUICK-START.md                    ✅ Guide démarrage rapide
├── 📖 SPECS.md                          ✅ Spécifications techniques détaillées
└── 📖 GITHUB-UPDATES-WORKFLOW.md        ✅ Workflow releases GitHub
```

---

## 🎯 Prochaines Étapes (Toi)

### 1️⃣ Tester en Local (Recommandé)

**A. Uploader le plugin sur ton site de dev :**

```bash
# Via SFTP/FTP, uploader le dossier complet dans :
wp-content/plugins/ai-engine-multilang/
```

**B. Activer le plugin dans WordPress Admin :**

1. Va dans **Extensions**
2. Trouve **AI Engine Multilang by Elevatio**
3. Clique **Activer**

**C. Vérifier les logs :**

```php
// Dans wp-config.php (si pas déjà activé)
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

```bash
# Consulter wp-content/debug.log, chercher :
[AI Engine Multilang v1.0.0] Plugin initialized | Polylang: fr
[AI Engine Multilang v1.0.0] UI Translator: Hook registered
[AI Engine Multilang v1.0.0] QA Translator: Hook registered
[AI Engine Multilang v1.0.0] Conversation Handler: Script enqueued
```

**D. Tester les scénarios :**

✅ **Scénario 1 : Changement FR → EN avec conversation**
1. Démarre une conversation (envoie un message)
2. Change la langue Polylang : FR → EN
3. Vérifie que la popup s'affiche en anglais
4. Clique "Start new conversation now"
5. Vérifie que le chatbot redémarre

✅ **Scénario 2 : Changement EN → FR sans conversation**
1. Change la langue : EN → FR
2. Vérifie qu'il n'y a pas de popup
3. Vérifie que les textes du chatbot sont en français

✅ **Scénario 3 : Quick Actions multilingues**
1. Crée une Quick Action avec label : `"Oui [fr]|Yes [en]"`
2. Change la langue
3. Vérifie que le label change

**E. Console navigateur (F12) :**

```javascript
// Logs attendus :
[AI Engine Multilang v1.0.0] Initializing... {currentLang: "fr"}
[AI Engine Multilang v1.0.0] No language change detected
// OU
[AI Engine Multilang v1.0.0] Language change detected: fr → en
[AI Engine Multilang v1.0.0] Popup displayed
```

---

### 2️⃣ Créer le Repo GitHub

**A. Initialiser Git :**

```bash
cd "C:\Users\cyril\OneDrive - ZEOLITOP\Elevatio\Projets\Développements wordpress\plugin reflexivo\ai-engine-multilang"
git init
git add .
git commit -m "feat: Initial commit - AI Engine Multilang v1.0.0"
```

**B. Créer le repo sur GitHub :**

1. Va sur https://github.com/new
2. Repository name : `ai-engine-multilang`
3. Description : `Gestion multilingue pour AI Engine avec Polylang. Traduction automatique des textes UI et Quick Actions.`
4. Public ou Private (au choix)
5. **NE PAS** initialiser avec README (on a déjà)
6. Clique **Create repository**

**C. Pusher le code :**

```bash
git remote add origin https://github.com/cyrilgodon/ai-engine-multilang.git
git branch -M main
git push -u origin main
```

---

### 3️⃣ Créer la Première Release (V1.0.0)

**A. Sur GitHub Web UI :**

1. Va sur https://github.com/cyrilgodon/ai-engine-multilang/releases
2. Clique **"Draft a new release"**
3. **Tag version** : `1.0.0` (sans "v")
4. **Release title** : `Version 1.0.0 - Initial Release`
5. **Description** : Copie-colle le contenu du CHANGELOG pour v1.0.0
6. Clique **"Publish release"**

**B. Le plugin est maintenant auto-updatable !** 🎉

Les utilisateurs recevront les mises à jour automatiquement dans WordPress Admin.

---

## 📦 Déploiement sur Site de Prod

### Option A : Via WordPress Admin (Recommandé)

1. **Créer un ZIP** (sans node_modules/) :

```bash
cd "C:\Users\cyril\OneDrive - ZEOLITOP\Elevatio\Projets\Développements wordpress\plugin reflexivo"
# Créer le ZIP manuellement ou via 7-Zip/WinRAR
# Inclure : tout SAUF node_modules/ et .git/
```

2. **Uploader via WordPress Admin** :
   - **Extensions > Ajouter > Téléverser**
   - Sélectionner le ZIP
   - Activer

### Option B : Via SFTP/FTP

1. Uploader le dossier `ai-engine-multilang/` dans `wp-content/plugins/`
2. Activer dans WordPress Admin

---

## 🎨 Configuration Quick Actions (Important !)

Pour que les Quick Actions soient traduites, utilise ce format :

```
Label: Oui, démarre (facile) [fr]|Yes, start (easy) [en]|Sí, comienza (fácil) [es]
Message: Je veux démarrer en difficulté facile
```

**Étapes :**

1. Va dans **AI Engine > Chatbots > [Ton chatbot]**
2. Section **Quick Actions**
3. Édite chaque Quick Action avec le format `"Texte [fr]|Text [en]|Texto [es]"`
4. Enregistre

---

## 🐛 Dépannage

### ❌ Plugin ne s'active pas

**Cause :** Dépendances manquantes (AI Engine ou Polylang)

**Solution :**
1. Vérifie que **AI Engine Pro** est activé
2. Vérifie que **Polylang** est activé
3. Consulte le message d'erreur WordPress

### ❌ Popup ne s'affiche pas

**Checklist :**
- [ ] Polylang est actif et `pll_current_language()` retourne une valeur
- [ ] JavaScript chargé (F12 → Sources → conversation-handler.min.js)
- [ ] Pas de cooldown actif (`localStorage['eai_ml_lang_alert_cooldown']`)
- [ ] Conversation AI Engine active (clés `mwai-*` dans localStorage)

**Test manuel :**

```javascript
// Console navigateur (F12)
localStorage.removeItem('eai_ml_lang_alert_cooldown');
location.reload();
```

### ❌ Traductions ne s'appliquent pas

**Checklist :**
- [ ] Langue Polylang détectée correctement (`pll_current_language()`)
- [ ] Logs PHP : `[AI Engine Multilang] Hook registered`
- [ ] Quick Actions au format correct : `"Texte [fr]|Text [en]"`

**Consulter les logs :**

```bash
# wp-content/debug.log
tail -f debug.log | grep "AI Engine Multilang"
```

---

## 📚 Documentation Complète

- **[README.md](README.md)** : Documentation utilisateur complète
- **[QUICK-START.md](QUICK-START.md)** : Guide démarrage rapide
- **[SPECS.md](SPECS.md)** : Spécifications techniques détaillées
- **[CHANGELOG.md](CHANGELOG.md)** : Historique des versions
- **[GITHUB-UPDATES-WORKFLOW.md](GITHUB-UPDATES-WORKFLOW.md)** : Workflow releases

---

## ✅ Checklist Finale

Avant de déployer en production :

- [ ] Tests locaux passés (3 scénarios minimum)
- [ ] Logs PHP sans erreur
- [ ] Logs JavaScript sans erreur (F12 Console)
- [ ] Quick Actions traduites correctement
- [ ] Textes UI traduits correctement
- [ ] Popup s'affiche et fonctionne
- [ ] Repo GitHub créé
- [ ] Release v1.0.0 publiée
- [ ] Documentation à jour

---

## 🎉 Félicitations !

Le plugin **AI Engine Multilang by Elevatio** est prêt à être déployé !

**Prochaines étapes suggérées :**

1. ✅ Tester en local (dev.elevatio.fr ou local)
2. ✅ Créer repo GitHub + release v1.0.0
3. ✅ Déployer sur site de prod (Reflexivo)
4. 📝 Créer article blog annonçant la fonctionnalité
5. 📊 Monitorer les logs pour détecter les bugs

---

## 📞 Support

- **Bugs** : Ouvrir une issue sur [GitHub](https://github.com/cyrilgodon/ai-engine-multilang/issues)
- **Questions** : Consulter [README.md](README.md) ou [SPECS.md](SPECS.md)
- **Contact** : contact@elevatio.fr

---

**Plugin développé par Elevatio** 🚀  
**Par Cyril Godon**  
https://elevatio.fr

---

**Bon courage pour les tests et le déploiement ! 💪**


