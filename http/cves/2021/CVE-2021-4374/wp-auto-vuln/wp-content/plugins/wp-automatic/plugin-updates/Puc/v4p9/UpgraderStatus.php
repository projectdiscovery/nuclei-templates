<?php
if ( !class_exists('Puc_v4p9_UpgraderStatus', false) ):

	/**
	 * A utility class that helps figure out which plugin or theme WordPress is upgrading.
	 *
	 * It may seem strange to have a separate class just for that, but the task is surprisingly complicated.
	 * Core classes like Plugin_Upgrader don't expose the plugin file name during an in-progress update (AFAICT).
	 * This class uses a few workarounds and heuristics to get the file name.
	 */
	class Puc_v4p9_UpgraderStatus {
		private $currentType = null; //"plugin" or "theme".
		private $currentId = null;   //Plugin basename or theme directory name.

		public function __construct() {
			//Keep track of which plugin/theme WordPress is currently upgrading.
			add_filter('upgrader_pre_install', array($this, 'setUpgradedThing'), 10, 2);
			add_filter('upgrader_package_options', array($this, 'setUpgradedPluginFromOptions'), 10, 1);
			add_filter('upgrader_post_install', array($this, 'clearUpgradedThing'), 10, 1);
			add_action('upgrader_process_complete', array($this, 'clearUpgradedThing'), 10, 1);
		}

		/**
		 * Is there and update being installed RIGHT NOW, for a specific plugin?
		 *
		 * Caution: This method is unreliable. WordPress doesn't make it easy to figure out what it is upgrading,
		 * and upgrader implementations are liable to change without notice.
		 *
		 * @param string $pluginFile The plugin to check.
		 * @param WP_Upgrader|null $upgrader The upgrader that's performing the current update.
		 * @return bool True if the plugin identified by $pluginFile is being upgraded.
		 */
		public function isPluginBeingUpgraded($pluginFile, $upgrader = null) {
			return $this->isBeingUpgraded('plugin', $pluginFile, $upgrader);
		}

		/**
		 * Is there an update being installed for a specific theme?
		 *
		 * @param string $stylesheet Theme directory name.
		 * @param WP_Upgrader|null $upgrader The upgrader that's performing the current update.
		 * @return bool
		 */
		public function isThemeBeingUpgraded($stylesheet, $upgrader = null) {
			return $this->isBeingUpgraded('theme', $stylesheet, $upgrader);
		}

		/**
		 * Check if a specific theme or plugin is being upgraded.
		 *
		 * @param string $type
		 * @param string $id
		 * @param Plugin_Upgrader|WP_Upgrader|null $upgrader
		 * @return bool
		 */
		protected function isBeingUpgraded($type, $id, $upgrader = null) {
			if ( isset($upgrader) ) {
				list($currentType, $currentId) = $this->getThingBeingUpgradedBy($upgrader);
				if ( $currentType !== null ) {
					$this->currentType = $currentType;
					$this->currentId = $currentId;
				}
			}
			return ($this->currentType === $type) && ($this->currentId === $id);
		}

		/**
		 * Figure out which theme or plugin is being upgraded by a WP_Upgrader instance.
		 *
		 * Returns an array with two items. The first item is the type of the thing that's being
		 * upgraded: "plugin" or "theme". The second item is either the plugin basename or
		 * the theme directory name. If we can't determine what the upgrader is doing, both items
		 * will be NULL.
		 *
		 * Examples:
		 *      ['plugin', 'plugin-dir-name/plugin.php']
		 *      ['theme', 'theme-dir-name']
		 *
		 * @param Plugin_Upgrader|WP_Upgrader $upgrader
		 * @return array
		 */
		private function getThingBeingUpgradedBy($upgrader) {
			if ( !isset($upgrader, $upgrader->skin) ) {
				return array(null, null);
			}

			//Figure out which plugin or theme is being upgraded.
			$pluginFile = null;
			$themeDirectoryName = null;

			$skin = $upgrader->skin;
			if ( isset($skin->theme_info) && ($skin->theme_info instanceof WP_Theme) ) {
				$themeDirectoryName = $skin->theme_info->get_stylesheet();
			} elseif ( $skin instanceof Plugin_Upgrader_Skin ) {
				if ( isset($skin->plugin) && is_string($skin->plugin) && ($skin->plugin !== '') ) {
					$pluginFile = $skin->plugin;
				}
			} elseif ( $skin instanceof Theme_Upgrader_Skin ) {
				if ( isset($skin->theme) && is_string($skin->theme) && ($skin->theme !== '') ) {
					$themeDirectoryName = $skin->theme;
				}
			} elseif ( isset($skin->plugin_info) && is_array($skin->plugin_info) ) {
				//This case is tricky because Bulk_Plugin_Upgrader_Skin (etc) doesn't actually store the plugin
				//filename anywhere. Instead, it has the plugin headers in $plugin_info. So the best we can
				//do is compare those headers to the headers of installed plugins.
				$pluginFile = $this->identifyPluginByHeaders($skin->plugin_info);
			}

			if ( $pluginFile !== null ) {
				return array('plugin', $pluginFile);
			} elseif ( $themeDirectoryName !== null ) {
				return array('theme', $themeDirectoryName);
			}
			return array(null, null);
		}

		/**
		 * Identify an installed plugin based on its headers.
		 *
		 * @param array $searchHeaders The plugin file header to look for.
		 * @return string|null Plugin basename ("foo/bar.php"), or NULL if we can't identify the plugin.
		 */
		private function identifyPluginByHeaders($searchHeaders) {
			if ( !function_exists('get_plugins') ){
				/** @noinspection PhpIncludeInspection */
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}

			$installedPlugins = get_plugins();
			$matches = array();
			foreach($installedPlugins as $pluginBasename => $headers) {
				$diff1 = array_diff_assoc($headers, $searchHeaders);
				$diff2 = array_diff_assoc($searchHeaders, $headers);
				if ( empty($diff1) && empty($diff2) ) {
					$matches[] = $pluginBasename;
				}
			}

			//It's possible (though very unlikely) that there could be two plugins with identical
			//headers. In that case, we can't unambiguously identify the plugin that's being upgraded.
			if ( count($matches) !== 1 ) {
				return null;
			}

			return reset($matches);
		}

		/**
		 * @access private
		 *
		 * @param mixed $input
		 * @param array $hookExtra
		 * @return mixed Returns $input unaltered.
		 */
		public function setUpgradedThing($input, $hookExtra) {
			if ( !empty($hookExtra['plugin']) && is_string($hookExtra['plugin']) ) {
				$this->currentId = $hookExtra['plugin'];
				$this->currentType = 'plugin';
			} elseif ( !empty($hookExtra['theme']) && is_string($hookExtra['theme']) ) {
				$this->currentId = $hookExtra['theme'];
				$this->currentType = 'theme';
			} else {
				$this->currentType = null;
				$this->currentId = null;
			}
			return $input;
		}

		/**
		 * @access private
		 *
		 * @param array $options
		 * @return array
		 */
		public function setUpgradedPluginFromOptions($options) {
			if ( isset($options['hook_extra']['plugin']) && is_string($options['hook_extra']['plugin']) ) {
				$this->currentType = 'plugin';
				$this->currentId = $options['hook_extra']['plugin'];
			} else {
				$this->currentType = null;
				$this->currentId = null;
			}
			return $options;
		}

		/**
		 * @access private
		 *
		 * @param mixed $input
		 * @return mixed Returns $input unaltered.
		 */
		public function clearUpgradedThing($input = null) {
			$this->currentId = null;
			$this->currentType = null;
			return $input;
		}
	}

endif;
