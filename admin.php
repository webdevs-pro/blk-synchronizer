<?php

class BlkSettingsPage {
	public function __construct() {
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_init', array($this, 'init_settings'));
	}

	public function add_admin_menu() {
		$page = add_menu_page(
			'BL Synk', 
			'BL Synk', 
			'manage_options', 
			'bs-settings-page', 
			array($this, 'settings_page')
		);

      add_action( 'admin_print_styles-' . $page, array( $this, 'print_scripts_and_styles' ) );

	}

   public function print_scripts_and_styles() {
      wp_enqueue_script( 'blk-prism', BLK_SYNCHRONIZER_DIR_URL .'/assets/prism.js', array(), BLK_PLUGIN_VERSION, true );
      wp_enqueue_script( 'blk-admin-page', BLK_SYNCHRONIZER_DIR_URL .'/assets/admin-page.js', array( 'jquery' ), BLK_PLUGIN_VERSION, true );
      wp_enqueue_style( 'blk-prism', BLK_SYNCHRONIZER_DIR_URL .'/assets/prism.css', array(), BLK_PLUGIN_VERSION );
      wp_enqueue_style( 'blk-admin-page', BLK_SYNCHRONIZER_DIR_URL .'/assets/admin-page.css', array(), BLK_PLUGIN_VERSION );

		// Localize the script with necessary data
		$script_data = array(
			'logFilePath' => BLK_SYNCHRONIZER_LOGS_URL // Assuming this variable holds the URL path you want to pass
		);
		wp_localize_script( 'blk-admin-page', 'blkAdminPageData', $script_data );
   }

	public function init_settings() {
		register_setting('Blk', 'blk_settings');

		add_settings_section(
			'blk_import_section', 
			'Import Settings', 
			array( $this, 'blk_import_section_callback' ), 
			'Blk'
		);

		add_settings_field(
			'blk_api_token', 
			'BaseLinker API token', 
			array( $this, 'blk_api_token' ), 
			'Blk', 
			'blk_import_section'
		);
		add_settings_field(
			'blk_categories_to_ignore', 
			'Categories to ignore on import', 
			array( $this, 'blk_categories_field_render' ), 
			'Blk', 
			'blk_import_section'
		);
		add_settings_field(
			'blk_skus_to_ignore', 
			'SKUs to ignore on import', 
			array( $this, 'blk_skus_field_render' ), 
			'Blk', 
			'blk_import_section'
		);
		add_settings_field(
			'blk_lock_file_max_lifetime', 
			'Lock file max lifetime', 
			array( $this, 'blk_lock_file_max_lifetime_render' ), 
			'Blk', 
			'blk_import_section'
		);
		add_settings_field(
			'blk_synk_type', 
			'Synk type', 
			array( $this, 'blk_synk_type_render' ), 
			'Blk', 
			'blk_import_section'
		);
		add_settings_field(
			'blk_cron_interval', 
			'Cron interval', 
			array( $this, 'blk_cron_interval_render' ), 
			'Blk', 
			'blk_import_section'
		);
		add_settings_field(
			'blk_query_type', 
			'Query type', 
			array( $this, 'blk_query_type_render' ), 
			'Blk', 
			'blk_import_section'
		);
	}

	public function blk_import_section_callback() { 
		// Callback content for the import settings section
	}

	public function blk_api_token() {
		$blk_settings = get_option( 'blk_settings' );
		?>
		<input id='blk_api_token' type="text" name='blk_settings[blk_api_token]' style="width: 100%;" value="<?php echo $blk_settings['blk_api_token'] ?? ''; ?>"><br>
		<p></p>
		<?php
	}

	public function blk_categories_field_render() {
		$blk_settings = get_option( 'blk_settings' );
		?>
		<textarea id='blk_categories_to_ignore' name='blk_settings[blk_categories_to_ignore]' rows='6'  style="width: 100%;"><?php echo $blk_settings['blk_categories_to_ignore'] ?? ''; ?></textarea><br>
		<p>A comma-separated list of category IDs to ignore.</p>
		<?php
	}

	public function blk_skus_field_render() {
		$blk_settings = get_option( 'blk_settings' );
		?>
		<textarea id='blk_skus_to_ignore' name='blk_settings[blk_skus_to_ignore]' rows='6' style="width: 100%;"><?php echo $blk_settings['blk_skus_to_ignore'] ?? ''; ?></textarea><br>
		<p>A comma-separated list of SKUs to ignore.</p>
		<?php
	}

	public function blk_lock_file_max_lifetime_render() {
		$blk_settings = get_option( 'blk_settings' );
		?>
		<input id='blk_sblk_lock_file_max_lifetime'  type="number" name='blk_settings[blk_lock_file_max_lifetime]' value="<?php echo $blk_settings['blk_lock_file_max_lifetime'] ?? 10; ?>"><br>
		<p>Recommended value is 10–30 minutes.</p>
		<?php
	}

	public function blk_synk_type_render() {
		$blk_settings = get_option( 'blk_settings' );
		$query_type = isset( $blk_settings['blk_synk_type'] ) ? $blk_settings['blk_synk_type'] : 'manual';
		?>
		<select id='blk_blk_query_type' name='blk_settings[blk_query_type]'>
			<option value='manual' <?php selected( $query_type, 'manual' ); ?>>Manual</option>
			<option value='cron' <?php selected( $query_type, 'cron' ); ?>>Cron</option>
		</select><br>
		<?php
	}

	public function blk_cron_interval_render() {
		$blk_settings = get_option( 'blk_settings' );
		?>
		<input id='blk_cron_interval'  type="number" name='blk_settings[blk_cron_interval]' value="<?php echo $blk_settings['blk_cron_interval'] ?? 60; ?>"><br>
		<p>Recommended value is 20–60 minutes.</p>
		<?php
	}

	public function blk_query_type_render() {
		$blk_settings = get_option( 'blk_settings' );
		$query_type = isset( $blk_settings['blk_query_type'] ) ? $blk_settings['blk_query_type'] : 'all';
		?>
		<select id='blk_blk_query_type' name='blk_settings[blk_query_type]'>
			<option value='all' <?php selected( $query_type, 'all' ); ?>>All</option>
			<option value='chunks' <?php selected( $query_type, 'chunks' ); ?>>Chunks</option>
		</select><br>
		<p>Use 'Chunks' for slow servers.</p>
		<?php
	}

	public function settings_page() {
		$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'log';
		?>
		<div class="wrap">
			<h1>BS Synk Settings</h1>
			<h2 class="nav-tab-wrapper">
				<a href="?page=bs-settings-page&tab=log" class="nav-tab <?php echo $active_tab == 'log' ? 'nav-tab-active' : ''; ?>">Log</a>
				<a href="?page=bs-settings-page&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
			</h2>

			<br>

			<?php
			switch ( $active_tab ) {
				case 'settings':
					$this->render_settings_tab();
					break;

				default:
					$this->render_log_tab();
					break;
			}
			?>
		</div>
		<?php
	}


	private function render_settings_tab() {
		echo '<form action="options.php" method="post">';
			settings_fields( 'Blk' );
			do_settings_sections( 'Blk' );
			submit_button();
		echo '</form>';
	}


	private function render_log_tab() {
		if ( blk_is_import_locked() ) {
			echo '<div style=""><img class="wp-spinner" src="/wp-admin/images/spinner.gif" alt="spinner.gif"> <span style="font-weight: bold; font-size: 1.7em;">Import in progress!</span></div><br>';

			echo '<button id="blk-start-import" class="button button-primary disabled">Start import</button>';
		} else {
			echo '<button id="blk-start-import" class="button button-primary">Start import</button>';
		} 
		?>
		<button id="blk-stop-import" class="button button-primary" style="background-color: #dc3232; border-color: #dc3232; color: white;">Stop import</button>
		<?php

		$log_directory = BLK_SYNCHRONIZER_LOGS_PATH;
		$sorted_files = $this->get_sorted_log_files( $log_directory );

		echo '<div class="blk-log-wrapper">';
			echo '<select id="blk-log-file-select">';
			foreach ( $sorted_files as $file ) {
				echo '<option value="' . esc_attr( $file ) . '">' . esc_html( $file ) . '</option>';
			}
			echo '</select>';
			echo '&nbsp;&nbsp;<a href="#" id="blk-log-reload">Reload</a><br>';

			echo '<div id="blk-log"></div>';

		echo '</div>';
	}


	private function get_sorted_log_files( $directory ) {
		$debug_files         = array();
		$synchronizer_files  = array();

		if ( $handle = opendir( $directory ) ) {
			while ( false !== ( $entry = readdir( $handle ) ) ) {
				if ( '.' !== $entry && '..' !== $entry ) {
						if ( 0 === strpos( $entry, 'debug-' ) ) {
							$debug_files[] = $entry;
						} elseif ( 0 === strpos( $entry, 'synchronizer-' ) ) {
							$synchronizer_files[] = $entry;
						}
				}
			}
			closedir( $handle );
		}

		rsort( $debug_files );
		rsort( $synchronizer_files );

		return array_merge( $debug_files, $synchronizer_files );
	}
}

new BlkSettingsPage();