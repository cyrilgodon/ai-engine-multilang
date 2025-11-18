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
	const COOLDOWN_KEY = 'eai_ml_lang_alert_cooldown';
	const COOLDOWN_DURATION = 5 * 60 * 1000; // 5 minutes

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
	 * Vérifier si une conversation AI Engine est active.
	 *
	 * Détecte la présence de clés localStorage commençant par "mwai-" (AI Engine).
	 *
	 * @returns {boolean} True si conversation active, false sinon.
	 */
	function hasActiveConversation() {
		try {
			const keys = Object.keys(localStorage);
			const mwaiKeys = keys.filter((key) => key.startsWith('mwai-'));
			
			if (mwaiKeys.length > 0) {
				log.info('Active conversation detected:', mwaiKeys);
				return true;
			}
			
			log.info('No active conversation found');
			return false;
		} catch (e) {
			log.error('Error checking active conversation:', e);
			return false;
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
	 * Récupérer les traductions du popup pour la langue actuelle.
	 *
	 * @returns {object} Traductions du popup.
	 */
	function getPopupTexts() {
		const langTranslations = TRANSLATIONS[CURRENT_LANG] || TRANSLATIONS['fr'] || {};
		
		return {
			title: langTranslations.title || 'Language change detected',
			message: langTranslations.message || 'You changed the language. Please start a new conversation.',
			btnNewConv: langTranslations.btnNewConv || 'Start new conversation',
			btnFinishCurr: langTranslations.btnFinishCurr || 'Finish current one',
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
	 * Afficher la popup de changement de langue.
	 */
	function showLanguageChangePopup() {
		const texts = getPopupTexts();

		// Créer le HTML de la popup
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
						gap: 12px;
						justify-content: flex-end;
					">
						<button id="eai-ml-btn-finish" style="
							background: #f5f5f5;
							border: none;
							border-radius: 6px;
							padding: 12px 20px;
							font-size: 15px;
							font-weight: 500;
							color: #4a4a4a;
							cursor: pointer;
							transition: background 0.2s;
						">${texts.btnFinishCurr}</button>
						<button id="eai-ml-btn-restart" style="
							background: #007aff;
							border: none;
							border-radius: 6px;
							padding: 12px 20px;
							font-size: 15px;
							font-weight: 500;
							color: white;
							cursor: pointer;
							transition: background 0.2s;
						">${texts.btnNewConv}</button>
					</div>
				</div>
			</div>
		`;

		// Injecter dans le DOM
		document.body.insertAdjacentHTML('beforeend', popupHTML);

		// Ajouter les event listeners
		const btnFinish = document.getElementById('eai-ml-btn-finish');
		const btnRestart = document.getElementById('eai-ml-btn-restart');
		const overlay = document.getElementById('eai-ml-popup-overlay');

		// Bouton "Terminer la discussion actuelle"
		btnFinish.addEventListener('click', () => {
			log.info('User chose to finish current conversation');
			activateCooldown();
			overlay.remove();
		});

		// Bouton "Démarrer nouvelle discussion"
		btnRestart.addEventListener('click', () => {
			log.info('User chose to restart conversation');
			restartConversation();
			overlay.remove();
		});

		// Fermeture au clic sur l'overlay
		overlay.addEventListener('click', (e) => {
			if (e.target === overlay) {
				log.info('Popup closed by clicking overlay');
				activateCooldown();
				overlay.remove();
			}
		});

		// Hover effects
		btnFinish.addEventListener('mouseenter', () => {
			btnFinish.style.background = '#e8e8e8';
		});
		btnFinish.addEventListener('mouseleave', () => {
			btnFinish.style.background = '#f5f5f5';
		});
		btnRestart.addEventListener('mouseenter', () => {
			btnRestart.style.background = '#0056b3';
		});
		btnRestart.addEventListener('mouseleave', () => {
			btnRestart.style.background = '#007aff';
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

			// Vérifier si conversation active
			if (!hasActiveConversation()) {
				log.info('No active conversation, silent language change');
				localStorage.setItem(LS_KEY, CURRENT_LANG);
				return;
			}

			// Afficher la popup
			showLanguageChangePopup();

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

