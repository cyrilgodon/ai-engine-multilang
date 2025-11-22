<?php
/**
 * Uninstall Script - AI Engine Multilang
 *
 * Nettoyage complet lors de la désinstallation du plugin :
 * - Suppression des options WordPress
 * - Nettoyage du localStorage (via notice admin)
 * - Suppression des transients
 *
 * @package AI_Engine_Multilang
 * @since 1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit; // Exit if uninstall not called from WordPress
}

/**
 * Supprimer les options WordPress (si le plugin en créait).
 *
 * Note : AI Engine Multilang v1.0.0 ne crée PAS d'options WordPress,
 * tout est géré côté client (localStorage). Cette section est préparée
 * pour les versions futures qui pourraient stocker des settings.
 */
function eai_ml_delete_options() {
	global $wpdb;

	// Options à supprimer (si créées dans le futur)
	$options_to_delete = array(
		'eai_ml_settings',
		'eai_ml_version',
		'eai_ml_popup_dismissed',
	);

	foreach ( $options_to_delete as $option ) {
		delete_option( $option );
		delete_site_option( $option ); // Pour multisite
	}

	// Log de suppression
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[AI Engine Multilang] Options deleted during uninstall' );
	}
}

/**
 * Supprimer les transients liés au plugin.
 */
function eai_ml_delete_transients() {
	global $wpdb;

	// Supprimer tous les transients commençant par 'eai_ml_'
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			'_transient_eai_ml_%',
			'_transient_timeout_eai_ml_%'
		)
	);

	// Pour multisite
	if ( is_multisite() ) {
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
				'_site_transient_eai_ml_%',
				'_site_transient_timeout_eai_ml_%'
			)
		);
	}

	// Log de suppression
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[AI Engine Multilang] Transients deleted during uninstall' );
	}
}

/**
 * Supprimer les métadonnées utilisateurs (si le plugin en créait).
 */
function eai_ml_delete_user_meta() {
	global $wpdb;

	// Supprimer toutes les user meta commençant par 'eai_ml_'
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
			'eai_ml_%'
		)
	);

	// Log de suppression
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[AI Engine Multilang] User meta deleted during uninstall' );
	}
}

/**
 * Notice admin pour informer du localStorage (ne peut pas être nettoyé côté serveur).
 *
 * Note : Cette fonction ne sera jamais appelée car le plugin est déjà désinstallé.
 * C'est une note pour le développeur : le localStorage persiste côté client et
 * ne peut pas être nettoyé par PHP. Les clés concernées :
 * - eai_ml_last_language
 * - eai_ml_lang_alert_cooldown
 *
 * Ces clés sont inoffensives et seront écrasées si le plugin est réinstallé.
 */

// ============================================================================
// EXÉCUTION DU SCRIPT DE DÉSINSTALLATION
// ============================================================================

eai_ml_delete_options();
eai_ml_delete_transients();
eai_ml_delete_user_meta();

// Log final
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	error_log( '[AI Engine Multilang] Uninstall complete - All data removed' );
}


