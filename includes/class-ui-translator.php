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
	 * Constructeur privé (Singleton).
	 */
	private function __construct() {
		$this->current_lang = $this->get_current_language();
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
		// Intercepter le HTML retourné par le shortcode [mwai_chatbot]
		// pour modifier le JSON dans data-params (approche propre : parse JSON, modifie, réencode)
		add_filter( 'do_shortcode_tag', array( $this, 'intercept_chatbot_html' ), 10, 4 );

		// Log d'initialisation
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'[AI Engine Multilang v%s] UI Translator: Initialized (Lang: %s)',
				EAI_ML_VERSION,
				$this->current_lang
			) );
		}
	}

	/**
	 * Intercepter le HTML du shortcode [mwai_chatbot] pour traduire les textes UI.
	 * Approche propre : parse le JSON data-params, modifie, réencode, remplace.
	 *
	 * @param string $output Le HTML retourné par le shortcode.
	 * @param string $tag Le nom du shortcode.
	 * @param array $attr Les attributs du shortcode.
	 * @param array $m Les résultats du regex match.
	 * @return string Le HTML modifié avec les traductions.
	 */
	public function intercept_chatbot_html( $output, $tag, $attr, $m ) {
		// Ne traiter que le shortcode mwai_chatbot
		if ( $tag !== 'mwai_chatbot' ) {
			return $output;
		}

		// Parser le data-params avec une regex PROPRE
		if ( ! preg_match( "/data-params='([^']+)'/", $output, $matches ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[AI Engine Multilang] UI Translator: No data-params found in chatbot HTML' );
			}
			return $output;
		}

		// Décoder le JSON
		$json_encoded = $matches[1];
		$params = json_decode( html_entity_decode( $json_encoded ), true );

		if ( ! $params ) {
			error_log( '[AI Engine Multilang] UI Translator ERROR: Failed to decode data-params JSON' );
			return $output;
		}

		// Clés des textes UI à traduire (en camelCase car déjà converties par AI Engine)
		$ui_text_keys = [
			'textSend',
			'textClear',
			'textInputPlaceholder',
			'textCompliance',
			'startSentence',
			'iconText',
			'headerSubtitle',
			'popupTitle'
		];

		$translated_count = 0;
		foreach ( $ui_text_keys as $key ) {
			if ( isset( $params[ $key ] ) && ! empty( $params[ $key ] ) ) {
				$original = $params[ $key ];
				$translated = $this->parse_multilang_text( $original, $this->current_lang );
				
				if ( $translated !== $original ) {
					$params[ $key ] = $translated;
					$translated_count++;
				}
			}
		}

		// Log résumé uniquement si WP_DEBUG
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && $translated_count > 0 ) {
			error_log( sprintf(
				'[AI Engine Multilang v%s] UI Translator: Translated %d UI texts (lang: %s)',
				EAI_ML_VERSION,
				$translated_count,
				$this->current_lang
			) );
		}

		// Ré-encoder le JSON et remplacer dans le HTML
		$new_json_encoded = htmlspecialchars( json_encode( $params ), ENT_QUOTES, 'UTF-8' );
		$output = str_replace( "data-params='$json_encoded'", "data-params='$new_json_encoded'", $output );

		return $output;
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
	 * Parser un texte multilingue avec tags.
	 *
	 * Supporte 2 formats :
	 * - Format 1 (avec pipe) : "Texte [fr]|Text [en]|Texto [es]"
	 * - Format 2 (sans pipe) : "[fr]Texte[en]Text[es]Texto"
	 * 
	 * @since 1.0.0
	 * @param string $text Texte avec tags multilingues.
	 * @param string $lang Code langue cible (fr, en, es, en-us, etc.).
	 * @return string Texte extrait pour la langue, ou texte original si pas de tag trouvé.
	 */
	private function parse_multilang_text( $text, $lang ) {
		if ( empty( $text ) || ! is_string( $text ) ) {
			return $text;
		}

		// Normaliser le code langue : en-us → en
		$lang_short = substr( $lang, 0, 2 );

		// FORMAT 1 : Avec pipe "Texte [fr]|Text [en]|Texto [es]"
		// Pattern : cherche "quelque chose [code]" précédé ou suivi d'un pipe ou début/fin
		// \s* ignore les espaces/retours à la ligne après le pipe
		$pattern_pipe = '/(?:^|\|)\s*([^|\[]+?)\s*\[' . preg_quote( $lang_short, '/' ) . '\]/is';
		
		if ( preg_match( $pattern_pipe, $text, $matches ) ) {
			return trim( $matches[1] );
		}

		// FORMAT 2 : Sans pipe "[fr]Texte[en]Text[es]Texto"
		$lang_tag_pattern = '\[(?:[a-z]{2}(?:-[a-z]{2})?)\]';
		$pattern_no_pipe = '/\[' . preg_quote( $lang_short, '/' ) . '\](.*?)(?:' . $lang_tag_pattern . '|$)/s';
		
		if ( preg_match( $pattern_no_pipe, $text, $matches ) ) {
			return trim( $matches[1] );
		}

		// Si pas de tag trouvé, retourner le texte original
		return $text;
	}

	/**
	 * Récupérer les traductions par défaut pour les champs UI courants.
	 *
	 * @since 1.0.6
	 * @return array Traductions par défaut par langue et par champ.
	 */
	private function get_default_translations() {
		/**
		 * Filtrer les traductions par défaut des champs UI.
		 *
		 * @since 1.0.6
		 * @param array $defaults Tableau des traductions par défaut.
		 */
		return apply_filters( 'eai_ml_default_translations', array(
			'textSend' => array(
				'fr' => 'Dire',
				'en' => 'Say',
				'es' => 'Decir',
				'de' => 'Sagen',
				'it' => 'Dire',
				'pt' => 'Dizer',
			),
			'textClear' => array(
				'fr' => 'Nouvelle conversation',
				'en' => 'New conversation',
				'es' => 'Nueva conversación',
				'de' => 'Neues Gespräch',
				'it' => 'Nuova conversazione',
				'pt' => 'Recomeçar',
			),
			'textInputPlaceholder' => array(
				'fr' => 'Tape ton message...',
				'en' => 'Type your message...',
				'es' => 'Escribe tu mensaje...',
				'de' => 'Schreibe deine Nachricht...',
				'it' => 'Scrivi il tuo messaggio...',
				'pt' => 'Digite sua mensagem...',
			),
		) );
	}

	/**
	 * Hook 'mwai_chatbot_params' : Traduire les textes UI du chatbot.
	 *
	 * Parse les textes configurés dans AI Engine avec le format [fr]...[en]...[es]...
	 * et extrait la traduction correspondant à la langue Polylang active.
	 * 
	 * Si un champ ne contient pas de tags multilingues, applique les traductions par défaut
	 * pour les champs UI courants (textSend, textClear, textInputPlaceholder).
	 *
	 * @since 1.0.0
	 * @param array $params Paramètres du chatbot AI Engine.
	 * @return array Paramètres modifiés avec traductions extraites.
	 */
	public function translate_ui_texts( $params ) {
		// Récupérer langue actuelle (au cas où elle a changé entre init et render)
		$lang = $this->get_current_language();

		// Récupérer les traductions par défaut
		$default_translations = $this->get_default_translations();

		// Liste des champs de texte UI à traduire
		$translatable_fields = array(
			'textSend',
			'textClear',
			'textInputPlaceholder',
			'startSentence',
			'headerSubtitle',
			'textCompliance',
			'aiName',
			'userName',
		);

		$translated_count = 0;
		$default_applied = 0;

		// Parser chaque champ s'il contient des tags multilingues
		foreach ( $translatable_fields as $field ) {
			if ( isset( $params[ $field ] ) && is_string( $params[ $field ] ) ) {
				// Vérifier si le texte contient des tags de langue valides
				// Pattern: [xx] ou [xx-yy] où xx et yy sont exactement 2 lettres minuscules
				if ( preg_match( '/\[[a-z]{2}(?:-[a-z]{2})?\]/', $params[ $field ] ) ) {
					$original = $params[ $field ];
					$parsed = $this->parse_multilang_text( $original, $lang );
					
					// Ne remplacer que si on a extrait quelque chose de différent
					if ( $parsed !== $original ) {
						$params[ $field ] = $parsed;
						$translated_count++;
					}
				} elseif ( isset( $default_translations[ $field ][ $lang ] ) && 'fr' !== $lang ) {
					// Si pas de tags ET qu'une traduction par défaut existe ET que la langue n'est pas FR
					// appliquer la traduction par défaut
					$params[ $field ] = $default_translations[ $field ][ $lang ];
					$default_applied++;
				}
			}
		}

		// Log des traductions appliquées
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'[AI Engine Multilang v%s] UI Translator: Parsed %d multilang texts, applied %d defaults for lang "%s"',
				EAI_ML_VERSION,
				$translated_count,
				$default_applied,
				$lang
			) );
		}

		return $params;
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
}

