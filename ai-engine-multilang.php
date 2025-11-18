<?php
/**
 * Plugin Name: AI Engine Multilang by Elevatio
 * Plugin URI: https://github.com/cyrilgodon/ai-engine-multilang
 * Description: Gestion multilingue complète pour AI Engine avec Polylang. Détecte les changements de langue et traduit automatiquement l'interface du chatbot (textes UI, Quick Actions). Requiert AI Engine, Polylang et AI Engine Elevatio.
 * Version: 1.0.3
 * Author: Elevatio / Cyril Godon
 * Author URI: https://elevatio.fr
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: ai-engine-multilang
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Requires Plugins: ai-engine, polylang
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

define( 'EAI_ML_VERSION', '1.0.3' );
define( 'EAI_ML_PLUGIN_FILE', __FILE__ );
define( 'EAI_ML_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EAI_ML_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EAI_ML_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

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
 * Note : Les dépendances (AI Engine, Polylang) sont gérées nativement par
 * WordPress via le header "Requires Plugins". Pas besoin de vérification custom.
 * 
 * @since 1.0.3
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
 * Charge les classes et initialise les modules.
 * Priorité 20 pour charger APRÈS AI Engine et Elevatio.
 * 
 * Note : Les dépendances sont gérées par WordPress via "Requires Plugins".
 * Si elles manquent, WordPress affiche automatiquement un message et empêche
 * l'activation du plugin.
 * 
 * @since 1.0.0
 */
function eai_ml_init() {
	// Charger les modules
	require_once EAI_ML_PLUGIN_DIR . 'includes/class-ui-translator.php';
	require_once EAI_ML_PLUGIN_DIR . 'includes/class-qa-translator.php';
	require_once EAI_ML_PLUGIN_DIR . 'includes/class-conversation-handler.php';
	
	// Initialiser les modules
	EAI_ML_UI_Translator::get_instance()->init();
	EAI_ML_QA_Translator::get_instance()->init();
	EAI_ML_Conversation_Handler::get_instance()->init();
	
	// Log d'initialisation
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( sprintf(
			'[AI Engine Multilang v%s] Plugin initialized | Polylang: %s | Elevatio: %s',
			EAI_ML_VERSION,
			function_exists( 'pll_current_language' ) ? pll_current_language() : 'N/A',
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

