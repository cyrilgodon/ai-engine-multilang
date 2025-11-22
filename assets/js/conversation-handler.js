/**
 * AI Engine Multilang - Conversation Handler (Client-Side)
 *
 * Détecte les changements de langue Polylang via localStorage et affiche une popup
 * intelligente si une conversation AI Engine est en cours.
 *
 * @package AI_Engine_Multilang
 * @since 1.0.0
 */

(function () {
    'use strict';

    /**
     * Configuration et variables globales.
     */
    const PLUGIN_VERSION = window.eaiMLData?.pluginVersion || '1.0.0';
    const IS_DEBUG = window.eaiMLData?.isDebug || false;
    const CURRENT_LANG = window.eaiMLData?.currentLang || 'fr';
    const TRANSLATIONS = window.eaiMLData?.translations || {};
    const LS_KEY = window.eaiMLData?.localStorageKey || 'eai_ml_last_language';
    const LS_TIMESTAMP_KEY = 'eai_ml_lang_change_timestamp';
    const COOLDOWN_KEY = 'eai_ml_lang_alert_cooldown';
    const COOLDOWN_DURATION = 5 * 60 * 1000; // 5 minutes
    const RECENT_CHANGE_THRESHOLD = 10 * 1000; // 10 secondes pour détecter changement sur page actuelle

    /**
     * Logger personnalisé avec préfixe et version.
     */
    const log = {
        info: (...args) => {
            if (IS_DEBUG) {
                console.log(`[AI Engine Multilang v${PLUGIN_VERSION}]`, ...args);
            }
        },
        warn: (...args) => {
            if (IS_DEBUG) {
                console.warn(`[AI Engine Multilang v${PLUGIN_VERSION}]`, ...args);
            }
        },
        error: (...args) => {
            console.error(`[AI Engine Multilang v${PLUGIN_VERSION}]`, ...args);
        },
    };

    /**
     * Vérifier si un chatbot AI Engine est présent sur la page.
     *
     * @returns {boolean} True si chatbot présent, false sinon.
     */
    function hasChatbotOnPage() {
        const chatbot = document.querySelector('.mwai-chatbot-container, .mwai-chatbot');
        if (chatbot) {
            log.info('Chatbot found on page');
            return true;
        }
        log.info('No chatbot on page');
        return false;
    }

    /**
     * Récupérer le nom du chatbot (aiName) depuis les paramètres AI Engine.
     *
     * @returns {string} Nom du chatbot ou 'Reflexivo' par défaut.
     */
    function getChatbotName() {
        try {
            const chatbotContainer = document.querySelector('.mwai-chatbot-container');
            if (!chatbotContainer) {
                log.warn('Chatbot container not found, using default name');
                return 'Reflexivo';
            }

            const dataParams = chatbotContainer.getAttribute('data-params');
            if (!dataParams) {
                log.warn('data-params not found, using default name');
                return 'Reflexivo';
            }

            const params = JSON.parse(dataParams);
            const botName = params.aiName || 'Reflexivo';
            log.info('Chatbot name retrieved:', botName);
            return botName;
        } catch (e) {
            log.error('Error getting chatbot name:', e);
            return 'Reflexivo';
        }
    }

    /**
     * Compter le nombre de messages dans la conversation en attendant que le DOM soit chargé.
     * Utilise un MutationObserver pour détecter quand les messages sont ajoutés.
     *
     * @param {Function} callback - Fonction appelée avec le nombre de messages
     * @param {number} timeout - Timeout en ms (défaut: 2000ms)
     */
    function waitForMessagesAndCount(callback, timeout = 2000) {
        let callbackCalled = false; // Flag pour éviter les appels multiples

        // Fonction pour compter les messages
        const countMessages = () => {
            const messages = document.querySelectorAll('.mwai-reply');
            return messages.length;
        };

        // Fonction pour appeler le callback une seule fois
        const callOnce = (count) => {
            if (!callbackCalled) {
                callbackCalled = true;
                callback(count);
            }
        };

        // Vérifier immédiatement
        const initialCount = countMessages();
        if (initialCount > 1) {
            log.info(`Messages found immediately: ${initialCount}`);
            callOnce(initialCount);
            return;
        }

        // Sinon, attendre avec MutationObserver
        log.info('Waiting for messages to load in DOM...');

        const observer = new MutationObserver(() => {
            const count = countMessages();
            if (count > 0) {
                log.info(`Messages loaded: ${count}`);
                observer.disconnect();
                clearTimeout(timeoutId);
                callOnce(count);
            }
        });

        // Observer les changements dans le body
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        // Timeout de sécurité
        const timeoutId = setTimeout(() => {
            observer.disconnect();
            const finalCount = countMessages();
            log.info(`Timeout reached, final count: ${finalCount}`);
            callOnce(finalCount);
        }, timeout);
    }

    /**
     * Vérifier si une conversation AI Engine est active ET contient plusieurs messages.
     *
     * Détecte la présence de clés localStorage commençant par "mwai-" (AI Engine)
     * ET vérifie qu'il y a plus d'un message dans la conversation.
     *
     * @param {Function} callback - Fonction appelée avec true/false
     */
    function hasActiveConversation(callback) {
        try {
            const keys = Object.keys(localStorage);
            const mwaiKeys = keys.filter((key) => key.startsWith('mwai-'));

            if (mwaiKeys.length === 0) {
                log.info('No active conversation found (no localStorage keys)');
                callback(false);
                return;
            }

            // Attendre que les messages soient chargés et les compter
            waitForMessagesAndCount((messageCount) => {
                // Ne considérer comme "conversation active" que s'il y a au moins 2 messages
                // (1 message = juste le message initial du bot, pas besoin de popup)
                if (messageCount <= 1) {
                    log.info(`Only ${messageCount} message(s), no popup needed`);
                    callback(false);
                    return;
                }

                log.info(`Active conversation detected with ${messageCount} messages`);
                callback(true);
            });
        } catch (e) {
            log.error('Error checking active conversation:', e);
            callback(false);
        }
    }

    /**
     * Vérifier si le cooldown est actif (popup fermée récemment).
     *
     * @returns {boolean} True si cooldown actif, false sinon.
     */
    function isCooldownActive() {
        try {
            const cooldownEnd = localStorage.getItem(COOLDOWN_KEY);
            if (!cooldownEnd) {
                return false;
            }

            const now = Date.now();
            const remaining = parseInt(cooldownEnd, 10) - now;

            if (remaining > 0) {
                log.info(`Cooldown active: ${Math.round(remaining / 1000)}s remaining`);
                return true;
            }

            // Cooldown expiré, nettoyer
            localStorage.removeItem(COOLDOWN_KEY);
            return false;
        } catch (e) {
            log.error('Error checking cooldown:', e);
            return false;
        }
    }

    /**
     * Activer le cooldown (après fermeture popup).
     */
    function activateCooldown() {
        try {
            const cooldownEnd = Date.now() + COOLDOWN_DURATION;
            localStorage.setItem(COOLDOWN_KEY, cooldownEnd.toString());
            log.info(`Cooldown activated until ${new Date(cooldownEnd).toLocaleTimeString()}`);
        } catch (e) {
            log.error('Error activating cooldown:', e);
        }
    }

    /**
     * Récupérer le nom complet de la langue.
     *
     * @param {string} langCode - Code de langue (fr, en, es, etc.)
     * @returns {string} Nom complet de la langue.
     */
    function getLanguageName(langCode) {
        const langTranslations = TRANSLATIONS[CURRENT_LANG] || TRANSLATIONS['fr'] || {};
        const langNames = langTranslations.langNames || {};
        return langNames[langCode] || langCode;
    }

    /**
     * Remplacer les placeholders dans un texte.
     *
     * @param {string} text - Texte avec placeholders {newLang}, {oldLang}, {botName}, {clearBtn}.
     * @param {Object} replacements - Objet avec les valeurs de remplacement.
     * @returns {string} Texte avec placeholders remplacés.
     */
    function replacePlaceholders(text, replacements) {
        return text
            .replace(/\{newLang\}/g, replacements.newLang || '')
            .replace(/\{oldLang\}/g, replacements.oldLang || '')
            .replace(/\{botName\}/g, replacements.botName || '')
            .replace(/\{clearBtn\}/g, replacements.clearBtn || '');
    }

    /**
     * Déterminer si le changement de langue s'est produit sur la page actuelle.
     *
     * @returns {boolean} True si changement récent (< 10s), false sinon.
     */
    function isRecentLanguageChange() {
        try {
            const timestamp = localStorage.getItem(LS_TIMESTAMP_KEY);
            if (!timestamp) {
                return false;
            }

            const now = Date.now();
            const elapsed = now - parseInt(timestamp, 10);

            if (elapsed < RECENT_CHANGE_THRESHOLD) {
                log.info('Recent language change detected (on current page)');
                return true;
            }

            return false;
        } catch (e) {
            log.error('Error checking recent language change:', e);
            return false;
        }
    }

    /**
     * Récupérer le nom du bouton "Clear" traduit depuis les data-params du chatbot.
     * 
     * @returns {string} Nom traduit du bouton Clear (ex: "Nouvelle conversation")
     */
    function getClearButtonName() {
        try {
            const chatbotElement = document.querySelector('.mwai-chatbot');
            if (!chatbotElement) {
                log.warn('Chatbot element not found for Clear button name');
                return 'Nouvelle conversation'; // Fallback français
            }

            const dataParams = chatbotElement.getAttribute('data-params');
            if (!dataParams) {
                log.warn('data-params not found, using default Clear button name');
                return 'Nouvelle conversation';
            }

            const params = JSON.parse(dataParams);
            const clearButtonName = params.textClear || 'Nouvelle conversation';
            log.info('Clear button name retrieved:', clearButtonName);
            return clearButtonName;
        } catch (e) {
            log.error('Error getting Clear button name:', e);
            return 'Nouvelle conversation'; // Fallback
        }
    }

    /**
     * Récupérer les textes traduits pour la popup (version simplifiée avec un seul bouton).
     *
     * @param {string} oldLang - Ancienne langue (code).
     * @returns {Object} Textes de la popup dans la langue actuelle.
     */
    function getPopupTexts(oldLang) {
        const botName = getChatbotName();
        const newLangName = getLanguageName(CURRENT_LANG);
        const oldLangName = getLanguageName(oldLang);
        const clearButtonName = getClearButtonName();

        const replacements = {
            newLang: newLangName,
            oldLang: oldLangName,
            botName: botName,
            clearBtn: clearButtonName,
        };

        // Textes simplifiés par langue
        const langTranslations = TRANSLATIONS[CURRENT_LANG] || {};

        return {
            title: langTranslations.title || 'Language change detected',
            message: replacePlaceholders(
                langTranslations.message || 'You changed the language to {newLang}. To start a new conversation in {newLang}, click on the <strong>"{clearBtn}"</strong> button.',
                replacements
            ),
            btnOk: langTranslations.btnOk || 'Got it',
        };
    }

    /**
     * Effacer le champ de saisie et trigger le bouton "Start over" AI Engine.
     *
     * Compatible avec le React d'AI Engine (pas de manipulation directe DOM).
     */
    function restartConversation() {
        log.info('Restarting conversation...');

        try {
            // 1. Trouver le bouton "Clear" / "Start over" d'AI Engine
            // Sélecteurs possibles selon version AI Engine
            const clearButtonSelectors = [
                '.mwai-chatbot .mwai-clear-button',
                '.mwai-chatbot .mwai-reset-button',
                '.mwai-chatbot button[aria-label*="clear"]',
                '.mwai-chatbot button[aria-label*="restart"]',
            ];

            let clearButton = null;
            for (const selector of clearButtonSelectors) {
                clearButton = document.querySelector(selector);
                if (clearButton) {
                    log.info('Clear button found with selector:', selector);
                    break;
                }
            }

            if (clearButton) {
                // Trigger le clic sur le bouton natif AI Engine
                clearButton.click();
                log.info('Clear button clicked');
            } else {
                log.warn('Clear button not found, fallback: clear input field only');

                // Fallback : vider uniquement le champ de saisie
                const inputField = document.querySelector('.mwai-chatbot input[type="text"], .mwai-chatbot textarea');
                if (inputField) {
                    inputField.value = '';
                    log.info('Input field cleared (fallback)');
                }
            }

            // 2. Mettre à jour la dernière langue connue
            localStorage.setItem(LS_KEY, CURRENT_LANG);
            log.info(`Last language updated to: ${CURRENT_LANG}`);

        } catch (e) {
            log.error('Error restarting conversation:', e);
        }
    }

    /**
     * Afficher la popup de changement de langue (version simplifiée).
     *
     * @param {string} oldLang - Ancienne langue (code).
     */
    function showLanguageChangePopup(oldLang) {
        // Vérifier si la popup existe déjà
        if (document.getElementById('eai-ml-popup-overlay')) {
            log.info('Popup already displayed, skipping');
            return;
        }

        const texts = getPopupTexts(oldLang);

        // Créer le HTML de la popup (version simplifiée avec un seul bouton)
        const popupHTML = `
			<div id="eai-ml-popup-overlay" style="
				position: fixed;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
				background: rgba(0, 0, 0, 0.5);
				z-index: 999999;
				display: flex;
				align-items: center;
				justify-content: center;
			">
				<div id="eai-ml-popup" style="
					background: white;
					border-radius: 12px;
					padding: 30px;
					max-width: 500px;
					width: 90%;
					box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
					font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
				">
					<h2 style="
						margin: 0 0 15px 0;
						font-size: 22px;
						font-weight: 600;
						color: #1a1a1a;
					">${texts.title}</h2>
					<p style="
						margin: 0 0 25px 0;
						font-size: 16px;
						line-height: 1.5;
						color: #4a4a4a;
					">${texts.message}</p>
					<div style="
						display: flex;
						justify-content: flex-end;
					">
						<button id="eai-ml-btn-ok" style="
							background: #007aff;
							border: none;
							border-radius: 6px;
							padding: 12px 24px;
							font-size: 15px;
							font-weight: 500;
							color: white;
							cursor: pointer;
							transition: background 0.2s;
						">${texts.btnOk}</button>
					</div>
				</div>
			</div>
		`;

        // Injecter dans le DOM
        document.body.insertAdjacentHTML('beforeend', popupHTML);

        // Ajouter les event listeners (version simplifiée)
        const btnOk = document.getElementById('eai-ml-btn-ok');
        const overlay = document.getElementById('eai-ml-popup-overlay');

        log.info('Event listeners setup', {
            btnOk: !!btnOk,
            overlay: !!overlay
        });

        if (!btnOk || !overlay) {
            log.error('Failed to find popup elements!');
            return;
        }

        // Bouton "OK" : fermer la popup et mettre à jour la langue
        btnOk.addEventListener('click', () => {
            log.info('User acknowledged language change');
            localStorage.setItem(LS_KEY, CURRENT_LANG);
            activateCooldown();
            overlay.remove();
        });

        // Fermeture au clic sur l'overlay
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                log.info('Popup closed by clicking overlay');
                localStorage.setItem(LS_KEY, CURRENT_LANG);
                activateCooldown();
                overlay.remove();
            }
        });

        // Hover effect
        btnOk.addEventListener('mouseenter', () => {
            btnOk.style.background = '#0056b3';
        });
        btnOk.addEventListener('mouseleave', () => {
            btnOk.style.background = '#007aff';
        });

        log.info('Popup displayed');
    }

    /**
     * Détecter le changement de langue et déclencher la popup si nécessaire.
     */
    function detectLanguageChange() {
        log.info('Detecting language change...', {
            currentLang: CURRENT_LANG,
            lastLang: localStorage.getItem(LS_KEY),
        });

        try {
            // Vérifier si un chatbot est présent sur la page
            if (!hasChatbotOnPage()) {
                log.info('No chatbot on page, skipping language change detection');
                return;
            }

            const lastLang = localStorage.getItem(LS_KEY);

            // Première visite ou pas de langue stockée
            if (!lastLang) {
                log.info('First visit, storing current language:', CURRENT_LANG);
                localStorage.setItem(LS_KEY, CURRENT_LANG);
                return;
            }

            // Pas de changement de langue
            if (lastLang === CURRENT_LANG) {
                log.info('No language change detected');
                return;
            }

            // Changement de langue détecté !
            log.info(`Language change detected: ${lastLang} → ${CURRENT_LANG}`);

            // Vérifier si cooldown actif
            if (isCooldownActive()) {
                log.info('Cooldown active, skipping popup');
                return;
            }

            // Vérifier si conversation active (asynchrone avec Observer)
            hasActiveConversation((hasConversation) => {
                if (!hasConversation) {
                    log.info('No active conversation, silent language change');
                    localStorage.setItem(LS_KEY, CURRENT_LANG);
                    return;
                }

                // Enregistrer le timestamp du changement
                localStorage.setItem(LS_TIMESTAMP_KEY, Date.now().toString());

                // Afficher la popup
                showLanguageChangePopup(lastLang);
            });

        } catch (e) {
            log.error('Error detecting language change:', e);
        }
    }

    /**
     * Initialisation au chargement du DOM.
     */
    function init() {
        log.info('Initializing...', {
            version: PLUGIN_VERSION,
            currentLang: CURRENT_LANG,
            isDebug: IS_DEBUG,
        });

        // Vérifier que eaiMLData est disponible
        if (!window.eaiMLData) {
            log.error('eaiMLData not found! Plugin may not work correctly.');
            return;
        }

        // Détecter changement de langue
        detectLanguageChange();

        log.info('Initialization complete');
    }

    // Démarrer quand le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

