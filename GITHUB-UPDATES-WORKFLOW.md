# 🚀 GitHub Updates Workflow - AI Engine Multilang

**Workflow de mise à jour automatique** via GitHub avec Plugin Update Checker (Yahnis Elsts).

---

## 📦 Auto-Update depuis GitHub

Ce plugin utilise **Plugin Update Checker v5** pour permettre les mises à jour automatiques depuis GitHub, **SANS avoir besoin de publier sur WordPress.org**.

### ✅ Avantages

- 🔄 **Mises à jour automatiques** : Les utilisateurs voient les updates dans WordPress Admin
- 🏷️ **Releases GitHub** : Utilise les tags/releases GitHub comme source
- 📝 **CHANGELOG intégré** : Affiche le CHANGELOG dans WordPress
- 🔒 **Contrôle total** : Pas de review WordPress.org, déploiement instantané
- 🌐 **Repos privés supportés** : Fonctionne avec repos GitHub privés (token requis)

---

## 🔧 Configuration dans le Plugin

Le plugin est déjà configuré dans `ai-engine-multilang.php` :

```php
if ( class_exists( 'YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
    $eaiMLUpdateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/cyrilgodon/ai-engine-multilang',
        __FILE__,
        'ai-engine-multilang'
    );
    $eaiMLUpdateChecker->setBranch('main');
}
```

**✅ Aucune autre configuration nécessaire côté plugin !**

---

## 📋 Workflow de Release (Développeur)

### 1️⃣ Préparer la Release

**A. Mettre à jour le numéro de version**

```php
// Dans ai-engine-multilang.php (ligne 6)
* Version: 1.1.0
```

```php
// Dans ai-engine-multilang.php (ligne 28)
define( 'EAI_ML_VERSION', '1.1.0' );
```

```json
// Dans package.json
"version": "1.1.0"
```

**B. Mettre à jour le CHANGELOG**

```markdown
## [1.1.0] - 2025-XX-XX

### Added
- Nouvelle fonctionnalité X
- Support de Y

### Fixed
- Correction du bug Z
```

**C. Builder les assets**

```bash
npm install
npm run build:all
```

**D. Commiter les changements**

```bash
git add .
git commit -m "chore: Bump version to 1.1.0"
git push origin main
```

### 2️⃣ Créer la Release GitHub

**Option A : Via GitHub Web UI**

1. Va sur https://github.com/cyrilgodon/ai-engine-multilang/releases
2. Clique **"Draft a new release"**
3. **Tag version** : `1.1.0` (sans "v" devant)
4. **Release title** : `Version 1.1.0 - Description courte`
5. **Description** : Copie-colle le contenu du CHANGELOG pour cette version
6. ✅ Clique **"Publish release"**

**Option B : Via GitHub CLI**

```bash
# Installer GitHub CLI : https://cli.github.com/
gh release create 1.1.0 \
  --title "Version 1.1.0 - Description" \
  --notes "$(cat CHANGELOG.md | sed -n '/## \[1.1.0\]/,/## \[/p' | head -n -1)"
```

### 3️⃣ Les Utilisateurs Reçoivent la Mise à Jour

🎉 **C'est automatique !**

- ✅ La mise à jour apparaît dans **WordPress Admin > Tableau de bord > Mises à jour**
- ✅ Notification dans **Extensions** avec badge "Mise à jour disponible"
- ✅ Les utilisateurs peuvent cliquer "Mettre à jour maintenant"

---

## 🔒 Repos Privés (Optionnel)

Si ton repo GitHub est **privé**, les utilisateurs doivent configurer un **token d'accès** :

### Configuration côté plugin (développeur)

```php
$eaiMLUpdateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
    'https://github.com/cyrilgodon/ai-engine-multilang',
    __FILE__,
    'ai-engine-multilang'
);
$eaiMLUpdateChecker->setAuthentication('YOUR_GITHUB_TOKEN'); // Token avec accès repo
```

### Configuration côté utilisateur

**Méthode recommandée : Constante wp-config.php**

```php
// Dans wp-config.php
define( 'EAI_ML_GITHUB_TOKEN', 'ghp_xxxxxxxxxxxxxxxxxxxx' );
```

Puis dans le plugin :

```php
if ( defined( 'EAI_ML_GITHUB_TOKEN' ) ) {
    $eaiMLUpdateChecker->setAuthentication( EAI_ML_GITHUB_TOKEN );
}
```

---

## 📊 Versionning Sémantique

Utilise **Semantic Versioning** (SemVer) :

```
MAJOR.MINOR.PATCH

1.0.0 → 1.0.1 (PATCH)   : Correction de bugs uniquement
1.0.1 → 1.1.0 (MINOR)   : Nouvelles fonctionnalités compatibles
1.1.0 → 2.0.0 (MAJOR)   : Changements incompatibles (breaking changes)
```

### Exemples

- `1.0.0` → `1.0.1` : Fix bug traduction espagnol
- `1.0.1` → `1.1.0` : Ajout support WPML
- `1.1.0` → `2.0.0` : Changement architecture (breaking)

---

## 🧪 Tester les Updates Localement

### Forcer la Vérification des Mises à Jour

```php
// Ajouter temporairement dans functions.php
add_action( 'admin_init', function() {
    delete_site_transient( 'update_plugins' );
} );
```

Puis recharge **Extensions** dans WordPress Admin.

### Tester avec une Fausse Version

```php
// Modifier temporairement dans ai-engine-multilang.php
define( 'EAI_ML_VERSION', '0.9.0' ); // Version inférieure à la release GitHub
```

Recharge **Extensions** → La mise à jour devrait apparaître.

---

## 📚 Ressources

- **Plugin Update Checker** : https://github.com/YahnisElsts/plugin-update-checker
- **Semantic Versioning** : https://semver.org/
- **GitHub Releases** : https://docs.github.com/en/repositories/releasing-projects-on-github

---

## 🆘 Troubleshooting

### ❌ "Aucune mise à jour disponible"

**Causes possibles :**

1. **Pas de release GitHub** : Vérifie que la release est publiée
2. **Tag incorrect** : Utilise `1.1.0` et non `v1.1.0`
3. **Cache WordPress** : Supprime transient `update_plugins`
4. **Version plugin >= version release** : Vérifie `EAI_ML_VERSION`

**Solution :**

```bash
# Dans WordPress Admin
wp transient delete update_plugins

# Ou via PHP (wp-admin/admin.php?page=ai-engine-multilang&force_update=1)
delete_site_transient( 'update_plugins' );
```

### ❌ "Impossible de télécharger la mise à jour"

**Causes :**

1. **Repo privé sans token** : Ajoute un token d'accès GitHub
2. **Problème réseau** : Vérifie que le serveur peut accéder à GitHub
3. **ZIP trop gros** : Optimise les assets (exclure node_modules/)

---

**Workflow prêt ! 🚀**

Chaque fois que tu publies une **release GitHub**, les utilisateurs reçoivent automatiquement la mise à jour dans leur WordPress Admin.

---

**Développé par Elevatio**  
https://elevatio.fr



