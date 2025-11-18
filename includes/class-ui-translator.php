<?php
/**
 * UI Translator - Traduction des textes de l'interface AI Engine.
 *
 * Cette classe gère la traduction automatique des textes de l'interface du chatbot
 * AI Engine (boutons, placeholders, messages) selon la langue Polylang active.
 *
 * @package AI_Engine_Multilang
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe EAI_ML_UI_Translator
 *
 * Responsable de la traduction des paramètres UI du chatbot AI Engine via le hook
 * 'mwai_chatbot_params'. Injecte les traductions appropriées selon la langue Polylang.
 *
 * @since 1.0.0
 */
class EAI_ML_UI_Translator {

	/**
	 * Instance unique de la classe (Singleton).
	 *
	 * @var EAI_ML_UI_Translator|null
	 */
	private static $instance = null;

	/**
	 * Langue Polylang actuellement active.
	 *
	 * @var string
	 */
	private $current_lang;

	/**
	 * Tableau des traductions UI par langue.
	 *
	 * @var array
	 */
	private $translations = array();

	/**
	 * Constructeur privé (Singleton).
	 */
	private function __construct() {
		$this->current_lang = $this->get_current_language();
		$this->load_translations();
	}

	/**
	 * Récupérer l'instance unique (Singleton).
	 *
	 * @return EAI_ML_UI_Translator
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
		add_filter( 'mwai_chatbot_params', array( $this, 'translate_ui_texts' ), 10, 1 );

		// Log d'initialisation
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'[AI Engine Multilang v%s] UI Translator: Hook registered (priority 10) | Lang: %s',
				EAI_ML_VERSION,
				$this->current_lang
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
		return 'fr'; // Fallback français
	}

	/**
	 * Charger les traductions UI pour toutes les langues supportées.
	 *
	 * @since 1.0.0
	 */
	private function load_translations() {
		/**
		 * Filtrer les traductions UI supportées.
		 *
		 * @since 1.0.0
		 * @param array $translations Tableau des traductions par langue.
		 */
		$this->translations = apply_filters( 'eai_ml_translations_ui', array(
			'fr' => array(
				// FR = pas de surcharge, on garde les valeurs par défaut d'AI Engine
			),
			'en' => array(
				'textSend'             => 'Send',
				'textClear'            => 'Start over',
				'textInputPlaceholder' => 'Type your message...',
				'startSentence'        => 'Hello! I am Reflexivo, your personal coach. How can I help you today?',
				'headerSubtitle'       => 'Your personal coach',
			),
			'es' => array(
				'textSend'             => 'Enviar',
				'textClear'            => 'Empezar de nuevo',
				'textInputPlaceholder' => 'Escribe tu mensaje...',
				'startSentence'        => '¡Hola! Soy Reflexivo, tu coach personal. ¿Cómo puedo ayudarte hoy?',
				'headerSubtitle'       => 'Tu coach personal',
			),
		) );
	}

	/**
	 * Hook 'mwai_chatbot_params' : Traduire les textes UI du chatbot.
	 *
	 * @since 1.0.0
	 * @param array $params Paramètres du chatbot AI Engine.
	 * @return array Paramètres modifiés avec traductions.
	 */
	public function translate_ui_texts( $params ) {
		// Récupérer langue actuelle (au cas où elle a changé entre init et render)
		$lang = $this->get_current_language();

		// Si français ou langue non supportée, on garde les valeurs par défaut d'AI Engine
		if ( 'fr' === $lang || ! isset( $this->translations[ $lang ] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf(
					'[AI Engine Multilang v%s] UI Translator: No translation needed for lang "%s"',
					EAI_ML_VERSION,
					$lang
				) );
			}
			return $params;
		}

		// Merger les traductions avec les paramètres existants
		$translated_params = array_merge( $params, $this->translations[ $lang ] );

		// Log des traductions appliquées
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$translated_keys = array_keys( $this->translations[ $lang ] );
			error_log( sprintf(
				'[AI Engine Multilang v%s] UI Translator: Applied %d translations for lang "%s" | Keys: %s',
				EAI_ML_VERSION,
				count( $translated_keys ),
				$lang,
				implode( ', ', $translated_keys )
			) );
		}

		return $translated_params;
	}

	/**
	 * Récupérer la langue actuelle (publique, pour usage externe).
	 *
	 * @since 1.0.0
	 * @return string Code langue actuel.
	 */
	public function get_lang() {
		return $this->current_lang;
	}

	/**
	 * Récupérer toutes les traductions (publique, pour usage externe).
	 *
	 * @since 1.0.0
	 * @return array Tableau des traductions.
	 */
	public function get_translations() {
		return $this->translations;
	}
}

