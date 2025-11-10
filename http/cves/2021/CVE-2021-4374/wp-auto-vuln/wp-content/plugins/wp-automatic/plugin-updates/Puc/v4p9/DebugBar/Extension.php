<?php
if ( !class_exists('Puc_v4p9_DebugBar_Extension', false) ):

	class Puc_v4p9_DebugBar_Extension {
		const RESPONSE_BODY_LENGTH_LIMIT = 4000;

		/** @var Puc_v4p9_UpdateChecker */
		protected $updateChecker;
		protected $panelClass = 'Puc_v4p9_DebugBar_Panel';

		public function __construct($updateChecker, $panelClass = null) {
			$this->updateChecker = $updateChecker;
			if ( isset($panelClass) ) {
				$this->panelClass = $panelClass;
			}

			add_filter('debug_bar_panels', array($this, 'addDebugBarPanel'));
			add_action('debug_bar_enqueue_scripts', array($this, 'enqueuePanelDependencies'));

			add_action('wp_ajax_puc_v4_debug_check_now', array($this, 'ajaxCheckNow'));
		}

		/**
		 * Register the PUC Debug Bar panel.
		 *
		 * @param array $panels
		 * @return array
		 */
		public function addDebugBarPanel($panels) {
			if ( $this->updateChecker->userCanInstallUpdates() ) {
				$panels[] = new $this->panelClass($this->updateChecker);
			}
			return $panels;
		}

		/**
		 * Enqueue our Debug Bar scripts and styles.
		 */
		public function enqueuePanelDependencies() {
			wp_enqueue_style(
				'puc-debug-bar-style-v4',
				$this->getLibraryUrl("/css/puc-debug-bar.css"),
				array('debug-bar'),
				'20171124'
			);

			wp_enqueue_script(
				'puc-debug-bar-js-v4',
				$this->getLibraryUrl("/js/debug-bar.js"),
				array('jquery'),
				'20170516'
			);
		}

		/**
		 * Run an update check and output the result. Useful for making sure that
		 * the update checking process works as expected.
		 */
		public function ajaxCheckNow() {
			if ( $_POST['uid'] !== $this->updateChecker->getUniqueName('uid') ) {
				return;
			}
			$this->preAjaxRequest();
			$update = $this->updateChecker->checkForUpdates();
			if ( $update !== null ) {
				echo "An update is available:";
				echo '<pre>', htmlentities(print_r($update, true)), '</pre>';
			} else {
				echo 'No updates found.';
			}

			$errors = $this->updateChecker->getLastRequestApiErrors();
			if ( !empty($errors) ) {
				printf('<p>The update checker encountered %d API error%s.</p>', count($errors), (count($errors) > 1) ? 's' : '');

				foreach (array_values($errors) as $num => $item) {
					$wpError = $item['error'];
					/** @var WP_Error $wpError */
					printf('<h4>%d) %s</h4>', $num + 1, esc_html($wpError->get_error_message()));

					echo '<dl>';
					printf('<dt>Error code:</dt><dd><code>%s</code></dd>', esc_html($wpError->get_error_code()));

					if ( isset($item['url']) ) {
						printf('<dt>Requested URL:</dt><dd><code>%s</code></dd>', esc_html($item['url']));
					}

					if ( isset($item['httpResponse']) ) {
						if ( is_wp_error($item['httpResponse']) ) {
							$httpError = $item['httpResponse'];
							/** @var WP_Error $httpError */
							printf(
								'<dt>WordPress HTTP API error:</dt><dd>%s (<code>%s</code>)</dd>',
								esc_html($httpError->get_error_message()),
								esc_html($httpError->get_error_code())
							);
						} else {
							//Status code.
							printf(
								'<dt>HTTP status:</dt><dd><code>%d %s</code></dd>',
								wp_remote_retrieve_response_code($item['httpResponse']),
								wp_remote_retrieve_response_message($item['httpResponse'])
							);

							//Headers.
							echo '<dt>Response headers:</dt><dd><pre>';
							foreach (wp_remote_retrieve_headers($item['httpResponse']) as $name => $value) {
								printf("%s: %s\n", esc_html($name), esc_html($value));
							}
							echo '</pre></dd>';

							//Body.
							$body = wp_remote_retrieve_body($item['httpResponse']);
							if ( $body === '' ) {
								$body = '(Empty response.)';
							} else if ( strlen($body) > self::RESPONSE_BODY_LENGTH_LIMIT ) {
								$length = strlen($body);
								$body = substr($body, 0, self::RESPONSE_BODY_LENGTH_LIMIT)
									. sprintf("\n(Long string truncated. Total length: %d bytes.)", $length);
							}

							printf('<dt>Response body:</dt><dd><pre>%s</pre></dd>', esc_html($body));
						}
					}
					echo '<dl>';
				}
			}

			exit;
		}

		/**
		 * Check access permissions and enable error display (for debugging).
		 */
		protected function preAjaxRequest() {
			if ( !$this->updateChecker->userCanInstallUpdates() ) {
				die('Access denied');
			}
			check_ajax_referer('puc-ajax');

			error_reporting(E_ALL);
			@ini_set('display_errors', 'On');
		}

		/**
		 * @param string $filePath
		 * @return string
		 */
		private function getLibraryUrl($filePath) {
			$absolutePath = realpath(dirname(__FILE__) . '/../../../' . ltrim($filePath, '/'));

			//Where is the library located inside the WordPress directory structure?
			$absolutePath = Puc_v4p9_Factory::normalizePath($absolutePath);

			$pluginDir = Puc_v4p9_Factory::normalizePath(WP_PLUGIN_DIR);
			$muPluginDir = Puc_v4p9_Factory::normalizePath(WPMU_PLUGIN_DIR);
			$themeDir = Puc_v4p9_Factory::normalizePath(get_theme_root());

			if ( (strpos($absolutePath, $pluginDir) === 0) || (strpos($absolutePath, $muPluginDir) === 0) ) {
				//It's part of a plugin.
				return plugins_url(basename($absolutePath), $absolutePath);
			} else if ( strpos($absolutePath, $themeDir) === 0 ) {
				//It's part of a theme.
				$relativePath = substr($absolutePath, strlen($themeDir) + 1);
				$template = substr($relativePath, 0, strpos($relativePath, '/'));
				$baseUrl = get_theme_root_uri($template);

				if ( !empty($baseUrl) && $relativePath ) {
					return $baseUrl . '/' . $relativePath;
				}
			}

			return '';
		}
	}

endif;
