<?php
/**
 * Plugin Name: AI Engine Multilang by Elevatio
 * Plugin URI: https://github.com/cyrilgodon/ai-engine-multilang
 * Description: Gestion multilingue complète pour AI Engine avec Polylang. Détecte les changements de langue et traduit automatiquement l'interface du chatbot (textes UI, Quick Actions). Requiert AI Engine, Polylang et AI Engine Elevatio.
 * Version: 1.4.1
 * Author: Elevatio
 * Author URI: https://elevatio.fr
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: ai-engine-multilang
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Update URI: https://github.com/cyrilgodon/ai-engine-multilang
 * 
 * @package AI_Engine_Multilang
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// ============================================================================
// CONSTANTES DU PLUGIN
// ============================================================================

define( 'EAI_ML_VERSION', '1.4.1' );
define( 'EAI_ML_PLUGIN_FILE', __FILE__ );
define( 'EAI_ML_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EAI_ML_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EAI_ML_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// ============================================================================
// INTERFACE : Pipeline Nameable (chargée AVANT plugins_loaded)
// ============================================================================
// Charger l'interface stub IMMÉDIATEMENT pour compatibilité avec AI Engine Elevatio.
// Elevatio se charge en priorité 2-6, donc l'interface doit exister avant.
if ( ! interface_exists( 'EAI_Pipeline_Nameable' ) ) {
	interface EAI_Pipeline_Nameable {
		public function get_pipeline_name();
		public function get_pipeline_icon();
		public function get_pipeline_description();
	}
}

// ============================================================================
// 🚀 PLUGIN UPDATE CHECKER - GitHub Integration
// ============================================================================
if ( file_exists( EAI_ML_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once EAI_ML_PLUGIN_DIR . 'vendor/autoload.php';
	
	if ( class_exists( 'YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
		$eaiMLUpdateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			'https://github.com/cyrilgodon/ai-engine-multilang',
			__FILE__,
			'ai-engine-multilang'
		);
		$eaiMLUpdateChecker->setBranch('master');
	}
}

// ============================================================================
// HOOKS D'ACTIVATION / DÉSACTIVATION
// ============================================================================

/**
 * Hook d'activation.
 * 
 * Note : Pas de vérification de dépendances à l'activation. Le plugin s'active
 * toujours et vérifie les dépendances au runtime (plugins_loaded). Si AI Engine
 * ou Polylang manquent, le plugin ne fait rien (graceful degradation).
 * 
 * Raison : AI Engine Pro et Polylang Pro sont des plugins premium non présents
 * sur WordPress.org, donc le système "Requires Plugins:" ne peut pas les détecter.
 * 
 * @since 1.0.4
 */
function eai_ml_activate() {
	// Log d'activation
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[AI Engine Multilang v' . EAI_ML_VERSION . '] Plugin activated' );
	}
}
register_activation_hook( __FILE__, 'eai_ml_activate' );

/**
 * Hook de désactivation.
 * 
 * @since 1.0.0
 */
function eai_ml_deactivate() {
	// Log de désactivation
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[AI Engine Multilang v' . EAI_ML_VERSION . '] Plugin deactivated' );
	}
}
register_deactivation_hook( __FILE__, 'eai_ml_deactivate' );

// ============================================================================
// INITIALISATION DU PLUGIN
// ============================================================================

/**
 * Initialiser le plugin AI Engine Multilang.
 * 
 * Charge les classes et initialise les modules UNIQUEMENT si les dépendances
 * sont présentes. Priorité 20 pour charger APRÈS AI Engine et Elevatio.
 * 
 * Si AI Engine ou Polylang manquent, le plugin ne fait rien (graceful degradation).
 * Pas d'erreur, pas de plantage, juste inactif.
 * 
 * @since 1.0.0
 */
function eai_ml_init() {
	// Vérifier Polylang (obligatoire)
	if ( ! function_exists( 'pll_current_language' ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[AI Engine Multilang v' . EAI_ML_VERSION . '] Polylang not found, plugin inactive' );
		}
		return; // Sortir silencieusement
	}
	
	// Vérifier AI Engine (obligatoire)
	if ( ! class_exists( 'Meow_MWAI_Core' ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[AI Engine Multilang v' . EAI_ML_VERSION . '] AI Engine not found, plugin inactive' );
		}
		return; // Sortir silencieusement
	}
	
	// Les 2 dépendances sont présentes, charger les modules
	require_once EAI_ML_PLUGIN_DIR . 'includes/class-ui-translator.php';
	require_once EAI_ML_PLUGIN_DIR . 'includes/class-qa-translator.php';
	require_once EAI_ML_PLUGIN_DIR . 'includes/class-conversation-handler.php';
	require_once EAI_ML_PLUGIN_DIR . 'includes/class-prompt-filter.php';
	require_once EAI_ML_PLUGIN_DIR . 'includes/class-admin-settings.php';
	
	// Initialiser les modules
	EAI_ML_UI_Translator::get_instance()->init();
	EAI_ML_QA_Translator::get_instance()->init();
	EAI_ML_Conversation_Handler::get_instance()->init();
	
	// Initialiser la page admin
	EAI_ML_Admin_Settings::get_instance();
	
	// Initialiser le filtre de prompts multilingues
	$settings = get_option( 'eai_ml_settings', array( 'prompt_filter_enabled' => true, 'prompt_filter_priority' => 5 ) );
	
	// LOG FORCÉ pour diagnostic
	error_log( '🔥 [AI Engine Multilang] Prompt Filter Settings: ' . print_r( $settings, true ) );
	
	if ( ! empty( $settings['prompt_filter_enabled'] ) ) {
		$prompt_filter = EAI_ML_Prompt_Filter::get_instance();
		$priority = isset( $settings['prompt_filter_priority'] ) ? absint( $settings['prompt_filter_priority'] ) : 5;
		add_filter( 'mwai_ai_instructions', array( $prompt_filter, 'filter_prompt' ), $priority, 2 );
		
		error_log( "🔥 [AI Engine Multilang] Prompt Filter ENABLED with priority {$priority} 🔥" );
	} else {
		error_log( '❌ [AI Engine Multilang] Prompt Filter DISABLED in settings' );
	}
	
	// Log d'initialisation
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( sprintf(
			'[AI Engine Multilang v%s] Plugin initialized | Polylang: %s | Elevatio: %s',
			EAI_ML_VERSION,
			pll_current_language() ?: 'N/A',
			defined( 'EAI_VERSION' ) ? EAI_VERSION : 'Not installed'
		) );
	}
}
add_action( 'plugins_loaded', 'eai_ml_init', 20 ); // Priorité 20 = APRÈS AI Engine (10) et Elevatio (15)

// ============================================================================
// CHARGEMENT DES TRADUCTIONS
// ============================================================================

/**
 * Charger les fichiers de traduction du plugin.
 * 
 * @since 1.0.0
 */
function eai_ml_load_textdomain() {
	load_plugin_textdomain(
		'ai-engine-multilang',
		false,
		dirname( EAI_ML_PLUGIN_BASENAME ) . '/languages'
	);
}
add_action( 'plugins_loaded', 'eai_ml_load_textdomain', 5 );

