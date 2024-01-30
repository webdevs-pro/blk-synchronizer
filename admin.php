<?php

class BlkSettingsPage {
	public function __construct() {
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_init', array($this, 'init_settings'));
	}

	public function add_admin_menu() {
		add_menu_page(
			'BL Synk', 
			'BL Synk', 
			'manage_options', 
			'bs-settings-page', 
			array($this, 'settings_page')
		);
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
		$blk_settings = get_option('blk_settings');
		?>
		<textarea id='blk_categories_to_ignore' name='blk_settings[blk_categories_to_ignore]' rows='6'  style="width: 100%;"><?php echo $blk_settings['blk_categories_to_ignore'] ?? ''; ?></textarea><br>
		<p>A comma-separated list of category IDs to ignore.</p>
		<?php
	}

	public function blk_skus_field_render() {
		$blk_settings = get_option('blk_settings');
		?>
		<textarea id='blk_skus_to_ignore' name='blk_settings[blk_skus_to_ignore]' rows='6' style="width: 100%;"><?php echo $blk_settings['blk_skus_to_ignore'] ?? ''; ?></textarea><br>
		<p>A comma-separated list of SKUs to ignore.</p>
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
		} ?>
		<button id="blk-stop-import" class="button button-primary" style="background-color: #dc3232; border-color: #dc3232; color: white;">Stop import</button>

		<script type="text/javascript">
			jQuery(document).ready(function ($) {

				$('#blk-start-import').click(function(e) {
					e.preventDefault();

					var $startButton = $(this);
					$startButton.addClass('disabled');

					$.ajax({
						url: '/?action=blkSynchronizer&method=synchronize',
						type: 'POST',
						success: function(data) {
								console.log('import complete'); // Handle the response data
								$startButton.removeClass('disabled');
						},
						error: function(jqXHR, textStatus, errorThrown) {
								console.error('There has been a problem with your AJAX operation:', textStatus, errorThrown);
								$startButton.removeClass('disabled');
								// Handle errors here
						}
					});
				});


				$('#blk-stop-import').click(function (e) {
					e.preventDefault();

					var data = {
							action: 'blk_stop_import',
					};

					$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: data,
							beforeSend: function (xhr) {
								$('#blk-stop-import').addClass('disabled');
								//  $('#frymo_ajax_result').html('&nbsp;');
							},
							success: function (response) {
								$('#blk-stop-import').removeClass('disabled');
								//  $('#frymo_ajax_result').html(response);
								console.log(response);
							}
					});
				});
				
			});
		</script>
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

			echo '<pre id="blk-log"></pre>';

		echo '</div>';

		?>
		<style>
			.wrap .notice {
				display: none;
			}
			.blk-log-wrapper {
				margin-top: 20px;
			}
			#blk-log {
				background-color: #fff;
				padding: 10px;
				height: 400px;
				overflow-y: auto;
				border: 1px solid #ccc;
				overscroll-behavior: contain;
			}
			#blk-log-reload.disabled {
				color: gray;        /* Change the text color to gray or any other color */
				pointer-events: none; /* This prevents the link from being clickable */
				cursor: default; 
			}
		</style>
		<script>
			jQuery(document).ready(function($) {
				// Function to load log file content
				function loadLogFile(filePath) {
					$('#blk-log-file-select').prop('disabled', true);
					$('#blk-log-reload').addClass('disabled');
					$('#blk-log-reload').blur();

					// Append a timestamp to the file path to prevent caching
					var cacheBuster = "?t=" + new Date().getTime();
					$.get(filePath + cacheBuster, function(data) {
						$('#blk-log').text(data).scrollTop($('#blk-log')[0].scrollHeight);
					});

					setTimeout(function() {
						$('#blk-log-file-select').prop('disabled', false);
						$('#blk-log-reload').removeClass('disabled');
					}, 500);
				}

				var currentFile = $('#blk-log-file-select').val();
				var logFilePath = '<?php echo esc_js( BLK_SYNCHRONIZER_LOGS_URL ); ?>' + currentFile;
				loadLogFile(logFilePath);

				// Handle change event for the log file selection
				$('#blk-log-file-select').change(function() {
					var selectedFile = $(this).val();
					var logFilePath = '<?php echo esc_js( BLK_SYNCHRONIZER_LOGS_URL ); ?>' + selectedFile;
					loadLogFile(logFilePath);
				});

				// Handle click event for the "Reload" link
				$('#blk-log-reload').click(function(e) {
					e.preventDefault();
					var currentFile = $('#blk-log-file-select').val();
					var logFilePath = '<?php echo esc_js( BLK_SYNCHRONIZER_LOGS_URL ); ?>' + currentFile;
					loadLogFile(logFilePath);
				});
			});
		</script>
		<?php


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