<?php
/**
 * Plugin Name: AI Engine Multilang by Elevatio
 * Plugin URI: https://github.com/cyrilgodon/ai-engine-multilang
 * Description: Gestion multilingue complète pour AI Engine avec Polylang. Détecte les changements de langue et traduit automatiquement l'interface du chatbot (textes UI, Quick Actions). Requiert AI Engine, Polylang et AI Engine Elevatio.
 * Version: 1.0.0
 * Author: Elevatio / Cyril Godon
 * Author URI: https://elevatio.fr
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: ai-engine-multilang
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Requires Plugins: ai-engine, polylang
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

define( 'EAI_ML_VERSION', '1.0.0' );
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
// VÉRIFICATION DES DÉPENDANCES
// ============================================================================

/**
 * Vérifier les dépendances au chargement du plugin.
 * 
 * Le plugin requiert :
 * - AI Engine Pro (Meow Apps)
 * - Polylang ou Polylang Pro (WP Syntex)
 * - AI Engine Elevatio v2.6.0+ (recommandé mais optionnel)
 * 
 * @since 1.0.0
 * @return array Liste des dépendances manquantes
 */
function eai_ml_check_dependencies() {
	$missing = array();
	
	// Vérifier AI Engine
	if ( ! class_exists( 'Meow_MWAI_Core' ) ) {
		$missing[] = array(
			'name' => 'AI Engine Pro',
			'url'  => 'https://ai-engine.meowapps.com/',
		);
	}
	
	// Vérifier Polylang (gratuit ou Pro)
	// Méthode 1 : Vérifier si fonction existe (après chargement)
	// Méthode 2 : Vérifier si constante POLYLANG_VERSION existe
	// Méthode 3 : Vérifier si plugins sont actifs
	$polylang_active = defined( 'POLYLANG_VERSION' ) 
		|| function_exists( 'pll_current_language' )
		|| class_exists( 'Polylang' )
		|| ( function_exists( 'is_plugin_active' ) && (
			is_plugin_active( 'polylang/polylang.php' ) 
			|| is_plugin_active( 'polylang-pro/polylang.php' )
		) );
	
	if ( ! $polylang_active ) {
		$missing[] = array(
			'name' => 'Polylang ou Polylang Pro',
			'url'  => 'https://wordpress.org/plugins/polylang/',
		);
	}
	
	return $missing;
}

/**
 * Vérifier la compatibilité de version AI Engine Elevatio.
 * 
 * @since 1.0.0
 * @return bool True si compatible, false sinon
 */
function eai_ml_check_elevatio_compatibility() {
	if ( ! defined( 'EAI_VERSION' ) ) {
		return true; // Pas installé, on ne bloque pas
	}
	
	// Vérifier version minimale 2.6.0
	return version_compare( EAI_VERSION, '2.6.0', '>=' );
}

/**
 * Hook d'activation : vérifier les dépendances.
 * 
 * @since 1.0.0
 */
function eai_ml_activate() {
	$missing = eai_ml_check_dependencies();
	
	if ( ! empty( $missing ) ) {
		// Construire le message d'erreur
		$plugin_links = array();
		foreach ( $missing as $plugin ) {
			$plugin_links[] = sprintf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( $plugin['url'] ),
				esc_html( $plugin['name'] )
			);
		}
		
		// Désactiver le plugin
		deactivate_plugins( EAI_ML_PLUGIN_BASENAME );
		
		// Message d'erreur
		wp_die(
			sprintf(
				'<h1>%s</h1><p>%s</p><ul><li>%s</li></ul><p><a href="%s">%s</a></p>',
				esc_html__( 'Plugin non activé', 'ai-engine-multilang' ),
				esc_html__( 'AI Engine Multilang requiert les plugins suivants :', 'ai-engine-multilang' ),
				implode( '</li><li>', $plugin_links ),
				esc_url( admin_url( 'plugins.php' ) ),
				esc_html__( 'Retour aux plugins', 'ai-engine-multilang' )
			)
		);
	}
	
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
// NOTICE ADMIN : Compatibilité AI Engine Elevatio
// ============================================================================

/**
 * Afficher une notice si AI Engine Elevatio est trop ancien.
 * 
 * @since 1.0.0
 */
function eai_ml_elevatio_version_warning() {
	if ( ! eai_ml_check_elevatio_compatibility() ) {
		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'AI Engine Multilang :', 'ai-engine-multilang' ); ?></strong>
				<?php
				printf(
					/* translators: %s: version actuelle d'AI Engine Elevatio */
					esc_html__( 'Ce plugin nécessite AI Engine Elevatio v2.6.0+ (version actuelle : %s). Certaines fonctionnalités peuvent ne pas fonctionner correctement.', 'ai-engine-multilang' ),
					esc_html( EAI_VERSION )
				);
				?>
			</p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'eai_ml_elevatio_version_warning' );

// ============================================================================
// INITIALISATION DU PLUGIN
// ============================================================================

/**
 * Initialiser le plugin AI Engine Multilang.
 * 
 * Charge les classes et initialise les modules uniquement si les dépendances
 * sont présentes. Priorité 20 pour charger APRÈS AI Engine et Elevatio.
 * 
 * @since 1.0.0
 */
function eai_ml_init() {
	// Vérifier les dépendances à chaque chargement
	$missing = eai_ml_check_dependencies();
	
	if ( ! empty( $missing ) ) {
		// Dépendances manquantes, sortir silencieusement
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$missing_names = array_column( $missing, 'name' );
			error_log( '[AI Engine Multilang v' . EAI_ML_VERSION . '] Dependencies missing: ' . implode( ', ', $missing_names ) );
		}
		return;
	}
	
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

