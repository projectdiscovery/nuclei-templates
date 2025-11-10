<?php
if ( !class_exists('Puc_v4p9_Plugin_UpdateChecker', false) ):

	/**
	 * A custom plugin update checker.
	 *
	 * @author Janis Elsts
	 * @copyright 2018
	 * @access public
	 */
	class Puc_v4p9_Plugin_UpdateChecker extends Puc_v4p9_UpdateChecker {
		protected $updateTransient = 'update_plugins';
		protected $translationType = 'plugin';

		public $pluginAbsolutePath = ''; //Full path of the main plugin file.
		public $pluginFile = '';  //Plugin filename relative to the plugins directory. Many WP APIs use this to identify plugins.
		public $muPluginFile = ''; //For MU plugins, the plugin filename relative to the mu-plugins directory.

		/**
		 * @var Puc_v4p9_Plugin_Package
		 */
		protected $package;

		private $extraUi = null;

		/**
		 * Class constructor.
		 *
		 * @param string $metadataUrl The URL of the plugin's metadata file.
		 * @param string $pluginFile Fully qualified path to the main plugin file.
		 * @param string $slug The plugin's 'slug'. If not specified, the filename part of $pluginFile sans '.php' will be used as the slug.
		 * @param integer $checkPeriod How often to check for updates (in hours). Defaults to checking every 12 hours. Set to 0 to disable automatic update checks.
		 * @param string $optionName Where to store book-keeping info about update checks. Defaults to 'external_updates-$slug'.
		 * @param string $muPluginFile Optional. The plugin filename relative to the mu-plugins directory.
		 */
		public function __construct($metadataUrl, $pluginFile, $slug = '', $checkPeriod = 12, $optionName = '', $muPluginFile = ''){
			$this->pluginAbsolutePath = $pluginFile;
			$this->pluginFile = plugin_basename($this->pluginAbsolutePath);
			$this->muPluginFile = $muPluginFile;

			//If no slug is specified, use the name of the main plugin file as the slug.
			//For example, 'my-cool-plugin/cool-plugin.php' becomes 'cool-plugin'.
			if ( empty($slug) ){
				$slug = basename($this->pluginFile, '.php');
			}

			//Plugin slugs must be unique.
			$slugCheckFilter = 'puc_is_slug_in_use-' . $slug;
			$slugUsedBy = apply_filters($slugCheckFilter, false);
			if ( $slugUsedBy ) {
				$this->triggerError(sprintf(
					'Plugin slug "%s" is already in use by %s. Slugs must be unique.',
					htmlentities($slug),
					htmlentities($slugUsedBy)
				), E_USER_ERROR);
			}
			add_filter($slugCheckFilter, array($this, 'getAbsolutePath'));

			parent::__construct($metadataUrl, dirname($this->pluginFile), $slug, $checkPeriod, $optionName);

			//Backwards compatibility: If the plugin is a mu-plugin but no $muPluginFile is specified, assume
			//it's the same as $pluginFile given that it's not in a subdirectory (WP only looks in the base dir).
			if ( (strpbrk($this->pluginFile, '/\\') === false) && $this->isUnknownMuPlugin() ) {
				$this->muPluginFile = $this->pluginFile;
			}

			//To prevent a crash during plugin uninstallation, remove updater hooks when the user removes the plugin.
			//Details: https://github.com/YahnisElsts/plugin-update-checker/issues/138#issuecomment-335590964
			add_action('uninstall_' . $this->pluginFile, array($this, 'removeHooks'));

			$this->extraUi = new Puc_v4p9_Plugin_Ui($this);
		}

		/**
		 * Create an instance of the scheduler.
		 *
		 * @param int $checkPeriod
		 * @return Puc_v4p9_Scheduler
		 */
		protected function createScheduler($checkPeriod) {
			$scheduler = new Puc_v4p9_Scheduler($this, $checkPeriod, array('load-plugins.php'));
			register_deactivation_hook($this->pluginFile, array($scheduler, 'removeUpdaterCron'));
			return $scheduler;
		}

		/**
		 * Install the hooks required to run periodic update checks and inject update info
		 * into WP data structures.
		 *
		 * @return void
		 */
		protected function installHooks(){
			//Override requests for plugin information
			add_filter('plugins_api', array($this, 'injectInfo'), 20, 3);

			parent::installHooks();
		}

		/**
		 * Remove update checker hooks.
		 *
		 * The intent is to prevent a fatal error that can happen if the plugin has an uninstall
		 * hook. During uninstallation, WP includes the main plugin file (which creates a PUC instance),
		 * the uninstall hook runs, WP deletes the plugin files and then updates some transients.
		 * If PUC hooks are still around at this time, they could throw an error while trying to
		 * autoload classes from files that no longer exist.
		 *
		 * The "site_transient_{$transient}" filter is the main problem here, but let's also remove
		 * most other PUC hooks to be safe.
		 *
		 * @internal
		 */
		public function removeHooks() {
			parent::removeHooks();
			$this->extraUi->removeHooks();
			$this->package->removeHooks();

			remove_filter('plugins_api', array($this, 'injectInfo'), 20);
		}

		/**
		 * Retrieve plugin info from the configured API endpoint.
		 *
		 * @uses wp_remote_get()
		 *
		 * @param array $queryArgs Additional query arguments to append to the request. Optional.
		 * @return Puc_v4p9_Plugin_Info
		 */
		public function requestInfo($queryArgs = array()) {
			list($pluginInfo, $result) = $this->requestMetadata('Puc_v4p9_Plugin_Info', 'request_info', $queryArgs);

			if ( $pluginInfo !== null ) {
				/** @var Puc_v4p9_Plugin_Info $pluginInfo */
				$pluginInfo->filename = $this->pluginFile;
				$pluginInfo->slug = $this->slug;
			}

			$pluginInfo = apply_filters($this->getUniqueName('request_info_result'), $pluginInfo, $result);
			return $pluginInfo;
		}

		/**
		 * Retrieve the latest update (if any) from the configured API endpoint.
		 *
		 * @uses PluginUpdateChecker::requestInfo()
		 *
		 * @return Puc_v4p9_Update|null An instance of Plugin_Update, or NULL when no updates are available.
		 */
		public function requestUpdate() {
			//For the sake of simplicity, this function just calls requestInfo()
			//and transforms the result accordingly.
			$pluginInfo = $this->requestInfo(array('checking_for_updates' => '1'));
			if ( $pluginInfo === null ){
				return null;
			}
			$update = Puc_v4p9_Plugin_Update::fromPluginInfo($pluginInfo);

			$update = $this->filterUpdateResult($update);

			return $update;
		}

		/**
		 * Intercept plugins_api() calls that request information about our plugin and
		 * use the configured API endpoint to satisfy them.
		 *
		 * @see plugins_api()
		 *
		 * @param mixed $result
		 * @param string $action
		 * @param array|object $args
		 * @return mixed
		 */
		public function injectInfo($result, $action = null, $args = null){
			$relevant = ($action == 'plugin_information') && isset($args->slug) && (
					($args->slug == $this->slug) || ($args->slug == dirname($this->pluginFile))
				);
			if ( !$relevant ) {
				return $result;
			}

			$pluginInfo = $this->requestInfo();
			$this->fixSupportedWordpressVersion($pluginInfo);

			$pluginInfo = apply_filters($this->getUniqueName('pre_inject_info'), $pluginInfo);
			if ( $pluginInfo ) {
				return $pluginInfo->toWpFormat();
			}

			return $result;
		}

		protected function shouldShowUpdates() {
			//No update notifications for mu-plugins unless explicitly enabled. The MU plugin file
			//is usually different from the main plugin file so the update wouldn't show up properly anyway.
			return !$this->isUnknownMuPlugin();
		}

		/**
		 * @param stdClass|null $updates
		 * @param stdClass $updateToAdd
		 * @return stdClass
		 */
		protected function addUpdateToList($updates, $updateToAdd) {
			if ( $this->package->isMuPlugin() ) {
				//WP does not support automatic update installation for mu-plugins, but we can
				//still display a notice.
				$updateToAdd->package = null;
			}
			return parent::addUpdateToList($updates, $updateToAdd);
		}

		/**
		 * @param stdClass|null $updates
		 * @return stdClass|null
		 */
		protected function removeUpdateFromList($updates) {
			$updates = parent::removeUpdateFromList($updates);
			if ( !empty($this->muPluginFile) && isset($updates, $updates->response) ) {
				unset($updates->response[$this->muPluginFile]);
			}
			return $updates;
		}

		/**
		 * For plugins, the update array is indexed by the plugin filename relative to the "plugins"
		 * directory. Example: "plugin-name/plugin.php".
		 *
		 * @return string
		 */
		protected function getUpdateListKey() {
			if ( $this->package->isMuPlugin() ) {
				return $this->muPluginFile;
			}
			return $this->pluginFile;
		}

		/**
		 * Alias for isBeingUpgraded().
		 *
		 * @deprecated
		 * @param WP_Upgrader|null $upgrader The upgrader that's performing the current update.
		 * @return bool
		 */
		public function isPluginBeingUpgraded($upgrader = null) {
			return $this->isBeingUpgraded($upgrader);
		}

		/**
		 * Is there an update being installed for this plugin, right now?
		 *
		 * @param WP_Upgrader|null $upgrader
		 * @return bool
		 */
		public function isBeingUpgraded($upgrader = null) {
			return $this->upgraderStatus->isPluginBeingUpgraded($this->pluginFile, $upgrader);
		}

		/**
		 * Get the details of the currently available update, if any.
		 *
		 * If no updates are available, or if the last known update version is below or equal
		 * to the currently installed version, this method will return NULL.
		 *
		 * Uses cached update data. To retrieve update information straight from
		 * the metadata URL, call requestUpdate() instead.
		 *
		 * @return Puc_v4p9_Plugin_Update|null
		 */
		public function getUpdate() {
			$update = parent::getUpdate();
			if ( isset($update) ) {
				/** @var Puc_v4p9_Plugin_Update $update */
				$update->filename = $this->pluginFile;
			}
			return $update;
		}

		/**
		 * Get the translated plugin title.
		 *
		 * @deprecated
		 * @return string
		 */
		public function getPluginTitle() {
			return $this->package->getPluginTitle();
		}

		/**
		 * Check if the current user has the required permissions to install updates.
		 *
		 * @return bool
		 */
		public function userCanInstallUpdates() {
			return current_user_can('update_plugins');
		}

		/**
		 * Check if the plugin file is inside the mu-plugins directory.
		 *
		 * @deprecated
		 * @return bool
		 */
		protected function isMuPlugin() {
			return $this->package->isMuPlugin();
		}

		/**
		 * MU plugins are partially supported, but only when we know which file in mu-plugins
		 * corresponds to this plugin.
		 *
		 * @return bool
		 */
		protected function isUnknownMuPlugin() {
			return empty($this->muPluginFile) && $this->package->isMuPlugin();
		}

		/**
		 * Get absolute path to the main plugin file.
		 *
		 * @return string
		 */
		public function getAbsolutePath() {
			return $this->pluginAbsolutePath;
		}

		/**
		 * Register a callback for filtering query arguments.
		 *
		 * The callback function should take one argument - an associative array of query arguments.
		 * It should return a modified array of query arguments.
		 *
		 * @uses add_filter() This method is a convenience wrapper for add_filter().
		 *
		 * @param callable $callback
		 * @return void
		 */
		public function addQueryArgFilter($callback){
			$this->addFilter('request_info_query_args', $callback);
		}

		/**
		 * Register a callback for filtering arguments passed to wp_remote_get().
		 *
		 * The callback function should take one argument - an associative array of arguments -
		 * and return a modified array or arguments. See the WP documentation on wp_remote_get()
		 * for details on what arguments are available and how they work.
		 *
		 * @uses add_filter() This method is a convenience wrapper for add_filter().
		 *
		 * @param callable $callback
		 * @return void
		 */
		public function addHttpRequestArgFilter($callback) {
			$this->addFilter('request_info_options', $callback);
		}

		/**
		 * Register a callback for filtering the plugin info retrieved from the external API.
		 *
		 * The callback function should take two arguments. If the plugin info was retrieved
		 * successfully, the first argument passed will be an instance of  PluginInfo. Otherwise,
		 * it will be NULL. The second argument will be the corresponding return value of
		 * wp_remote_get (see WP docs for details).
		 *
		 * The callback function should return a new or modified instance of PluginInfo or NULL.
		 *
		 * @uses add_filter() This method is a convenience wrapper for add_filter().
		 *
		 * @param callable $callback
		 * @return void
		 */
		public function addResultFilter($callback) {
			$this->addFilter('request_info_result', $callback, 10, 2);
		}

		protected function createDebugBarExtension() {
			return new Puc_v4p9_DebugBar_PluginExtension($this);
		}

		/**
		 * Create a package instance that represents this plugin or theme.
		 *
		 * @return Puc_v4p9_InstalledPackage
		 */
		protected function createInstalledPackage() {
			return new Puc_v4p9_Plugin_Package($this->pluginAbsolutePath, $this);
		}

		/**
		 * @return Puc_v4p9_Plugin_Package
		 */
		public function getInstalledPackage() {
			return $this->package;
		}
	}

endif;
