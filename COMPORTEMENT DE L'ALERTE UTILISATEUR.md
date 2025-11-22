### 📢 2️⃣ COMPORTEMENT DE L'ALERTE UTILISATEUR

Q2.1 - Type d'alerte :

- [Banner non-bloquant en haut du chatbot avec bouton "Redémarrer"]

- Moins intrusif qu'une modal

- Permet de voir le contexte (messages existants)

- Style cohérent avec feedback-bar existante

- Alternative : Modal bloquante ?

Q2.2 - Contenu de l'alerte :

- Langue du message : [Nouvelle langue (celle choisie)]

- Ton : [Informatif et conversationnel]

- Messages suggérés :

   FR: "🌍 Vous avez changé de langue. Pour continuer en français, veuillez redémarrer une nouvelle conversation avec Reflexivo."

   EN: "🌍 You changed the language. To continue in English, please start a new conversation with Reflexivo."

   ES: "🌍 Has cambiado de idioma. Para continuar en español, inicia una nueva conversación con Reflexivo."

Q2.3 - Actions utilisateur :

- [Option 1 : Bouton unique "Redémarrer maintenant" (pas de choix Annuler)]

- Plus simple : l'utilisateur a déjà fait son choix en changeant la langue

- Le banner reste visible tant qu'il n'a pas redémarré

- Possibilité de fermer le banner (X) mais il réapparaîtra au prochain chargement tant qu'il y a conversation + langue différente

- Alternative : Ajouter bouton "Annuler" qui force retour à l'ancienne langue ?

------

### 🔄 3️⃣ RÉINITIALISATION DE LA CONVERSATION

Q3.1 - Méthode de réinitialisation :

- [Côté client uniquement : effacer localStorage AI Engine + recharger page]

- Effacer toutes les clés mwai-* du localStorage

- Mettre à jour cookie reflexivo_last_language avec nouvelle langue

- Recharger la page (location.reload())

- Le serveur PHP appliquera automatiquement la nouvelle langue Polylang

- Alternative : Aussi supprimer en base côté serveur ?

Q3.2 - Rechargement de la page :

- [OUI : recharger automatiquement après clic sur "Redémarrer"]

- Garantit cohérence (prompt, documents, filtrage langue)

- Évite bugs de synchronisation état client/serveur

- Expérience utilisateur claire (reset complet)

Q3.3 - Préservation de données :

- [Réinitialisation complète (clean slate)]

- Pas de données préservées entre conversations

- Utilisateur peut rappeler son contexte si besoin

- Plus simple et robuste

------

### 🎨 4️⃣ GESTION DES CAS PARTICULIERS

Q4.1 - Pas de conversation active :

- [Changement silencieux : aucune alerte, pas de banner]

- Mettre à jour cookie reflexivo_last_language silencieusement

- Le chatbot démarrera directement dans la nouvelle langue

- Toast discret (optionnel) : "Reflexivo est maintenant en [LANGUE]"

Q4.2 - Changement vers la même langue :

- [Ignorer complètement (ne rien faire)]

- Pas d'alerte, pas d'action

- Évite confusion utilisateur

Q4.3 - Multiples chatbots :

- [Réinitialiser tous les chatbots de la page]

- Effacer tout le localStorage mwai-*

- Cohérence globale

- Alternative : Identifier le bot actif ?

------

### 🎨 5️⃣ UX & AFFICHAGE

Q5.1 - Position du sélecteur Polylang :

- [Dans le header du site, hors du chatbot]

- Standard WordPress + Polylang

- Confirme ? Ou position custom ?

Q5.2 - Position du banner d'alerte :

- [En haut du chatbot (premier élément dans .mwai-conversation)]

- Visible immédiatement

- Contextualisé avec le chatbot

- Style :

    background: #fff3cd;

    border: 2px solid #ffc107;

    padding: 12px;

    border-radius: 6px;

    margin-bottom: 16px;

Q5.3 - Feedback visuel :

- [Transition instantanée avec loader natif navigateur pendant reload]

- Pas de spinner custom

- location.reload() suffit

- Alternative : Ajouter spinner avant reload ?

------

### 🛠️ 6️⃣ ASPECTS TECHNIQUES

Q6.1 - Compatibilité navigateurs :

- [Navigateurs modernes uniquement (Chrome/Firefox/Safari/Edge récents)]

- Pas de support IE11

- Utilisation ES6+ (const, arrow functions, localStorage)

Q6.2 - Logging & Debug :

- [OUI : Logs console JS avec version + logs PHP debug.log]

- Console :

    console.log('🌍 [AI Engine Elevatio v2.X.X] Language change detected: fr → en');

    console.log('💬 [AI Engine Elevatio v2.X.X] Active conversation found, showing alert');

- PHP :

    error_log('[AI Engine Elevatio v2.X.X] Language filter: detected language en');

Q6.3 - Hooks & Filtres :

- [OUI : Exposer hooks pour customisation]

- Filtres JS :

    *// Permettre customisation du message d'alerte*

    window.eaiMultilangAlertMessage = function(*message*, *oldLang*, *newLang*) {

     return message; *// Personnaliser ici*

    };

- Actions PHP :

    do_action('eai_multilang_conversation_reset', $old_lang, $new_lang);

    apply_filters('eai_multilang_alert_enabled', true, $bot_id);

Q6.4 - Fichiers & Structure :

- [Nouveau module autonome : multilang-conversation-handler.js + build système]

- Source : includes/multilang-conversation-handler.js

- Build : dist/multilang-conversation-handler.dev.min.js + .min.js

- Enqueue via fonction PHP dans ai-engine-elevatio.php

- Priorité 25 (après tous les autres scripts)

- Workflow build standard : npm run build:all

Q6.5 - Tests :

- [Tests manuels sur staging avec checklist complète]

- Scénarios à tester :

- ✅ FR → EN avec conversation active

- ✅ EN → ES avec conversation active

- ✅ FR → EN sans conversation

- ✅ FR → FR (même langue, doit ignorer)

- ✅ Fermer banner + recharger (banner réapparaît)

- ✅ Cliquer "Redémarrer" (conversation réinitialisée)

- Tests automatisés (optionnel) : Jest pour logique détection ?

------

### 📦 7️⃣ VERSIONING & LIVRAISON

Q7.1 - Version du plugin :

- [Bump version à 2.7.0 (nouvelle fonctionnalité majeure)]

- Suivre SemVer : MAJOR.MINOR.PATCH

- 2.7.0 = nouvelle feature multilang conversation handler

Q7.2 - Documentation :

- [README section + doc technique dédiée]

- Ajouter section dans README.md principal

- Créer docs/MULTILANG-CONVERSATION-HANDLER.md avec :

- Guide utilisateur

- Guide développeur (hooks, customisation)

- Troubleshooting

Q7.3 - Changelog :

- [Entrée détaillée dans CHANGELOG.md]

   \### [2.7.0] - 2025-11-18

   \#### Added

   \- 🌍 **Gestion multilangue des conversations** : Alerte automatique lors du changement de langue Polylang

   \- 🔄 **Réinitialisation intelligente** : Détection conversation active + banner informatif

   \- 🍪 **Cookie de tracking langue** : Détection changement entre sessions

   \- 📝 **Logs complets** : Console + debug.log avec version du plugin