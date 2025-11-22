<?php
/**
 * Quick Actions Translator - Traduction des labels des Quick Actions.
 *
 * Cette classe gère la traduction automatique des labels des Quick Actions
 * AI Engine selon le format : "Texte [fr]|Text [en]|Texto [es]".
 *
 * @package AI_Engine_Multilang
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe EAI_ML_QA_Translator
 *
 * Responsable de la traduction des Quick Actions via le hook 'mwai_chatbot_shortcuts'.
 * Parse les labels multilingues et extrait la traduction appropriée selon la langue active.
 *
 * @since 1.0.0
 */
class EAI_ML_QA_Translator {

	/**
	 * Instance unique de la classe (Singleton).
	 *
	 * @var EAI_ML_QA_Translator|null
	 */
	private static $instance = null;

	/**
	 * Constructeur privé (Singleton).
	 */
	private function __construct() {}

	/**
	 * Récupérer l'instance unique (Singleton).
	 *
	 * @return EAI_ML_QA_Translator
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
		// Priorité 999 pour être sûr d'être exécuté EN DERNIER
		add_filter( 'mwai_chatbot_shortcuts', array( $this, 'translate_quick_actions' ), 999, 2 );

		// Log d'initialisation
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'[AI Engine Multilang v%s] QA Translator: Initialized',
				EAI_ML_VERSION
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
	 * Hook 'mwai_chatbot_shortcuts' : Traduire les labels des Quick Actions.
	 *
	 * Format attendu des labels :
	 * "Texte français [fr]|English text [en]|Texto español [es]"
	 *
	 * @since 1.0.0
	 * @param array $shortcuts Liste des Quick Actions.
	 * @param array $args Arguments supplémentaires (chatbot ID, etc.).
	 * @return array Quick Actions modifiées avec traductions.
	 */
	public function translate_quick_actions( $shortcuts, $args ) {
		$lang = $this->get_current_language();

		// Si pas de shortcuts, retourner tel quel
		if ( empty( $shortcuts ) ) {
			return $shortcuts;
		}

		// Pattern pour extraire les traductions : "texte [code]"
		// Regex : cherche du texte suivi de [xx] où xx est le code langue
		$pattern = '/([^|]+)\[(' . preg_quote( $lang, '/' ) . ')\]/i';

		$translated_count = 0;
		$unchanged_count = 0;

		foreach ( $shortcuts as &$shortcut ) {
			// Les Quick Actions ont leur label dans $shortcut['data']['label']
			if ( ! isset( $shortcut['data'] ) ) {
				continue;
			}

			// Traduire le label
			if ( isset( $shortcut['data']['label'] ) ) {
				$original_label = $shortcut['data']['label'];

				// Vérifier si format multilingue (contient au moins un tag [xx])
				if ( preg_match( '/\[(?:fr|en|es|de|it|pt|nl|pl|ru|ja|zh)\]/i', $original_label ) ) {
					// Chercher la traduction pour la langue active
					if ( preg_match( $pattern, $original_label, $matches ) ) {
						$shortcut['data']['label'] = trim( $matches[1] );
						$translated_count++;
					} else {
						// Fallback français
						if ( preg_match( '/([^|]+)\[fr\]/i', $original_label, $fallback_matches ) ) {
							$shortcut['data']['label'] = trim( $fallback_matches[1] );
							$translated_count++;
						}
					}
				} else {
					$unchanged_count++;
				}
			}

			// Traduire aussi le message
			if ( isset( $shortcut['data']['message'] ) ) {
				$original_message = $shortcut['data']['message'];

				// Vérifier si format multilingue
				if ( preg_match( '/\[(?:fr|en|es|de|it|pt|nl|pl|ru|ja|zh)\]/i', $original_message ) ) {
					// Chercher la traduction pour la langue active
					if ( preg_match( $pattern, $original_message, $matches ) ) {
						$shortcut['data']['message'] = trim( $matches[1] );
					} else {
						// Fallback français
						if ( preg_match( '/([^|]+)\[fr\]/i', $original_message, $fallback_matches ) ) {
							$shortcut['data']['message'] = trim( $fallback_matches[1] );
						}
					}
				}
			}
		}

		// Log résumé uniquement si traductions effectuées
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && $translated_count > 0 ) {
			error_log( sprintf(
				'[AI Engine Multilang v%s] QA Translator: Translated %d Quick Actions (lang: %s)',
				EAI_ML_VERSION,
				$translated_count,
				$lang
			) );
		}

		return $shortcuts;
	}

	/**
	 * Tronquer une chaîne pour les logs (éviter les logs trop longs).
	 *
	 * @param string $text Texte à tronquer.
	 * @param int    $length Longueur maximale.
	 * @return string Texte tronqué.
	 */
	private function truncate( $text, $length = 50 ) {
		if ( strlen( $text ) <= $length ) {
			return $text;
		}
		return substr( $text, 0, $length ) . '...';
	}
}

