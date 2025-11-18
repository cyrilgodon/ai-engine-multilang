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
		add_filter( 'mwai_chatbot_shortcuts', array( $this, 'translate_quick_actions' ), 20, 2 );

		// Log d'initialisation
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'[AI Engine Multilang v%s] QA Translator: Hook registered (priority 20)',
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

		// Pattern pour extraire les traductions : "texte [code]"
		// Regex : cherche du texte suivi de [xx] où xx est le code langue
		$pattern = '/([^|]+)\[(' . preg_quote( $lang, '/' ) . ')\]/i';

		$translated_count = 0;

		foreach ( $shortcuts as &$shortcut ) {
			if ( ! isset( $shortcut['label'] ) ) {
				continue;
			}

			$original_label = $shortcut['label'];

			// Vérifier si format multilingue (contient au moins un tag [xx])
			if ( ! preg_match( '/\[(?:fr|en|es|de|it|pt|nl|pl|ru|ja|zh)\]/i', $original_label ) ) {
				// Pas de tag multilingue, on garde tel quel
				continue;
			}

			// Chercher la traduction pour la langue active
			if ( preg_match( $pattern, $original_label, $matches ) ) {
				// $matches[1] = le texte avant [xx]
				$shortcut['label'] = trim( $matches[1] );
				$translated_count++;

				// Log pour debug
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( sprintf(
						'[AI Engine Multilang v%s] QA Translator: "%s" → "%s" (lang: %s)',
						EAI_ML_VERSION,
						$this->truncate( $original_label, 50 ),
						$shortcut['label'],
						$lang
					) );
				}
			} else {
				// Langue active non trouvée, fallback français
				if ( preg_match( '/([^|]+)\[fr\]/i', $original_label, $fallback_matches ) ) {
					$shortcut['label'] = trim( $fallback_matches[1] );
					$translated_count++;

					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( sprintf(
							'[AI Engine Multilang v%s] QA Translator: Fallback FR for "%s" (requested lang: %s not found)',
							EAI_ML_VERSION,
							$this->truncate( $original_label, 50 ),
							$lang
						) );
					}
				}
			}
		}

		// Log résumé
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && $translated_count > 0 ) {
			error_log( sprintf(
				'[AI Engine Multilang v%s] QA Translator: Translated %d Quick Action(s) for lang "%s"',
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

