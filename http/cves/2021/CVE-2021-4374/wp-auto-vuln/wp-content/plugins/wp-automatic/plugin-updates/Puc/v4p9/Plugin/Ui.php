<?php
if ( !class_exists('Puc_v4p9_Plugin_Ui', false) ):
	/**
	 * Additional UI elements for plugins.
	 */
	class Puc_v4p9_Plugin_Ui {
		private $updateChecker;
		private $manualCheckErrorTransient = '';

		/**
		 * @param Puc_v4p9_Plugin_UpdateChecker $updateChecker
		 */
		public function __construct($updateChecker) {
			$this->updateChecker = $updateChecker;
			$this->manualCheckErrorTransient = $this->updateChecker->getUniqueName('manual_check_errors');

			add_action('admin_init', array($this, 'onAdminInit'));
		}

		public function onAdminInit() {
			if ( $this->updateChecker->userCanInstallUpdates() ) {
				$this->handleManualCheck();

				add_filter('plugin_row_meta', array($this, 'addViewDetailsLink'), 10, 3);
				add_filter('plugin_row_meta', array($this, 'addCheckForUpdatesLink'), 10, 2);
				add_action('all_admin_notices', array($this, 'displayManualCheckResult'));
			}
		}

		/**
		 * Add a "View Details" link to the plugin row in the "Plugins" page. By default,
		 * the new link will appear before the "Visit plugin site" link (if present).
		 *
		 * You can change the link text by using the "puc_view_details_link-$slug" filter.
		 * Returning an empty string from the filter will disable the link.
		 *
		 * You can change the position of the link using the
		 * "puc_view_details_link_position-$slug" filter.
		 * Returning 'before' or 'after' will place the link immediately before/after
		 * the "Visit plugin site" link.
		 * Returning 'append' places the link after any existing links at the time of the hook.
		 * Returning 'replace' replaces the "Visit plugin site" link.
		 * Returning anything else disables the link when there is a "Visit plugin site" link.
		 *
		 * If there is no "Visit plugin site" link 'append' is always used!
		 *
		 * @param array $pluginMeta Array of meta links.
		 * @param string $pluginFile
		 * @param array $pluginData Array of plugin header data.
		 * @return array
		 */
		public function addViewDetailsLink($pluginMeta, $pluginFile, $pluginData = array()) {
			if ( $this->isMyPluginFile($pluginFile) && !isset($pluginData['slug']) ) {
				$linkText = apply_filters($this->updateChecker->getUniqueName('view_details_link'), __('View details'));
				if ( !empty($linkText) ) {
					$viewDetailsLinkPosition = 'append';

					//Find the "Visit plugin site" link (if present).
					$visitPluginSiteLinkIndex = count($pluginMeta) - 1;
					if ( $pluginData['PluginURI'] ) {
						$escapedPluginUri = esc_url($pluginData['PluginURI']);
						foreach ($pluginMeta as $linkIndex => $existingLink) {
							if ( strpos($existingLink, $escapedPluginUri) !== false ) {
								$visitPluginSiteLinkIndex = $linkIndex;
								$viewDetailsLinkPosition = apply_filters(
									$this->updateChecker->getUniqueName('view_details_link_position'),
									'before'
								);
								break;
							}
						}
					}

					$viewDetailsLink = sprintf('<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s" data-title="%s">%s</a>',
						esc_url(network_admin_url('plugin-install.php?tab=plugin-information&plugin=' . urlencode($this->updateChecker->slug) .
							'&TB_iframe=true&width=600&height=550')),
						esc_attr(sprintf(__('More information about %s'), $pluginData['Name'])),
						esc_attr($pluginData['Name']),
						$linkText
					);
					switch ($viewDetailsLinkPosition) {
						case 'before':
							array_splice($pluginMeta, $visitPluginSiteLinkIndex, 0, $viewDetailsLink);
							break;
						case 'after':
							array_splice($pluginMeta, $visitPluginSiteLinkIndex + 1, 0, $viewDetailsLink);
							break;
						case 'replace':
							$pluginMeta[$visitPluginSiteLinkIndex] = $viewDetailsLink;
							break;
						case 'append':
						default:
							$pluginMeta[] = $viewDetailsLink;
							break;
					}
				}
			}
			return $pluginMeta;
		}

		/**
		 * Add a "Check for updates" link to the plugin row in the "Plugins" page. By default,
		 * the new link will appear after the "Visit plugin site" link if present, otherwise
		 * after the "View plugin details" link.
		 *
		 * You can change the link text by using the "puc_manual_check_link-$slug" filter.
		 * Returning an empty string from the filter will disable the link.
		 *
		 * @param array $pluginMeta Array of meta links.
		 * @param string $pluginFile
		 * @return array
		 */
		public function addCheckForUpdatesLink($pluginMeta, $pluginFile) {
			if ( $this->isMyPluginFile($pluginFile) ) {
				$linkUrl = wp_nonce_url(
					add_query_arg(
						array(
							'puc_check_for_updates' => 1,
							'puc_slug'              => $this->updateChecker->slug,
						),
						self_admin_url('plugins.php')
					),
					'puc_check_for_updates'
				);

				$linkText = apply_filters(
					$this->updateChecker->getUniqueName('manual_check_link'),
					__('Check for updates', 'plugin-update-checker')
				);
				if ( !empty($linkText) ) {
					/** @noinspection HtmlUnknownTarget */
					$pluginMeta[] = sprintf('<a href="%s">%s</a>', esc_attr($linkUrl), $linkText);
				}
			}
			return $pluginMeta;
		}

		protected function isMyPluginFile($pluginFile) {
			return ($pluginFile == $this->updateChecker->pluginFile)
				|| (!empty($this->updateChecker->muPluginFile) && ($pluginFile == $this->updateChecker->muPluginFile));
		}

		/**
		 * Check for updates when the user clicks the "Check for updates" link.
		 *
		 * @see self::addCheckForUpdatesLink()
		 *
		 * @return void
		 */
		public function handleManualCheck() {
			$shouldCheck =
				isset($_GET['puc_check_for_updates'], $_GET['puc_slug'])
				&& $_GET['puc_slug'] == $this->updateChecker->slug
				&& check_admin_referer('puc_check_for_updates');

			if ( $shouldCheck ) {
				$update = $this->updateChecker->checkForUpdates();
				$status = ($update === null) ? 'no_update' : 'update_available';

				if ( ($update === null) && !empty($this->lastRequestApiErrors) ) {
					//Some errors are not critical. For example, if PUC tries to retrieve the readme.txt
					//file from GitHub and gets a 404, that's an API error, but it doesn't prevent updates
					//from working. Maybe the plugin simply doesn't have a readme.
					//Let's only show important errors.
					$foundCriticalErrors = false;
					$questionableErrorCodes = array(
						'puc-github-http-error',
						'puc-gitlab-http-error',
						'puc-bitbucket-http-error',
					);

					foreach ($this->lastRequestApiErrors as $item) {
						$wpError = $item['error'];
						/** @var WP_Error $wpError */
						if ( !in_array($wpError->get_error_code(), $questionableErrorCodes) ) {
							$foundCriticalErrors = true;
							break;
						}
					}

					if ( $foundCriticalErrors ) {
						$status = 'error';
						set_site_transient($this->manualCheckErrorTransient, $this->lastRequestApiErrors, 60);
					}
				}

				wp_redirect(add_query_arg(
					array(
						'puc_update_check_result' => $status,
						'puc_slug'                => $this->updateChecker->slug,
					),
					self_admin_url('plugins.php')
				));
				exit;
			}
		}

		/**
		 * Display the results of a manual update check.
		 *
		 * @see self::handleManualCheck()
		 *
		 * You can change the result message by using the "puc_manual_check_message-$slug" filter.
		 */
		public function displayManualCheckResult() {
			if ( isset($_GET['puc_update_check_result'], $_GET['puc_slug']) && ($_GET['puc_slug'] == $this->updateChecker->slug) ) {
				$status = strval($_GET['puc_update_check_result']);
				$title = $this->updateChecker->getInstalledPackage()->getPluginTitle();
				$noticeClass = 'updated notice-success';
				$details = '';

				if ( $status == 'no_update' ) {
					$message = sprintf(_x('The %s plugin is up to date.', 'the plugin title', 'plugin-update-checker'), $title);
				} else if ( $status == 'update_available' ) {
					$message = sprintf(_x('A new version of the %s plugin is available.', 'the plugin title', 'plugin-update-checker'), $title);
				} else if ( $status === 'error' ) {
					$message = sprintf(_x('Could not determine if updates are available for %s.', 'the plugin title', 'plugin-update-checker'), $title);
					$noticeClass = 'error notice-error';

					$details = $this->formatManualCheckErrors(get_site_transient($this->manualCheckErrorTransient));
					delete_site_transient($this->manualCheckErrorTransient);
				} else {
					$message = sprintf(__('Unknown update checker status "%s"', 'plugin-update-checker'), htmlentities($status));
					$noticeClass = 'error notice-error';
				}
				printf(
					'<div class="notice %s is-dismissible"><p>%s</p>%s</div>',
					$noticeClass,
					apply_filters($this->updateChecker->getUniqueName('manual_check_message'), $message, $status),
					$details
				);
			}
		}

		/**
		 * Format the list of errors that were thrown during an update check.
		 *
		 * @param array $errors
		 * @return string
		 */
		protected function formatManualCheckErrors($errors) {
			if ( empty($errors) ) {
				return '';
			}
			$output = '';

			$showAsList = count($errors) > 1;
			if ( $showAsList ) {
				$output .= '<ol>';
				$formatString = '<li>%1$s <code>%2$s</code></li>';
			} else {
				$formatString = '<p>%1$s <code>%2$s</code></p>';
			}
			foreach ($errors as $item) {
				$wpError = $item['error'];
				/** @var WP_Error $wpError */
				$output .= sprintf(
					$formatString,
					$wpError->get_error_message(),
					$wpError->get_error_code()
				);
			}
			if ( $showAsList ) {
				$output .= '</ol>';
			}

			return $output;
		}

		public function removeHooks() {
			remove_action('admin_init', array($this, 'onAdminInit'));
			remove_filter('plugin_row_meta', array($this, 'addViewDetailsLink'), 10);
			remove_filter('plugin_row_meta', array($this, 'addCheckForUpdatesLink'), 10);
			remove_action('all_admin_notices', array($this, 'displayManualCheckResult'));
		}
	}
endif;
