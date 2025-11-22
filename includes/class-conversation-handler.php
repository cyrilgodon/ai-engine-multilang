<?php
/**
 * Conversation Handler - Gestion détection changement langue + popup.
 *
 * Cette classe gère la détection des changements de langue Polylang et l'affichage
 * d'une popup intelligente si une conversation AI Engine est en cours.
 *
 * @package AI_Engine_Multilang
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe EAI_ML_Conversation_Handler
 *
 * Responsable de :
 * - Injecter la langue Polylang actuelle via wp_localize_script()
 * - Enqueue du JavaScript qui détecte les changements de langue (localStorage)
 * - Afficher une popup si changement détecté avec conversation active
 *
 * @since 1.0.0
 */
class EAI_ML_Conversation_Handler {

	/**
	 * Instance unique de la classe (Singleton).
	 *
	 * @var EAI_ML_Conversation_Handler|null
	 */
	private static $instance = null;

	/**
	 * Constructeur privé (Singleton).
	 */
	private function __construct() {}

	/**
	 * Récupérer l'instance unique (Singleton).
	 *
	 * @return EAI_ML_Conversation_Handler
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialiser les hooks WordPress.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 100 );

		// Log d'initialisation
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'[AI Engine Multilang v%s] Conversation Handler: Initialized',
				EAI_ML_VERSION
			) );
		}
	}

	/**
	 * Enqueue du JavaScript de détection changement langue.
	 *
	 * Priorité 100 pour charger APRÈS AI Engine.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$lang = $this->get_current_language();

		// Déterminer le fichier JS selon WP_DEBUG
		$js_file = ( defined( 'WP_DEBUG' ) && WP_DEBUG )
			? 'conversation-handler.dev.min.js'
			: 'conversation-handler.min.js';

		$js_url = EAI_ML_PLUGIN_URL . 'assets/js/' . $js_file;
		$js_path = EAI_ML_PLUGIN_DIR . 'assets/js/' . $js_file;

		// Vérifier si le fichier existe
		if ( ! file_exists( $js_path ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf(
					'[AI Engine Multilang v%s] WARNING: JavaScript file not found: %s',
					EAI_ML_VERSION,
					$js_file
				) );
			}
			return;
		}

		// Enqueue du script
		wp_enqueue_script(
			'eai-ml-conversation-handler',
			$js_url,
			array(), // Pas de dépendances (vanilla JS)
			EAI_ML_VERSION,
			true // Dans le footer
		);

		// Injecter les variables PHP vers JavaScript
		wp_localize_script(
			'eai-ml-conversation-handler',
			'eaiMLData',
			array(
				'currentLang'    => $lang,
				'pluginVersion'  => EAI_ML_VERSION,
				'isDebug'        => defined( 'WP_DEBUG' ) && WP_DEBUG,
				'translations'   => $this->get_popup_translations(),
				'localStorageKey' => 'eai_ml_last_language',
			)
		);

		// Log d'enqueue
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'[AI Engine Multilang v%s] Conversation Handler: Script enqueued (%s) | Lang: %s',
				EAI_ML_VERSION,
				$js_file,
				$lang
			) );
		}
	}

	/**
	 * Récupérer la langue Polylang actuelle.
	 *
	 * @return string Code langue (fr, en, es, etc.)
	 */
	private function get_current_language() {
		if ( function_exists( 'pll_current_language' ) ) {
			return pll_current_language() ?: 'fr';
		}
		return 'fr';
	}

	/**
	 * Récupérer les traductions du popup pour toutes les langues.
	 *
	 * @since 1.0.0
	 * @since 1.4.0 Simplifié avec un seul bouton informatif.
	 * @return array Traductions du popup par langue.
	 */
	private function get_popup_translations() {
		/**
		 * Filtrer les traductions du popup.
		 *
		 * @since 1.0.0
		 * @param array $translations Tableau des traductions par langue.
		 */
		return apply_filters( 'eai_ml_popup_translations', array(
			'fr' => array(
				'title'              => 'Changement de langue',
				'message'            => 'Vous avez changé la langue en {newLang}. Vous avez une discussion en cours en {oldLang} avec {botName}. {botName} ne peut pas changer de langue en cours de discussion.<br><br>Pour démarrer une nouvelle conversation en {newLang}, cliquez sur le bouton <strong>"{clearBtn}"</strong>.',
				'btnOk'              => 'Compris',
				// Noms des langues
				'langNames'          => array(
					'fr' => 'français',
					'en' => 'anglais',
					'es' => 'espagnol',
					'de' => 'allemand',
					'it' => 'italien',
					'pt' => 'portugais',
				),
			),
			'en' => array(
				'title'              => 'Language Change',
				'message'            => 'You changed the language to {newLang}. You have an ongoing conversation in {oldLang} with {botName}. {botName} cannot change language during a conversation.<br><br>To start a new conversation in {newLang}, click on the <strong>"{clearBtn}"</strong> button.',
				'btnOk'              => 'Got it',
				'langNames'          => array(
					'fr' => 'French',
					'en' => 'English',
					'es' => 'Spanish',
					'de' => 'German',
					'it' => 'Italian',
					'pt' => 'Portuguese',
				),
			),
			'es' => array(
				'title'              => 'Cambio de idioma',
				'message'            => 'Has cambiado el idioma a {newLang}. Tienes una conversación en curso en {oldLang} con {botName}. {botName} no puede cambiar de idioma durante una conversación.<br><br>Para iniciar una nueva conversación en {newLang}, haz clic en el botón <strong>"{clearBtn}"</strong>.',
				'btnOk'              => 'Entendido',
				'langNames'          => array(
					'fr' => 'francés',
					'en' => 'inglés',
					'es' => 'español',
					'de' => 'alemán',
					'it' => 'italiano',
					'pt' => 'portugués',
				),
			),
		) );
	}
}

