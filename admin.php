<?php

class BlkSettingsPage {
	public function __construct() {
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_init', array($this, 'init_settings'));
	}

	public function add_admin_menu() {
		add_menu_page(
			'BS Synk', 
			'BS Synk', 
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
		$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'settings';
		?>
		<div class="wrap">
			<h1>BS Synk Settings</h1>
			<h2 class="nav-tab-wrapper">
					<a href="?page=bs-settings-page&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
					<a href="?page=bs-settings-page&tab=log" class="nav-tab <?php echo $active_tab == 'log' ? 'nav-tab-active' : ''; ?>">Log</a>
			</h2>

			<br>

			<?php if ( blk_is_import_locked() ) {
				echo '<div style=""><img class="wp-spinner" src="/wp-admin/images/spinner.gif" alt="spinner.gif"> <span style="font-weight: bold; font-size: 1.7em;">Import in progress!</span></div><br>';

				echo '<button id="blk-start-import" class="button button-primary disabled">Start import</button>';
			} else {
				echo '<button id="blk-start-import" class="button button-primary">Start import</button>';
			} ?>
			<button id="blk-stop-import" class="button button-primary" style="background-color: #dc3232; border-color: #dc3232; color: white;">Stop import</button>

			<script type="text/javascript">
            jQuery(document).ready(function ($) {
					document.getElementById('blk-start-import').addEventListener('click', function() {
						const startButton = this;
						startButton.classList.add('disabled');

						fetch('/?action=blkSynchronizer&method=synchronize', {
							method: 'POST',
							headers: {
								'Content-Type': 'application/x-www-form-urlencoded',
							},
							body: '' // If your POST request needs data
						})
						.then(response => {
							if (!response.ok) {
									throw new Error('Network response was not ok');
							}
							return response.text();
						})
						.then(data => {
							console.log('import complete'); // Handle the response data
							startButton.classList.remove('disabled');
						})
						.catch(error => {
							console.error('There has been a problem with your fetch operation:', error);
							startButton.classList.remove('disabled');
							// Handle errors here
						});
					});


                $('#blk-stop-import').click(function (e) {
                    e.preventDefault();

                    var data = {
                        action: 'blk_stop_import',
                    };

                    // FETCHING XML
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

			<form action='options.php' method='post'>
					<?php
					if ($active_tab == 'settings') {
						settings_fields('Blk');
						do_settings_sections('Blk');
						submit_button();
					} elseif ($active_tab == 'log') {
						// Display log content here
						echo '<p>Log content goes here...</p>';
					}
					?>
			</form>
		</div>
		<?php
	}
}

new BlkSettingsPage();