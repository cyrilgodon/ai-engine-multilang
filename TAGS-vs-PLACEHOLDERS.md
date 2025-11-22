# Tags de Langue vs Placeholders - Guide de Distinction

## 🎯 Vue d'ensemble

Ce document explique la différence entre les **tags de langue** et les **placeholders/injections** dans le système multilingue.

---

## 🏷️ Tags de Langue

### Format
Les tags de langue identifient les blocs de traduction :

```
[fr]Texte en français[en]English text[es]Texto en español
```

### Règles Strictes

Un tag de langue est **TOUJOURS** :
- Entre crochets `[]`
- Exactement **2 lettres minuscules** : `fr`, `en`, `es`, `de`, `it`, `pt`
- OU format **xx-yy** (4 lettres + trait d'union) : `en-us`, `fr-ca`, `pt-br`

### Exemples Valides

| Tag | Description |
|-----|-------------|
| `[fr]` | Français |
| `[en]` | Anglais (générique) |
| `[en-us]` | Anglais américain |
| `[en-gb]` | Anglais britannique |
| `[es]` | Espagnol (générique) |
| `[pt-br]` | Portugais brésilien |
| `[fr-ca]` | Français canadien |

---

## 🔧 Placeholders / Injections

### Format
Les placeholders sont des variables qui seront remplacées dynamiquement par AI Engine :

```
[prenom_utilisateur]
[nom_entreprise]
[date_session]
```

### Caractéristiques

Un placeholder :
- Entre crochets `[]`
- **Plus de 2 caractères** (généralement descriptif)
- Peut contenir des underscores `_`, des lettres, des chiffres
- **Jamais confondu** avec un tag de langue par le système

### Exemples Courants

| Placeholder | Description |
|-------------|-------------|
| `[prenom_utilisateur]` | Prénom de l'utilisateur |
| `[nom_utilisateur]` | Nom de l'utilisateur |
| `[nom_entreprise]` | Nom de l'entreprise |
| `[date_session]` | Date de la session |
| `[objectif_jour]` | Objectif du jour |
| `[compteur_sessions]` | Nombre de sessions |

---

## ✅ Utilisation Combinée (SÛRE)

Tu peux **mélanger sans souci** les tags de langue et les placeholders :

```
[fr]Bonjour [prenom_utilisateur], prêt·e à t'entraîner ?[en]Hello [prenom_utilisateur], ready to train?[es]¡Hola [prenom_utilisateur], listo/a para entrenar!
```

### Traitement par le Plugin

1. **Le plugin AI Engine Multilang** détecte la langue active (ex: `en`)
2. **Il extrait le texte** correspondant : `Hello [prenom_utilisateur], ready to train?`
3. **Le placeholder reste intact** : `[prenom_utilisateur]`
4. **AI Engine remplace ensuite** `[prenom_utilisateur]` par la valeur réelle (ex: "John")
5. **Résultat final** : `Hello John, ready to train?`

---

## 🔍 Comment le Système Fait la Différence ?

### Pattern de Détection des Tags de Langue

```regex
\[[a-z]{2}(?:-[a-z]{2})?\]
```

Ce pattern cherche spécifiquement :
- `[` + exactement 2 lettres minuscules + `]`
- OU `[` + 2 lettres + `-` + 2 lettres + `]`

### Pourquoi les Placeholders Ne Matchent Jamais

| Texte | Match ? | Raison |
|-------|---------|--------|
| `[fr]` | ✅ OUI | Exactement 2 lettres |
| `[en-us]` | ✅ OUI | Format xx-yy valide |
| `[prenom_utilisateur]` | ❌ NON | Plus de 2 lettres (19 caractères) |
| `[nom]` | ❌ NON | 3 lettres (pas 2) |
| `[fr-FR]` | ❌ NON | Majuscules (pas minuscules) |
| `[FR]` | ❌ NON | Majuscules |

---

## ⚠️ Cas Problématiques (TRÈS RARES)

### Placeholder avec Code de Langue

Si tu crées un placeholder qui ressemble exactement à un code de langue :

```
❌ MAUVAIS : [fr] comme placeholder
❌ MAUVAIS : [en] comme placeholder
```

**Pourquoi c'est un problème ?**
Le système va le détecter comme un tag de langue et non comme un placeholder.

**Solution :**
Utilise des noms descriptifs :

```
✅ BON : [langue_preference]
✅ BON : [code_langue]
✅ BON : [user_language]
```

---

## 📝 Bonnes Pratiques

### 1. Noms de Placeholders Descriptifs

```
✅ [prenom_utilisateur]
✅ [objectif_session]
✅ [nom_entreprise]

❌ [fr] (confusion avec tag de langue)
❌ [en] (confusion avec tag de langue)
```

### 2. Tags de Langue Toujours Minuscules

```
✅ [fr]Texte[en]Text
✅ [en-us]Text[en-gb]Text

❌ [FR]Texte[EN]Text (majuscules)
❌ [Fr]Texte[En]Text (mixte)
```

### 3. Ordre de Traitement Clair

```
[fr]Bonjour [prenom], tu as [nb_sessions] sessions[en]Hello [firstname], you have [nb_sessions] sessions
```

**Traitement en 2 étapes :**
1. **AI Engine Multilang** → Extrait selon langue active
2. **AI Engine** → Remplace les placeholders

---

## 🧪 Exemples Complets

### Exemple 1 : Texte Simple

**Configuration :**
```
[fr]Bonjour ![en]Hello![es]¡Hola!
```

**Résultat en français :** `Bonjour !`  
**Résultat en anglais :** `Hello!`  
**Résultat en espagnol :** `¡Hola!`

### Exemple 2 : Avec Placeholders

**Configuration :**
```
[fr]Bonjour [prenom_utilisateur], tu as [nb_sessions] sessions aujourd'hui.[en]Hello [prenom_utilisateur], you have [nb_sessions] sessions today.[es]¡Hola [prenom_utilisateur], tienes [nb_sessions] sesiones hoy!
```

**Résultat en français (après remplacement) :** `Bonjour Marie, tu as 3 sessions aujourd'hui.`  
**Résultat en anglais (après remplacement) :** `Hello Marie, you have 3 sessions today.`  
**Résultat en espagnol (après remplacement) :** `¡Hola Marie, tienes 3 sesiones hoy!`

### Exemple 3 : Codes de Langue Étendus

**Configuration :**
```
[en-us]Color and analyze[en-gb]Colour and analyse[fr]Couleur et analyser
```

**Si langue active = `en-us` :** `Color and analyze`  
**Si langue active = `en-gb` :** `Colour and analyse`  
**Si langue active = `en` (générique) :** Fallback vers `en-us` ou première occurrence `en-*`  
**Si langue active = `fr` :** `Couleur et analyser`

---

## 🚀 Fallback Intelligent

Si tu configures `[en-us]` mais que Polylang retourne `en` (code court), le système :

1. Cherche d'abord `[en]` (exact match)
2. Si pas trouvé, cherche `[en-*]` (n'importe quelle variante)
3. Utilise la première occurrence trouvée

**Exemple :**
```
[en-us]American text[fr]Texte français
```

Si langue = `en` → Le système extraira `American text` (fallback intelligent)

---

## 📚 Voir Aussi

- [CONFIGURATION-EXEMPLES.md](CONFIGURATION-EXEMPLES.md) - Exemples de configuration des champs
- [START-HERE.md](START-HERE.md) - Guide de démarrage complet
- [SPECS.md](SPECS.md) - Spécifications techniques détaillées


