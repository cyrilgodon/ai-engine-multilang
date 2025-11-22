<?php
/**
 * Admin Settings Page.
 *
 * Gère la page de configuration du plugin AI Engine Multilang dans l'admin WordPress.
 *
 * @package    AI_Engine_Multilang
 * @subpackage AI_Engine_Multilang/includes
 * @since      1.0.5
 * @version    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class EAI_ML_Admin_Settings
 *
 * Gère l'interface d'administration du plugin.
 *
 * @since 1.0.5
 */
class EAI_ML_Admin_Settings {

	/**
	 * Instance unique (Singleton).
	 *
	 * @since  1.0.5
	 * @access private
	 * @var    EAI_ML_Admin_Settings
	 */
	private static $instance = null;

	/**
	 * Obtenir l'instance unique.
	 *
	 * @since  1.0.5
	 * @return EAI_ML_Admin_Settings
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
	 * @since 1.0.5
	 */
	private function __construct() {
		// Enregistrer la page admin
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Enregistrer la page dans le menu admin.
	 *
	 * @since 1.0.5
	 */
	public function register_admin_menu() {
		add_options_page(
			__( 'AI Engine Multilang', 'ai-engine-multilang' ),
			__( 'Multilingue', 'ai-engine-multilang' ),
			'manage_options',
			'ai-engine-multilang',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enregistrer les paramètres WordPress.
	 *
	 * @since 1.0.5
	 */
	public function register_settings() {
		register_setting(
			'eai_ml_settings_group',
			'eai_ml_settings',
			array(
				'type'              => 'array',
				'default'           => $this->get_default_settings(),
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		// Section : Langues actives
		add_settings_section(
			'eai_ml_languages_section',
			__( '🌐 Langues actives', 'ai-engine-multilang' ),
			array( $this, 'render_languages_section_info' ),
			'ai-engine-multilang'
		);

		add_settings_field(
			'supported_languages',
			__( 'Langues supportées', 'ai-engine-multilang' ),
			array( $this, 'render_supported_languages_field' ),
			'ai-engine-multilang',
			'eai_ml_languages_section'
		);

		add_settings_field(
			'default_language',
			__( 'Langue par défaut', 'ai-engine-multilang' ),
			array( $this, 'render_default_language_field' ),
			'ai-engine-multilang',
			'eai_ml_languages_section'
		);

		// Section : Configuration du filtre de prompts
		add_settings_section(
			'eai_ml_prompt_filter_section',
			__( '🔧 Filtre de prompts multilingues', 'ai-engine-multilang' ),
			array( $this, 'render_prompt_filter_section_info' ),
			'ai-engine-multilang'
		);

		add_settings_field(
			'prompt_filter_enabled',
			__( 'Activer le filtrage', 'ai-engine-multilang' ),
			array( $this, 'render_prompt_filter_enabled_field' ),
			'ai-engine-multilang',
			'eai_ml_prompt_filter_section'
		);

		add_settings_field(
			'prompt_filter_priority',
			__( 'Priorité du hook', 'ai-engine-multilang' ),
			array( $this, 'render_prompt_filter_priority_field' ),
			'ai-engine-multilang',
			'eai_ml_prompt_filter_section'
		);

		add_settings_field(
			'prompt_filter_debug',
			__( 'Mode debug', 'ai-engine-multilang' ),
			array( $this, 'render_prompt_filter_debug_field' ),
			'ai-engine-multilang',
			'eai_ml_prompt_filter_section'
		);
	}

	/**
	 * Paramètres par défaut.
	 *
	 * @since  1.0.5
	 * @return array
	 */
	private function get_default_settings() {
		return array(
			'supported_languages'      => array( 'fr', 'en' ),
			'default_language'         => 'fr',
			'prompt_filter_enabled'    => true,
			'prompt_filter_priority'   => 5,
			'prompt_filter_debug'      => false,
		);
	}

	/**
	 * Sanitize les paramètres.
	 *
	 * @since  1.0.5
	 * @param  array $input Données du formulaire
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$output = array();

		// Langues supportées
		if ( isset( $input['supported_languages'] ) && is_array( $input['supported_languages'] ) ) {
			$output['supported_languages'] = array_map( 'sanitize_text_field', $input['supported_languages'] );
		} else {
			$output['supported_languages'] = array( 'fr', 'en' );
		}

		// Langue par défaut
		$output['default_language'] = isset( $input['default_language'] ) 
			? sanitize_text_field( $input['default_language'] ) 
			: 'fr';

		// Filtre de prompts activé
		$output['prompt_filter_enabled'] = isset( $input['prompt_filter_enabled'] ) 
			? (bool) $input['prompt_filter_enabled'] 
			: true;

		// Priorité du hook
		$output['prompt_filter_priority'] = isset( $input['prompt_filter_priority'] ) 
			? absint( $input['prompt_filter_priority'] ) 
			: 5;

		// Mode debug
		$output['prompt_filter_debug'] = isset( $input['prompt_filter_debug'] ) 
			? (bool) $input['prompt_filter_debug'] 
			: false;

		return $output;
	}

	/**
	 * Rendre la page de paramètres.
	 *
	 * @since 1.0.5
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Vous n\'avez pas les permissions nécessaires.', 'ai-engine-multilang' ) );
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<div class="notice notice-info">
				<p>
					<strong><?php esc_html_e( 'ℹ️ À propos de ce plugin :', 'ai-engine-multilang' ); ?></strong>
					<?php esc_html_e( 'AI Engine Multilang gère automatiquement le multilingue pour AI Engine : traduction de l\'interface, filtrage des prompts par langue, et gestion intelligente des conversations multilingues.', 'ai-engine-multilang' ); ?>
				</p>
			</div>

			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'eai_ml_settings_group' );
				do_settings_sections( 'ai-engine-multilang' );
				submit_button();
				?>
			</form>

			<div class="card" style="max-width: 800px; margin-top: 20px;">
				<h2><?php esc_html_e( '📝 Syntaxe des prompts multilingues', 'ai-engine-multilang' ); ?></h2>
				<p><?php esc_html_e( 'Utilisez cette syntaxe dans vos prompts AI Engine :', 'ai-engine-multilang' ); ?></p>
				<pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto;">
<strong>[LANG:FR]</strong>
Texte en français uniquement
<strong>[/LANG:FR]</strong>

<strong>[LANG:EN]</strong>
Text in English only
<strong>[/LANG:EN]</strong>

<strong>[LANG:ES]</strong>
Texto en español solamente
<strong>[/LANG:ES]</strong>

Placeholders disponibles :
- <strong>{{LANGUAGE}}</strong> → Remplacé par le code langue (ex: fr, en, es)
- <strong>{{LANGUAGE_NAME}}</strong> → Remplacé par le nom de la langue (ex: Français, English, Español)
				</pre>

				<h3><?php esc_html_e( '✅ Économie de tokens', 'ai-engine-multilang' ); ?></h3>
				<p><?php esc_html_e( 'Le filtrage multilingue permet d\'économiser jusqu\'à 40% de tokens en envoyant uniquement le contenu de la langue active.', 'ai-engine-multilang' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Infos section langues.
	 *
	 * @since 1.0.5
	 */
	public function render_languages_section_info() {
		echo '<p>' . esc_html__( 'Configurez les langues supportées par votre site.', 'ai-engine-multilang' ) . '</p>';
	}

	/**
	 * Infos section filtre de prompts.
	 *
	 * @since 1.0.5
	 */
	public function render_prompt_filter_section_info() {
		echo '<p>' . esc_html__( 'Le filtre de prompts permet d\'envoyer uniquement le contenu de la langue active au LLM.', 'ai-engine-multilang' ) . '</p>';
	}

	/**
	 * Champ : Langues supportées.
	 *
	 * @since 1.0.5
	 */
	public function render_supported_languages_field() {
		$settings = get_option( 'eai_ml_settings', $this->get_default_settings() );
		$supported = isset( $settings['supported_languages'] ) ? $settings['supported_languages'] : array( 'fr', 'en' );
		
		$available_languages = array(
			'fr' => '🇫🇷 Français',
			'en' => '🇬🇧 English',
			'es' => '🇪🇸 Español',
			'de' => '🇩🇪 Deutsch',
			'it' => '🇮🇹 Italiano',
			'pt' => '🇵🇹 Português',
		);

		foreach ( $available_languages as $code => $label ) {
			$checked = in_array( $code, $supported, true );
			printf(
				'<label style="margin-right: 20px;"><input type="checkbox" name="eai_ml_settings[supported_languages][]" value="%s" %s> %s</label>',
				esc_attr( $code ),
				checked( $checked, true, false ),
				esc_html( $label )
			);
		}
		echo '<p class="description">' . esc_html__( 'Sélectionnez les langues que vous souhaitez supporter.', 'ai-engine-multilang' ) . '</p>';
	}

	/**
	 * Champ : Langue par défaut.
	 *
	 * @since 1.0.5
	 */
	public function render_default_language_field() {
		$settings = get_option( 'eai_ml_settings', $this->get_default_settings() );
		$default = isset( $settings['default_language'] ) ? $settings['default_language'] : 'fr';
		
		$available_languages = array(
			'fr' => '🇫🇷 Français',
			'en' => '🇬🇧 English',
			'es' => '🇪🇸 Español',
			'de' => '🇩🇪 Deutsch',
			'it' => '🇮🇹 Italiano',
			'pt' => '🇵🇹 Português',
		);

		echo '<select name="eai_ml_settings[default_language]">';
		foreach ( $available_languages as $code => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $code ),
				selected( $default, $code, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Langue utilisée si la détection automatique échoue.', 'ai-engine-multilang' ) . '</p>';
	}

	/**
	 * Champ : Activer le filtrage.
	 *
	 * @since 1.0.5
	 */
	public function render_prompt_filter_enabled_field() {
		$settings = get_option( 'eai_ml_settings', $this->get_default_settings() );
		$enabled = isset( $settings['prompt_filter_enabled'] ) ? (bool) $settings['prompt_filter_enabled'] : true;
		
		printf(
			'<label><input type="checkbox" name="eai_ml_settings[prompt_filter_enabled]" value="1" %s> %s</label>',
			checked( $enabled, true, false ),
			esc_html__( 'Activer le filtrage automatique des prompts multilingues', 'ai-engine-multilang' )
		);
		echo '<p class="description">' . esc_html__( 'Si activé, seul le contenu de la langue active sera envoyé au LLM.', 'ai-engine-multilang' ) . '</p>';
	}

	/**
	 * Champ : Priorité du hook.
	 *
	 * @since 1.0.5
	 */
	public function render_prompt_filter_priority_field() {
		$settings = get_option( 'eai_ml_settings', $this->get_default_settings() );
		$priority = isset( $settings['prompt_filter_priority'] ) ? absint( $settings['prompt_filter_priority'] ) : 5;
		
		printf(
			'<input type="number" name="eai_ml_settings[prompt_filter_priority]" value="%d" min="1" max="999" step="1" class="small-text">',
			$priority
		);
		echo '<p class="description">' . esc_html__( 'Priorité du filtre sur le hook mwai_ai_instructions (défaut: 5). Plus le nombre est bas, plus le filtre s\'exécute tôt.', 'ai-engine-multilang' ) . '</p>';
	}

	/**
	 * Champ : Mode debug.
	 *
	 * @since 1.0.5
	 */
	public function render_prompt_filter_debug_field() {
		$settings = get_option( 'eai_ml_settings', $this->get_default_settings() );
		$debug = isset( $settings['prompt_filter_debug'] ) ? (bool) $settings['prompt_filter_debug'] : false;
		
		printf(
			'<label><input type="checkbox" name="eai_ml_settings[prompt_filter_debug]" value="1" %s> %s</label>',
			checked( $debug, true, false ),
			esc_html__( 'Activer les logs de debug', 'ai-engine-multilang' )
		);
		echo '<p class="description">' . esc_html__( 'Les logs seront écrits dans debug.log si WP_DEBUG est activé.', 'ai-engine-multilang' ) . '</p>';
	}
}


