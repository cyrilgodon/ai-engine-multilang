<?php
/**
 * Multilingual Prompt Filter.
 *
 * Filtre les prompts multilingues avant envoi au LLM pour ne conserver que
 * la langue active de l'utilisateur, économisant ~40% de tokens par session.
 *
 * Structure des prompts source :
 * - Section CORE (universelle, toujours conservée)
 * - Sections [LANG:XX]...[/LANG:XX] (filtrées selon la langue)
 *
 * Fonctionnalités :
 * - Détection automatique de la langue (Polylang, WPML, fallback navigateur)
 * - Parsing robuste avec gestion des blocs multiples dispersés
 * - Cache intelligent avec transients WordPress (1h)
 * - Logging complet avec métriques d'économie de tokens
 * - Mode dégradé en cas d'erreur (retour prompt complet)
 *
 * @package    AI_Engine_Multilang
 * @subpackage AI_Engine_Multilang/includes
 * @since      1.0.0
 * @version    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class EAI_ML_Prompt_Filter
 *
 * Filtre les prompts multilingues pour AI Engine.
 * Implémente optionnellement EAI_Pipeline_Nameable si présent (plugin AI Engine Elevatio).
 *
 * @since 1.0.0
 */
class EAI_ML_Prompt_Filter implements EAI_Pipeline_Nameable {

	/**
	 * Instance unique (Singleton).
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    EAI_ML_Prompt_Filter
	 */
	private static $instance = null;

	/**
	 * Langues supportées.
	 *
	 * @since  2.5.0
	 * @access private
	 * @var    array
	 */
	private $supported_languages = array( 'fr', 'en', 'es' );

	/**
	 * Langue par défaut (fallback).
	 *
	 * @since  2.5.0
	 * @access private
	 * @var    string
	 */
	private $default_language = 'fr';

	/**
	 * Durée de vie du cache en secondes (1 heure).
	 *
	 * @since  2.5.0
	 * @access private
	 * @var    int
	 */
	private $cache_duration = HOUR_IN_SECONDS;

	/**
	 * Préfixe des clés de cache.
	 *
	 * @since  2.5.0
	 * @access private
	 * @var    string
	 */
	private $cache_prefix = 'eai_ml_prompt_';

	/**
	 * Compteur de métriques pour logging.
	 *
	 * @since  2.5.0
	 * @access private
	 * @var    array
	 */
	private $metrics = array();

	/**
	 * Obtenir l'instance unique (Singleton).
	 *
	 * @since  2.5.0
	 * @return EAI_ML_Prompt_Filter
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructeur privé (Singleton).
	 *
	 * @since 2.5.0
	 */
	private function __construct() {
		// Permettre la customisation de la durée du cache via filtre
		$this->cache_duration = apply_filters( 'eai_ml_prompt_cache_duration', $this->cache_duration );
		
		// Permettre la customisation des langues supportées via filtre
		$this->supported_languages = apply_filters( 'eai_ml_prompt_supported_languages', $this->supported_languages );
		
		// Log d'initialisation avec version
		$this->log_info( 'Multilingual Prompt Filter v1.0.0 initialized' );
	}

	/**
	 * Initialiser les hooks WordPress.
	 *
	 * @since 2.5.0
	 */
	public function init() {
		// Hook sur mwai_ai_instructions avec priorité 5 (AVANT System Documents qui est à 10)
		add_filter( 'mwai_ai_instructions', array( $this, 'filter_prompt' ), 5, 2 );
		
		// LOG FORCÉ pour diagnostic
		error_log( '🔥 [AI Engine Multilang] Prompt Filter: Hooked on mwai_ai_instructions (priority 5) 🔥' );
		$this->log_info( 'Hooked on mwai_ai_instructions (priority 5)' );
	}

	/**
	 * Filtrer le prompt multilingue (hook mwai_ai_instructions).
	 *
	 * Cette méthode est appelée par AI Engine avant l'envoi au LLM.
	 * Elle filtre les sections linguistiques pour ne conserver que la langue active.
	 *
	 * @since 2.5.0
	 * @param string $instructions Instructions actuelles (prompt).
	 * @param object $query        Query object AI Engine (contains botId, session, etc.).
	 * @return string              Instructions filtrées ou originales si erreur.
	 */
	public function filter_prompt( $instructions, $query ) {
		// LOG FORCÉ pour diagnostic
		error_log( '🔥 [AI Engine Multilang] Prompt Filter: filter_prompt() CALLED 🔥' );
		error_log( '[AI Engine Multilang] Instructions length: ' . strlen( $instructions ) );
		
		// 🔒 SÉCURITÉ : Validation des paramètres
		if ( empty( $instructions ) || ! is_string( $instructions ) ) {
			$this->log_warning( 'Invalid instructions parameter (empty or not string)' );
			return $instructions;
		}

		if ( ! is_object( $query ) ) {
			$this->log_warning( 'Invalid query parameter (not an object)' );
			return $instructions;
		}

		// Vérifier si le prompt a une structure multilingue
		if ( strpos( $instructions, '[LANG:' ) === false ) {
			$this->log_debug( 'No multilingual structure detected, returning original prompt' );
			return $instructions;
		}

		// Détecter la langue active (en passant $query pour vérifier si une langue est forcée)
		$detected_language = $this->detect_language( $query );
		
		// Valider et normaliser la langue
		$target_language = $this->validate_language( $detected_language );
		
		$this->log_info( "Filtering prompt for language: {$target_language}" );

		// Vérifier le cache
		$cached_prompt = $this->get_cached_prompt( $instructions, $target_language );
		if ( false !== $cached_prompt ) {
			$this->log_info( "Using cached filtered prompt for language: {$target_language}" );
			return $cached_prompt;
		}

		// Filtrer le prompt
		$filtered_prompt = $this->parse_and_filter( $instructions, $target_language );

		// 🛡️ MODE DÉGRADÉ : En cas d'erreur, retourner le prompt original
		if ( false === $filtered_prompt || empty( $filtered_prompt ) ) {
			$this->log_error( 'Filtering failed, returning original prompt (degraded mode)' );
			return $instructions;
		}

		// Remplacer les variables dans le prompt filtré
		$filtered_prompt = $this->replace_variables( $filtered_prompt, $target_language );

		// Calculer et logger les métriques
		$this->calculate_and_log_metrics( $instructions, $filtered_prompt, $target_language );

		// Mettre en cache le résultat
		$this->cache_filtered_prompt( $instructions, $target_language, $filtered_prompt );

		return $filtered_prompt;
	}

	/**
	 * Détecter la langue active de l'utilisateur.
	 *
	 * Ordre de priorité :
	 * 1. $query->language (langue forcée, ex: dans les tests ou conversations spécifiques)
	 * 2. Polylang (pll_current_language)
	 * 3. WPML (apply_filters 'wpml_current_language')
	 * 4. Locale WordPress (get_locale)
	 * 5. Langue par défaut (FR)
	 *
	 * @since  2.5.0
	 * @since  2.6.6 Ajout du paramètre $query pour supporter la langue forcée
	 * @param  object $query Query object AI Engine (peut contenir une langue forcée)
	 * @return string Code langue détecté ('fr', 'en', 'es', etc.)
	 */
	private function detect_language( $query = null ) {
		// 1. PRIORITÉ 1 : Langue forcée dans $query (utilisé dans les tests ou pour forcer une langue spécifique)
		// Permet de tester le preprocessing avec différentes langues sans changer la langue Polylang de la page
		if ( is_object( $query ) && isset( $query->language ) && ! empty( $query->language ) ) {
			$lang = $query->language;
			$this->log_debug( "Language FORCED via \$query->language: {$lang}" );
			return strtolower( trim( $lang ) );
		}
		
		// 2. Requêtes REST/AJAX : Vérifier les paramètres GET/POST (envoyés par le frontend)
		if ( ! empty( $_REQUEST['lang'] ) ) {
			$lang = sanitize_text_field( $_REQUEST['lang'] );
			$this->log_debug( "Language detected via REST parameter: {$lang}" );
			return strtolower( trim( $lang ) );
		}
		
		// 3. Requêtes REST/AJAX : Extraire la langue depuis l'URL du referer (page qui a fait la requête)
		if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
			$referer = $_SERVER['HTTP_REFERER'];
			
			// Polylang ajoute /en/, /fr/, /es/ dans l'URL
			if ( preg_match( '#/([a-z]{2})/#', $referer, $matches ) ) {
				$lang = $matches[1];
				$this->log_debug( "Language detected via HTTP_REFERER URL: {$lang} (from {$referer})" );
				return strtolower( trim( $lang ) );
			}
			
			// Polylang peut aussi utiliser ?lang=en
			if ( preg_match( '#[?&]lang=([a-z]{2})#', $referer, $matches ) ) {
				$lang = $matches[1];
				$this->log_debug( "Language detected via HTTP_REFERER query param: {$lang}" );
				return strtolower( trim( $lang ) );
			}
		}
		
		// 4. Cookies Polylang (utilisés en mode "Detect browser language")
		if ( ! empty( $_COOKIE['pll_language'] ) ) {
			$lang = sanitize_text_field( $_COOKIE['pll_language'] );
			$this->log_debug( "Language detected via Polylang cookie: {$lang}" );
			return strtolower( trim( $lang ) );
		}
		
		// 5. Polylang (langue de la page actuelle - fonctionne en mode non-REST)
		if ( function_exists( 'pll_current_language' ) ) {
			$lang = pll_current_language( 'slug' );
			if ( ! empty( $lang ) ) {
				$this->log_debug( "Language detected via Polylang: {$lang}" );
				return strtolower( trim( $lang ) );
			}
		}

		// 3. WPML
		if ( has_filter( 'wpml_current_language' ) ) {
			$lang = apply_filters( 'wpml_current_language', null );
			if ( ! empty( $lang ) ) {
				$this->log_debug( "Language detected via WPML: {$lang}" );
				return strtolower( trim( $lang ) );
			}
		}

		// 4. Locale WordPress (ex: fr_FR → fr)
		$locale = get_locale();
		if ( ! empty( $locale ) ) {
			$lang_code = substr( $locale, 0, 2 );
			$this->log_debug( "Language detected via WordPress locale: {$lang_code} (from {$locale})" );
			return strtolower( trim( $lang_code ) );
		}

		// 4. Fallback sur langue par défaut
		$this->log_warning( "No language detection method available, using default: {$this->default_language}" );
		return $this->default_language;
	}

	/**
	 * Valider et normaliser la langue.
	 *
	 * @since  2.5.0
	 * @param  string $language Langue à valider.
	 * @return string           Langue validée et normalisée.
	 */
	private function validate_language( $language ) {
		// Normaliser : trim + lowercase
		$language = strtolower( trim( $language ) );

		// 🔒 SÉCURITÉ : Validation stricte contre la whitelist
		if ( ! in_array( $language, $this->supported_languages, true ) ) {
			$this->log_warning( "Language '{$language}' not supported, fallback to '{$this->default_language}'" );
			return $this->default_language;
		}

		return $language;
	}

	/**
	 * Parser et filtrer le prompt multilingue.
	 *
	 * Algorithme amélioré (v2.1) :
	 * 0. Supprimer les commentaires HTML <!-- ... --> (documentation)
	 * 1. Extraire la section CORE (tout avant le premier [LANG:)
	 * 2. Pour la partie après CORE :
	 *    - Supprimer les blocs [LANG:XX] des langues NON actives
	 *    - Conserver les blocs [LANG:XX] de la langue active (sans marqueurs)
	 *    - Conserver TOUT le contenu ENTRE les blocs (sections universelles)
	 * 3. Combiner CORE + partie filtrée
	 * 4. Nettoyer les espaces multiples
	 *
	 * Cette approche préserve :
	 * - Les blocs de la langue active
	 * - Les sections universelles entre les blocs
	 * - La structure du prompt original
	 *
	 * @since  2.5.0
	 * @since  2.5.1 Algorithme amélioré pour gérer le contenu universel entre blocs
	 * @since  2.5.2 Ajout suppression commentaires HTML + {{LANGUAGE_NAME}}
	 * @param  string $prompt   Prompt source complet.
	 * @param  string $language Langue cible (validée).
	 * @return string|false     Prompt filtré ou false si erreur.
	 */
	private function parse_and_filter( $prompt, $language ) {
		// 0. SUPPRIMER LES COMMENTAIRES HTML (documentation pour développeurs)
		// Pattern : <!-- ... --> (multiline avec flag 's')
		$original_size = strlen( $prompt );
		$prompt = preg_replace( '/<!--.*?-->/s', '', $prompt );
		$cleaned_size = strlen( $prompt );
		
		if ( $cleaned_size < $original_size ) {
			$removed_bytes = $original_size - $cleaned_size;
			$this->log_debug( "Removed HTML comments: {$removed_bytes} bytes" );
		}
		
		// 1. EXTRAIRE LA SECTION CORE
		// Pattern : tout depuis le début jusqu'au premier [LANG:
		$core_pattern = '/^(.*?)(?=\[LANG:)/s';
		$core_matches = array();
		
		if ( ! preg_match( $core_pattern, $prompt, $core_matches ) ) {
			$this->log_error( 'Failed to extract CORE section (no [LANG: marker found)' );
			return false;
		}

		$core_section = isset( $core_matches[1] ) ? $core_matches[1] : '';

		if ( empty( $core_section ) ) {
			$this->log_warning( 'CORE section is empty' );
		}

		// 2. EXTRAIRE LA PARTIE APRÈS LE CORE (contient les blocs multilingues + contenu universel)
		$after_core = substr( $prompt, strlen( $core_section ) );

		// 3. FILTRER LA PARTIE APRÈS CORE
		$lang_upper = strtoupper( $language );
		
		// Construire le pattern pour TOUTES les langues supportées
		$all_langs = $this->supported_languages;
		$filtered_content = $after_core;
		
		// Supprimer les blocs des langues NON actives
		foreach ( $all_langs as $lang ) {
			$lang_code = strtoupper( $lang );
			
			// Si c'est la langue active, on retire juste les marqueurs [LANG:XX] et [/LANG:XX]
			// Sinon, on supprime tout le bloc
			if ( $lang === $language ) {
				// Retirer les marqueurs mais garder le contenu
				$lang_escaped = preg_quote( $lang_code, '/' );
				$filtered_content = preg_replace( '/\[LANG:' . $lang_escaped . '\]/', '', $filtered_content );
				$filtered_content = preg_replace( '/\[\/LANG:' . $lang_escaped . '\]/', '', $filtered_content );
			} else {
				// Supprimer tout le bloc (marqueurs + contenu)
				$lang_escaped = preg_quote( $lang_code, '/' );
				$pattern = '/\[LANG:' . $lang_escaped . '\].*?\[\/LANG:' . $lang_escaped . '\]/s';
				$filtered_content = preg_replace( $pattern, '', $filtered_content );
			}
		}

		// Si après filtrage on n'a rien, essayer le fallback FR
		$filtered_content_clean = trim( $filtered_content );
		if ( empty( $filtered_content_clean ) && $language !== $this->default_language ) {
			$this->log_warning( "No content found for language [{$lang_upper}], trying fallback to [FR]" );
			
			// Réappliquer le filtrage avec FR
			$filtered_content = $after_core;
			foreach ( $all_langs as $lang ) {
				$lang_code = strtoupper( $lang );
				if ( $lang === 'fr' ) {
					$lang_escaped = preg_quote( $lang_code, '/' );
					$filtered_content = preg_replace( '/\[LANG:' . $lang_escaped . '\]/', '', $filtered_content );
					$filtered_content = preg_replace( '/\[\/LANG:' . $lang_escaped . '\]/', '', $filtered_content );
				} else {
					$lang_escaped = preg_quote( $lang_code, '/' );
					$pattern = '/\[LANG:' . $lang_escaped . '\].*?\[\/LANG:' . $lang_escaped . '\]/s';
					$filtered_content = preg_replace( $pattern, '', $filtered_content );
				}
			}
		}

		// Vérifier qu'on a du contenu
		if ( empty( trim( $filtered_content ) ) ) {
			$this->log_error( "No content found after filtering for language [{$lang_upper}]" );
			return false;
		}

		// Compter les blocs de la langue active trouvés (pour logging)
		$lang_escaped = preg_quote( $lang_upper, '/' );
		$blocks_count = preg_match_all( '/\[LANG:' . $lang_escaped . '\]/', $after_core, $matches );
		
		$this->log_info( "Filtered content for language [{$lang_upper}] - Original had {$blocks_count} block(s)" );

		// 4. COMBINER CORE + CONTENU FILTRÉ
		$filtered_prompt = $core_section . $filtered_content;

		// 5. NETTOYER LES ESPACES MULTIPLES (3+ lignes vides → 2 lignes vides)
		$filtered_prompt = preg_replace( '/\n{3,}/', "\n\n", $filtered_prompt );

		// 6. TRIM
		$filtered_prompt = trim( $filtered_prompt );

		// Vérifier que le résultat n'est pas vide
		if ( empty( $filtered_prompt ) ) {
			$this->log_error( 'Filtered prompt is empty after processing' );
			return false;
		}

		return $filtered_prompt;
	}

	/**
	 * Remplacer les variables dans le prompt filtré.
	 *
	 * Variables supportées :
	 * - {{LANGUAGE}} : Code langue (fr, en, es, etc.)
	 * - {{LANGUAGE_NAME}} : Nom complet de la langue (français, English, español, etc.)
	 * - {{USER_CONTEXT}} : Contexte utilisateur (si disponible via filtre)
	 *
	 * @since  2.5.1
	 * @since  2.5.2 Ajout de {{LANGUAGE_NAME}}
	 * @param  string $prompt   Prompt filtré.
	 * @param  string $language Langue active.
	 * @return string           Prompt avec variables remplacées.
	 */
	private function replace_variables( $prompt, $language ) {
		// Mapping langue → nom complet (comme demandé par le prompt engineer)
		$language_names = array(
			'fr' => 'français',
			'en' => 'English',
			'es' => 'español',
			'de' => 'Deutsch',
			'it' => 'italiano',
			'pt' => 'português',
		);
		
		$language_name = isset( $language_names[ $language ] ) ? $language_names[ $language ] : 'français';
		
		// Remplacer {{LANGUAGE}}
		$prompt = str_replace( '{{LANGUAGE}}', $language, $prompt );
		
		// Remplacer {{LANGUAGE_NAME}}
		$prompt = str_replace( '{{LANGUAGE_NAME}}', $language_name, $prompt );
		
		// Remplacer {{USER_CONTEXT}} si disponible
		// Note : Cette fonctionnalité est gérée via un filtre externe
		// On laisse le filtre faire son travail, on ne touche pas ici
		$user_context = apply_filters( 'eai_ml_prompt_user_context', '', $language );
		
		if ( ! empty( $user_context ) ) {
			$prompt = str_replace( '{{USER_CONTEXT}}', $user_context, $prompt );
			$this->log_debug( 'Replaced {{USER_CONTEXT}} with user context' );
		}
		
		// Logger les remplacements
		$this->log_debug( "Replaced {{LANGUAGE}} with: {$language}" );
		$this->log_debug( "Replaced {{LANGUAGE_NAME}} with: {$language_name}" );
		
		return $prompt;
	}

	/**
	 * Obtenir un prompt filtré depuis le cache.
	 *
	 * @since  2.5.0
	 * @param  string $prompt   Prompt source (utilisé pour générer la clé).
	 * @param  string $language Langue cible.
	 * @return string|false     Prompt en cache ou false si non trouvé.
	 */
	private function get_cached_prompt( $prompt, $language ) {
		// Générer une clé de cache unique basée sur le hash du prompt + langue
		$cache_key = $this->generate_cache_key( $prompt, $language );
		
		// Récupérer depuis le transient WordPress
		$cached = get_transient( $cache_key );
		
		if ( false !== $cached && is_string( $cached ) && ! empty( $cached ) ) {
			return $cached;
		}
		
		return false;
	}

	/**
	 * Mettre en cache un prompt filtré.
	 *
	 * @since 2.5.0
	 * @param string $prompt          Prompt source.
	 * @param string $language        Langue cible.
	 * @param string $filtered_prompt Prompt filtré.
	 */
	private function cache_filtered_prompt( $prompt, $language, $filtered_prompt ) {
		$cache_key = $this->generate_cache_key( $prompt, $language );
		
		// Stocker dans un transient WordPress
		$success = set_transient( $cache_key, $filtered_prompt, $this->cache_duration );
		
		if ( $success ) {
			$this->log_debug( "Cached filtered prompt for language: {$language} (duration: {$this->cache_duration}s)" );
		} else {
			$this->log_warning( "Failed to cache filtered prompt for language: {$language}" );
		}
	}

	/**
	 * Générer une clé de cache unique.
	 *
	 * @since  2.5.0
	 * @param  string $prompt   Prompt source.
	 * @param  string $language Langue cible.
	 * @return string           Clé de cache.
	 */
	private function generate_cache_key( $prompt, $language ) {
		// Hash MD5 du prompt pour éviter les clés trop longues
		$prompt_hash = md5( $prompt );
		
		// Format : eai_ml_prompt_{langue}_{hash}
		return $this->cache_prefix . $language . '_' . $prompt_hash;
	}

	/**
	 * Calculer et logger les métriques d'économie.
	 *
	 * @since 2.5.0
	 * @param string $original_prompt Prompt original complet.
	 * @param string $filtered_prompt Prompt filtré.
	 * @param string $language        Langue cible.
	 */
	private function calculate_and_log_metrics( $original_prompt, $filtered_prompt, $language ) {
		$original_size = strlen( $original_prompt );
		$filtered_size = strlen( $filtered_prompt );
		$saved_bytes = $original_size - $filtered_size;
		$saved_percent = ( $original_size > 0 ) ? round( ( $saved_bytes / $original_size ) * 100, 1 ) : 0;
		
		// Estimation tokens (1 token ≈ 4 caractères en moyenne)
		$saved_tokens = round( $saved_bytes / 4 );

		// Stocker les métriques
		$this->metrics = array(
			'language'       => $language,
			'original_bytes' => $original_size,
			'filtered_bytes' => $filtered_size,
			'saved_bytes'    => $saved_bytes,
			'saved_percent'  => $saved_percent,
			'saved_tokens'   => $saved_tokens,
			'timestamp'      => current_time( 'mysql' ),
		);

		// Logger les métriques
		$this->log_info( sprintf(
			"Filtering metrics | Language: %s | Original: %d bytes | Filtered: %d bytes | Saved: %d bytes (%.1f%%) | Est. tokens saved: ~%d",
			$language,
			$original_size,
			$filtered_size,
			$saved_bytes,
			$saved_percent,
			$saved_tokens
		) );

		// Permettre aux autres plugins d'accéder aux métriques
		do_action( 'eai_ml_prompt_filtered', $this->metrics );
	}

	/**
	 * Effacer le cache des prompts filtrés.
	 *
	 * Méthode publique pour permettre le nettoyage manuel du cache.
	 *
	 * @since  2.5.0
	 * @return int Nombre de transients supprimés.
	 */
	public function clear_cache() {
		global $wpdb;

		// Supprimer tous les transients avec le préfixe eai_ml_prompt_
		$prefix = $wpdb->esc_like( '_transient_' . $this->cache_prefix ) . '%';
		
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$prefix
			)
		);

		// Aussi les transients timeout
		$prefix_timeout = $wpdb->esc_like( '_transient_timeout_' . $this->cache_prefix ) . '%';
		
		$deleted_timeout = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$prefix_timeout
			)
		);

		$total_deleted = $deleted + $deleted_timeout;

		$this->log_info( "Cache cleared: {$total_deleted} transient(s) deleted" );

		return $total_deleted;
	}

	/**
	 * Obtenir les dernières métriques.
	 *
	 * @since  2.5.0
	 * @return array Métriques ou tableau vide.
	 */
	public function get_last_metrics() {
		return $this->metrics;
	}

	/**
	 * Logger un message d'information.
	 *
	 * @since 2.5.0
	 * @param string $message Message à logger.
	 */
	private function log_info( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( '[AI Engine Multilang v%s] [Prompt Filter] %s', EAI_ML_VERSION, $message ) );
		}
	}

	/**
	 * Logger un message de debug.
	 *
	 * @since 2.5.0
	 * @param string $message Message à logger.
	 */
	private function log_debug( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( sprintf( '[AI Engine Multilang v%s] [Prompt Filter] [DEBUG] %s', EAI_ML_VERSION, $message ) );
		}
	}

	/**
	 * Logger un avertissement.
	 *
	 * @since 2.5.0
	 * @param string $message Message à logger.
	 */
	private function log_warning( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( '[AI Engine Multilang v%s] [Prompt Filter] ⚠️  WARNING: %s', EAI_ML_VERSION, $message ) );
		}
	}

	/**
	 * Logger une erreur.
	 *
	 * @since 2.5.0
	 * @param string $message Message à logger.
	 */
	private function log_error( $message ) {
		error_log( sprintf( '[AI Engine Multilang v%s] [Prompt Filter] ❌ ERROR: %s', EAI_ML_VERSION, $message ) );
	}

	/**
	 * Nom pour le pipeline de test (EAI_Pipeline_Nameable).
	 *
	 * @since  2.6.0
	 * @return string
	 */
	public function get_pipeline_name() {
		return 'Preprocessing Langue';
	}

	/**
	 * Icône pour le pipeline de test (EAI_Pipeline_Nameable).
	 *
	 * @since  2.6.0
	 * @return string
	 */
	public function get_pipeline_icon() {
		return '🔤';
	}

	/**
	 * Description pour le pipeline de test (EAI_Pipeline_Nameable).
	 *
	 * @since  2.6.0
	 * @return string
	 */
	public function get_pipeline_description() {
		return 'Filtrage des blocs [LANG:XX] et remplacement des variables {{LANGUAGE}}';
	}
}

