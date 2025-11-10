<?php

if ( !class_exists('Puc_v4p9_UpdateChecker', false) ):

	abstract class Puc_v4p9_UpdateChecker {
		protected $filterSuffix = '';
		protected $updateTransient = '';
		protected $translationType = ''; //"plugin" or "theme".

		/**
		 * Set to TRUE to enable error reporting. Errors are raised using trigger_error()
		 * and should be logged to the standard PHP error log.
		 * @var bool
		 */
		public $debugMode = null;

		/**
		 * @var string Where to store the update info.
		 */
		public $optionName = '';

		/**
		 * @var string The URL of the metadata file.
		 */
		public $metadataUrl = '';

		/**
		 * @var string Plugin or theme directory name.
		 */
		public $directoryName = '';

		/**
		 * @var string The slug that will be used in update checker hooks and remote API requests.
		 * Usually matches the directory name unless the plugin/theme directory has been renamed.
		 */
		public $slug = '';

		/**
		 * @var Puc_v4p9_InstalledPackage
		 */
		protected $package;

		/**
		 * @var Puc_v4p9_Scheduler
		 */
		public $scheduler;

		/**
		 * @var Puc_v4p9_UpgraderStatus
		 */
		protected $upgraderStatus;

		/**
		 * @var Puc_v4p9_StateStore
		 */
		protected $updateState;

		/**
		 * @var array List of API errors triggered during the last checkForUpdates() call.
		 */
		protected $lastRequestApiErrors = array();

		/**
		 * @var string|mixed The default is 0 because parse_url() can return NULL or FALSE.
		 */
		protected $cachedMetadataHost = 0;

		public function __construct($metadataUrl, $directoryName, $slug = null, $checkPeriod = 12, $optionName = '') {
			$this->debugMode = (bool)(constant('WP_DEBUG'));
			$this->metadataUrl = $metadataUrl;
			$this->directoryName = $directoryName;
			$this->slug = !empty($slug) ? $slug : $this->directoryName;

			$this->optionName = $optionName;
			if ( empty($this->optionName) ) {
				//BC: Initially the library only supported plugin updates and didn't use type prefixes
				//in the option name. Lets use the same prefix-less name when possible.
				if ( $this->filterSuffix === '' ) {
					$this->optionName = 'external_updates-' . $this->slug;
				} else {
					$this->optionName = $this->getUniqueName('external_updates');
				}
			}

			$this->package = $this->createInstalledPackage();
			$this->scheduler = $this->createScheduler($checkPeriod);
			$this->upgraderStatus = new Puc_v4p9_UpgraderStatus();
			$this->updateState = new Puc_v4p9_StateStore($this->optionName);

			if ( did_action('init') ) {
				$this->loadTextDomain();
			} else {
				add_action('init', array($this, 'loadTextDomain'));
			}

			$this->installHooks();
		}

		/**
		 * @internal
		 */
		public function loadTextDomain() {
			//We're not using load_plugin_textdomain() or its siblings because figuring out where
			//the library is located (plugin, mu-plugin, theme, custom wp-content paths) is messy.
			$domain = 'plugin-update-checker';
			$locale = apply_filters(
				'plugin_locale',
				(is_admin() && function_exists('get_user_locale')) ? get_user_locale() : get_locale(),
				$domain
			);

			$moFile = $domain . '-' . $locale . '.mo';
			$path = realpath(dirname(__FILE__) . '/../../languages');

			if ($path && file_exists($path)) {
				load_textdomain($domain, $path . '/' . $moFile);
			}
		}

		protected function installHooks() {
			//Insert our update info into the update array maintained by WP.
			add_filter('site_transient_' . $this->updateTransient, array($this,'injectUpdate'));

			//Insert translation updates into the update list.
			add_filter('site_transient_' . $this->updateTransient, array($this, 'injectTranslationUpdates'));

			//Clear translation updates when WP clears the update cache.
			//This needs to be done directly because the library doesn't actually remove obsolete plugin updates,
			//it just hides them (see getUpdate()). We can't do that with translations - too much disk I/O.
			add_action(
				'delete_site_transient_' . $this->updateTransient,
				array($this, 'clearCachedTranslationUpdates')
			);

			//Rename the update directory to be the same as the existing directory.
			if ( $this->directoryName !== '.' ) {
				add_filter('upgrader_source_selection', array($this, 'fixDirectoryName'), 10, 3);
			}

			//Allow HTTP requests to the metadata URL even if it's on a local host.
			add_filter('http_request_host_is_external', array($this, 'allowMetadataHost'), 10, 2);

			//DebugBar integration.
			if ( did_action('plugins_loaded') ) {
				$this->maybeInitDebugBar();
			} else {
				add_action('plugins_loaded', array($this, 'maybeInitDebugBar'));
			}
		}

		/**
		 * Remove hooks that were added by this update checker instance.
		 */
		public function removeHooks() {
			remove_filter('site_transient_' . $this->updateTransient, array($this,'injectUpdate'));
			remove_filter('site_transient_' . $this->updateTransient, array($this, 'injectTranslationUpdates'));
			remove_action(
				'delete_site_transient_' . $this->updateTransient,
				array($this, 'clearCachedTranslationUpdates')
			);

			remove_filter('upgrader_source_selection', array($this, 'fixDirectoryName'), 10);
			remove_filter('http_request_host_is_external', array($this, 'allowMetadataHost'), 10);
			remove_action('plugins_loaded', array($this, 'maybeInitDebugBar'));

			remove_action('init', array($this, 'loadTextDomain'));

			if ( $this->scheduler ) {
				$this->scheduler->removeHooks();
			}
		}

		/**
		 * Check if the current user has the required permissions to install updates.
		 *
		 * @return bool
		 */
		abstract public function userCanInstallUpdates();

		/**
		 * Explicitly allow HTTP requests to the metadata URL.
		 *
		 * WordPress has a security feature where the HTTP API will reject all requests that are sent to
		 * another site hosted on the same server as the current site (IP match), a local host, or a local
		 * IP, unless the host exactly matches the current site.
		 *
		 * This feature is opt-in (at least in WP 4.4). Apparently some people enable it.
		 *
		 * That can be a problem when you're developing your plugin and you decide to host the update information
		 * on the same server as your test site. Update requests will mysteriously fail.
		 *
		 * We fix that by adding an exception for the metadata host.
		 *
		 * @param bool $allow
		 * @param string $host
		 * @return bool
		 */
		public function allowMetadataHost($allow, $host) {
			if ( $this->cachedMetadataHost === 0 ) {
				$this->cachedMetadataHost = parse_url($this->metadataUrl, PHP_URL_HOST);
			}

			if ( is_string($this->cachedMetadataHost) && (strtolower($host) === strtolower($this->cachedMetadataHost)) ) {
				return true;
			}
			return $allow;
		}

		/**
		 * Create a package instance that represents this plugin or theme.
		 *
		 * @return Puc_v4p9_InstalledPackage
		 */
		abstract protected function createInstalledPackage();

		/**
		 * @return Puc_v4p9_InstalledPackage
		 */
		public function getInstalledPackage() {
			return $this->package;
		}

		/**
		 * Create an instance of the scheduler.
		 *
		 * This is implemented as a method to make it possible for plugins to subclass the update checker
		 * and substitute their own scheduler.
		 *
		 * @param int $checkPeriod
		 * @return Puc_v4p9_Scheduler
		 */
		abstract protected function createScheduler($checkPeriod);

		/**
		 * Check for updates. The results are stored in the DB option specified in $optionName.
		 *
		 * @return Puc_v4p9_Update|null
		 */
		public function checkForUpdates() {
			$installedVersion = $this->getInstalledVersion();
			//Fail silently if we can't find the plugin/theme or read its header.
			if ( $installedVersion === null ) {
				$this->triggerError(
					sprintf('Skipping update check for %s - installed version unknown.', $this->slug),
					E_USER_WARNING
				);
				return null;
			}

			//Start collecting API errors.
			$this->lastRequestApiErrors = array();
			add_action('puc_api_error', array($this, 'collectApiErrors'), 10, 4);

			$state = $this->updateState;
			$state->setLastCheckToNow()
				->setCheckedVersion($installedVersion)
				->save(); //Save before checking in case something goes wrong

			$state->setUpdate($this->requestUpdate());
			$state->save();

			//Stop collecting API errors.
			remove_action('puc_api_error', array($this, 'collectApiErrors'), 10);

			return $this->getUpdate();
		}

		/**
		 * Load the update checker state from the DB.
		 *
		 * @return Puc_v4p9_StateStore
		 */
		public function getUpdateState() {
			return $this->updateState->lazyLoad();
		}

		/**
		 * Reset update checker state - i.e. last check time, cached update data and so on.
		 *
		 * Call this when your plugin is being uninstalled, or if you want to
		 * clear the update cache.
		 */
		public function resetUpdateState() {
			$this->updateState->delete();
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
		 * @return Puc_v4p9_Update|null
		 */
		public function getUpdate() {
			$update = $this->updateState->getUpdate();

			//Is there an update available?
			if ( isset($update) ) {
				//Check if the update is actually newer than the currently installed version.
				$installedVersion = $this->getInstalledVersion();
				if ( ($installedVersion !== null) && version_compare($update->version, $installedVersion, '>') ){
					return $update;
				}
			}
			return null;
		}

		/**
		 * Retrieve the latest update (if any) from the configured API endpoint.
		 *
		 * Subclasses should run the update through filterUpdateResult before returning it.
		 *
		 * @return Puc_v4p9_Update An instance of Update, or NULL when no updates are available.
		 */
		abstract public function requestUpdate();

		/**
		 * Filter the result of a requestUpdate() call.
		 *
		 * @param Puc_v4p9_Update|null $update
		 * @param array|WP_Error|null $httpResult The value returned by wp_remote_get(), if any.
		 * @return Puc_v4p9_Update
		 */
		protected function filterUpdateResult($update, $httpResult = null) {
			//Let plugins/themes modify the update.
			$update = apply_filters($this->getUniqueName('request_update_result'), $update, $httpResult);

			$this->fixSupportedWordpressVersion($update);

			if ( isset($update, $update->translations) ) {
				//Keep only those translation updates that apply to this site.
				$update->translations = $this->filterApplicableTranslations($update->translations);
			}

			return $update;
		}

		/**
		 * The "Tested up to" field in the plugin metadata is supposed to be in the form of "major.minor",
		 * while WordPress core's list_plugin_updates() expects the $update->tested field to be an exact
		 * version, e.g. "major.minor.patch", to say it's compatible. In other case it shows
		 * "Compatibility: Unknown".
		 * The function mimics how wordpress.org API crafts the "tested" field out of "Tested up to".
		 *
		 * @param Puc_v4p9_Metadata|null $update
		 */
		protected function fixSupportedWordpressVersion(Puc_v4p9_Metadata $update = null) {
			if ( !isset($update->tested) || !preg_match('/^\d++\.\d++$/', $update->tested) ) {
				return;
			}

			$actualWpVersions = array();

			$wpVersion = $GLOBALS['wp_version'];

			if ( function_exists('get_preferred_from_update_core') ) {
				$coreUpdate = get_preferred_from_update_core();
				if ( isset($coreUpdate->current) && version_compare($coreUpdate->current, $wpVersion, '>') ) {
					$actualWpVersions[] = $coreUpdate->current;
				}
			}

			$actualWpVersions[] = $wpVersion;

			$actualWpPatchNumber = "999";
			foreach ($actualWpVersions as $version) {
				if ( preg_match('/^(?P<majorMinor>\d++\.\d++)\.(?P<patch>\d++)/', $version, $versionParts) ) {
					if ( $versionParts['majorMinor'] === $update->tested ) {
						$actualWpPatchNumber = $versionParts['patch'];
						break;
					}
				}
			}

			$update->tested .= '.' . $actualWpPatchNumber;
		}

		/**
		 * Get the currently installed version of the plugin or theme.
		 *
		 * @return string|null Version number.
		 */
		public function getInstalledVersion() {
			return $this->package->getInstalledVersion();
		}

		/**
		 * Get the full path of the plugin or theme directory.
		 *
		 * @return string
		 */
		public function getAbsoluteDirectoryPath() {
			return $this->package->getAbsoluteDirectoryPath();
		}

		/**
		 * Trigger a PHP error, but only when $debugMode is enabled.
		 *
		 * @param string $message
		 * @param int $errorType
		 */
		public function triggerError($message, $errorType) {
			if ( $this->isDebugModeEnabled() ) {
				trigger_error($message, $errorType);
			}
		}

		/**
		 * @return bool
		 */
		protected function isDebugModeEnabled() {
			if ( $this->debugMode === null ) {
				$this->debugMode = (bool)(constant('WP_DEBUG'));
			}
			return $this->debugMode;
		}

		/**
		 * Get the full name of an update checker filter, action or DB entry.
		 *
		 * This method adds the "puc_" prefix and the "-$slug" suffix to the filter name.
		 * For example, "pre_inject_update" becomes "puc_pre_inject_update-plugin-slug".
		 *
		 * @param string $baseTag
		 * @return string
		 */
		public function getUniqueName($baseTag) {
			$name = 'puc_' . $baseTag;
			if ( $this->filterSuffix !== '' ) {
				$name .= '_' . $this->filterSuffix;
			}
			return $name . '-' . $this->slug;
		}

		/**
		 * Store API errors that are generated when checking for updates.
		 *
		 * @internal
		 * @param WP_Error $error
		 * @param array|null $httpResponse
		 * @param string|null $url
		 * @param string|null $slug
		 */
		public function collectApiErrors($error, $httpResponse = null, $url = null, $slug = null) {
			if ( isset($slug) && ($slug !== $this->slug) ) {
				return;
			}

			$this->lastRequestApiErrors[] = array(
				'error'        => $error,
				'httpResponse' => $httpResponse,
				'url'          => $url,
			);
		}

		/**
		 * @return array
		 */
		public function getLastRequestApiErrors() {
			return $this->lastRequestApiErrors;
		}

		/* -------------------------------------------------------------------
		 * PUC filters and filter utilities
		 * -------------------------------------------------------------------
		 */

		/**
		 * Register a callback for one of the update checker filters.
		 *
		 * Identical to add_filter(), except it automatically adds the "puc_" prefix
		 * and the "-$slug" suffix to the filter name. For example, "request_info_result"
		 * becomes "puc_request_info_result-your_plugin_slug".
		 *
		 * @param string $tag
		 * @param callable $callback
		 * @param int $priority
		 * @param int $acceptedArgs
		 */
		public function addFilter($tag, $callback, $priority = 10, $acceptedArgs = 1) {
			add_filter($this->getUniqueName($tag), $callback, $priority, $acceptedArgs);
		}

		/* -------------------------------------------------------------------
		 * Inject updates
		 * -------------------------------------------------------------------
		 */

		/**
		 * Insert the latest update (if any) into the update list maintained by WP.
		 *
		 * @param stdClass $updates Update list.
		 * @return stdClass Modified update list.
		 */
		public function injectUpdate($updates) {
			//Is there an update to insert?
			$update = $this->getUpdate();

			if ( !$this->shouldShowUpdates() ) {
				$update = null;
			}

			if ( !empty($update) ) {
				//Let plugins filter the update info before it's passed on to WordPress.
				$update = apply_filters($this->getUniqueName('pre_inject_update'), $update);
				$updates = $this->addUpdateToList($updates, $update->toWpFormat());
			} else {
				//Clean up any stale update info.
				$updates = $this->removeUpdateFromList($updates);
			}

			return $updates;
		}

		/**
		 * @param stdClass|null $updates
		 * @param stdClass|array $updateToAdd
		 * @return stdClass
		 */
		protected function addUpdateToList($updates, $updateToAdd) {
			if ( !is_object($updates) ) {
				$updates = new stdClass();
				$updates->response = array();
			}

			$updates->response[$this->getUpdateListKey()] = $updateToAdd;
			return $updates;
		}

		/**
		 * @param stdClass|null $updates
		 * @return stdClass|null
		 */
		protected function removeUpdateFromList($updates) {
			if ( isset($updates, $updates->response) ) {
				unset($updates->response[$this->getUpdateListKey()]);
			}
			return $updates;
		}

		/**
		 * Get the key that will be used when adding updates to the update list that's maintained
		 * by the WordPress core. The list is always an associative array, but the key is different
		 * for plugins and themes.
		 *
		 * @return string
		 */
		abstract protected function getUpdateListKey();

		/**
		 * Should we show available updates?
		 *
		 * Usually the answer is "yes", but there are exceptions. For example, WordPress doesn't
		 * support automatic updates installation for mu-plugins, so PUC usually won't show update
		 * notifications in that case. See the plugin-specific subclass for details.
		 *
		 * Note: This method only applies to updates that are displayed (or not) in the WordPress
		 * admin. It doesn't affect APIs like requestUpdate and getUpdate.
		 *
		 * @return bool
		 */
		protected function shouldShowUpdates() {
			return true;
		}

		/* -------------------------------------------------------------------
		 * JSON-based update API
		 * -------------------------------------------------------------------
		 */

		/**
		 * Retrieve plugin or theme metadata from the JSON document at $this->metadataUrl.
		 *
		 * @param string $metaClass Parse the JSON as an instance of this class. It must have a static fromJson method.
		 * @param string $filterRoot
		 * @param array $queryArgs Additional query arguments.
		 * @return array [Puc_v4p9_Metadata|null, array|WP_Error] A metadata instance and the value returned by wp_remote_get().
		 */
		protected function requestMetadata($metaClass, $filterRoot, $queryArgs = array()) {
			//Query args to append to the URL. Plugins can add their own by using a filter callback (see addQueryArgFilter()).
			$queryArgs = array_merge(
				array(
					'installed_version' => strval($this->getInstalledVersion()),
					'php' => phpversion(),
					'locale' => get_locale(),
				),
				$queryArgs
			);
			$queryArgs = apply_filters($this->getUniqueName($filterRoot . '_query_args'), $queryArgs);

			//Various options for the wp_remote_get() call. Plugins can filter these, too.
			$options = array(
				'timeout' => 10, //seconds
				'headers' => array(
					'Accept' => 'application/json',
				),
			);
			$options = apply_filters($this->getUniqueName($filterRoot . '_options'), $options);

			//The metadata file should be at 'http://your-api.com/url/here/$slug/info.json'
			$url = $this->metadataUrl;
			if ( !empty($queryArgs) ){
				$url = add_query_arg($queryArgs, $url);
			}

			$result = wp_remote_get($url, $options);

			$result = apply_filters($this->getUniqueName('request_metadata_http_result'), $result, $url, $options);
			
			//Try to parse the response
			$status = $this->validateApiResponse($result);
			$metadata = null;
			if ( !is_wp_error($status) ){
				$metadata = call_user_func(array($metaClass, 'fromJson'), $result['body']);
			} else {
				do_action('puc_api_error', $status, $result, $url, $this->slug);
				$this->triggerError(
					sprintf('The URL %s does not point to a valid metadata file. ', $url)
					. $status->get_error_message(),
					E_USER_WARNING
				);
			}

			return array($metadata, $result);
		}

		/**
		 * Check if $result is a successful update API response.
		 *
		 * @param array|WP_Error $result
		 * @return true|WP_Error
		 */
		protected function validateApiResponse($result) {
			if ( is_wp_error($result) ) { /** @var WP_Error $result */
				return new WP_Error($result->get_error_code(), 'WP HTTP Error: ' . $result->get_error_message());
			}

			if ( !isset($result['response']['code']) ) {
				return new WP_Error(
					'puc_no_response_code',
					'wp_remote_get() returned an unexpected result.'
				);
			}

			if ( $result['response']['code'] !== 200 ) {
				return new WP_Error(
					'puc_unexpected_response_code',
					'HTTP response code is ' . $result['response']['code'] . ' (expected: 200)'
				);
			}

			if ( empty($result['body']) ) {
				return new WP_Error('puc_empty_response', 'The metadata file appears to be empty.');
			}

			return true;
		}

		/* -------------------------------------------------------------------
		 * Language packs / Translation updates
		 * -------------------------------------------------------------------
		 */

		/**
		 * Filter a list of translation updates and return a new list that contains only updates
		 * that apply to the current site.
		 *
		 * @param array $translations
		 * @return array
		 */
		protected function filterApplicableTranslations($translations) {
			$languages = array_flip(array_values(get_available_languages()));
			$installedTranslations = $this->getInstalledTranslations();

			$applicableTranslations = array();
			foreach ($translations as $translation) {
				//Does it match one of the available core languages?
				$isApplicable = array_key_exists($translation->language, $languages);
				//Is it more recent than an already-installed translation?
				if ( isset($installedTranslations[$translation->language]) ) {
					$updateTimestamp = strtotime($translation->updated);
					$installedTimestamp = strtotime($installedTranslations[$translation->language]['PO-Revision-Date']);
					$isApplicable = $updateTimestamp > $installedTimestamp;
				}

				if ( $isApplicable ) {
					$applicableTranslations[] = $translation;
				}
			}

			return $applicableTranslations;
		}

		/**
		 * Get a list of installed translations for this plugin or theme.
		 *
		 * @return array
		 */
		protected function getInstalledTranslations() {
			if ( !function_exists('wp_get_installed_translations') ) {
				return array();
			}
			$installedTranslations = wp_get_installed_translations($this->translationType . 's');
			if ( isset($installedTranslations[$this->directoryName]) ) {
				$installedTranslations = $installedTranslations[$this->directoryName];
			} else {
				$installedTranslations = array();
			}
			return $installedTranslations;
		}

		/**
		 * Insert translation updates into the list maintained by WordPress.
		 *
		 * @param stdClass $updates
		 * @return stdClass
		 */
		public function injectTranslationUpdates($updates) {
			$translationUpdates = $this->getTranslationUpdates();
			if ( empty($translationUpdates) ) {
				return $updates;
			}

			//Being defensive.
			if ( !is_object($updates) ) {
				$updates = new stdClass();
			}
			if ( !isset($updates->translations) ) {
				$updates->translations = array();
			}

			//In case there's a name collision with a plugin or theme hosted on wordpress.org,
			//remove any preexisting updates that match our thing.
			$updates->translations = array_values(array_filter(
				$updates->translations,
				array($this, 'isNotMyTranslation')
			));

			//Add our updates to the list.
			foreach($translationUpdates as $update) {
				$convertedUpdate = array_merge(
					array(
						'type' => $this->translationType,
						'slug' => $this->directoryName,
						'autoupdate' => 0,
						//AFAICT, WordPress doesn't actually use the "version" field for anything.
						//But lets make sure it's there, just in case.
						'version' => isset($update->version) ? $update->version : ('1.' . strtotime($update->updated)),
					),
					(array)$update
				);

				$updates->translations[] = $convertedUpdate;
			}

			return $updates;
		}

		/**
		 * Get a list of available translation updates.
		 *
		 * This method will return an empty array if there are no updates.
		 * Uses cached update data.
		 *
		 * @return array
		 */
		public function getTranslationUpdates() {
			return $this->updateState->getTranslations();
		}

		/**
		 * Remove all cached translation updates.
		 *
		 * @see wp_clean_update_cache
		 */
		public function clearCachedTranslationUpdates() {
			$this->updateState->setTranslations(array());
		}

		/**
		 * Filter callback. Keeps only translations that *don't* match this plugin or theme.
		 *
		 * @param array $translation
		 * @return bool
		 */
		protected function isNotMyTranslation($translation) {
			$isMatch = isset($translation['type'], $translation['slug'])
				&& ($translation['type'] === $this->translationType)
				&& ($translation['slug'] === $this->directoryName);

			return !$isMatch;
		}

		/* -------------------------------------------------------------------
		 * Fix directory name when installing updates
		 * -------------------------------------------------------------------
		 */

		/**
		 * Rename the update directory to match the existing plugin/theme directory.
		 *
		 * When WordPress installs a plugin or theme update, it assumes that the ZIP file will contain
		 * exactly one directory, and that the directory name will be the same as the directory where
		 * the plugin or theme is currently installed.
		 *
		 * GitHub and other repositories provide ZIP downloads, but they often use directory names like
		 * "project-branch" or "project-tag-hash". We need to change the name to the actual plugin folder.
		 *
		 * This is a hook callback. Don't call it from a plugin.
		 *
		 * @access protected
		 *
		 * @param string $source The directory to copy to /wp-content/plugins or /wp-content/themes. Usually a subdirectory of $remoteSource.
		 * @param string $remoteSource WordPress has extracted the update to this directory.
		 * @param WP_Upgrader $upgrader
		 * @return string|WP_Error
		 */
		public function fixDirectoryName($source, $remoteSource, $upgrader) {
			global $wp_filesystem;
			/** @var WP_Filesystem_Base $wp_filesystem */

			//Basic sanity checks.
			if ( !isset($source, $remoteSource, $upgrader, $upgrader->skin, $wp_filesystem) ) {
				return $source;
			}

			//If WordPress is upgrading anything other than our plugin/theme, leave the directory name unchanged.
			if ( !$this->isBeingUpgraded($upgrader) ) {
				return $source;
			}

			//Rename the source to match the existing directory.
			$correctedSource = trailingslashit($remoteSource) . $this->directoryName . '/';
			if ( $source !== $correctedSource ) {
				//The update archive should contain a single directory that contains the rest of plugin/theme files.
				//Otherwise, WordPress will try to copy the entire working directory ($source == $remoteSource).
				//We can't rename $remoteSource because that would break WordPress code that cleans up temporary files
				//after update.
				if ( $this->isBadDirectoryStructure($remoteSource) ) {
					return new WP_Error(
						'puc-incorrect-directory-structure',
						sprintf(
							'The directory structure of the update is incorrect. All files should be inside ' .
							'a directory named <span class="code">%s</span>, not at the root of the ZIP archive.',
							htmlentities($this->slug)
						)
					);
				}

				/** @var WP_Upgrader_Skin $upgrader ->skin */
				$upgrader->skin->feedback(sprintf(
					'Renaming %s to %s&#8230;',
					'<span class="code">' . basename($source) . '</span>',
					'<span class="code">' . $this->directoryName . '</span>'
				));

				if ( $wp_filesystem->move($source, $correctedSource, true) ) {
					$upgrader->skin->feedback('Directory successfully renamed.');
					return $correctedSource;
				} else {
					return new WP_Error(
						'puc-rename-failed',
						'Unable to rename the update to match the existing directory.'
					);
				}
			}

			return $source;
		}

		/**
		 * Is there an update being installed right now, for this plugin or theme?
		 *
		 * @param WP_Upgrader|null $upgrader The upgrader that's performing the current update.
		 * @return bool
		 */
		abstract public function isBeingUpgraded($upgrader = null);

		/**
		 * Check for incorrect update directory structure. An update must contain a single directory,
		 * all other files should be inside that directory.
		 *
		 * @param string $remoteSource Directory path.
		 * @return bool
		 */
		protected function isBadDirectoryStructure($remoteSource) {
			global $wp_filesystem;
			/** @var WP_Filesystem_Base $wp_filesystem */

			$sourceFiles = $wp_filesystem->dirlist($remoteSource);
			if ( is_array($sourceFiles) ) {
				$sourceFiles = array_keys($sourceFiles);
				$firstFilePath = trailingslashit($remoteSource) . $sourceFiles[0];
				return (count($sourceFiles) > 1) || (!$wp_filesystem->is_dir($firstFilePath));
			}

			//Assume it's fine.
			return false;
		}

		/* -------------------------------------------------------------------
		 * DebugBar integration
		 * -------------------------------------------------------------------
		 */

		/**
		 * Initialize the update checker Debug Bar plugin/add-on thingy.
		 */
		public function maybeInitDebugBar() {
			if ( class_exists('Debug_Bar', false) && file_exists(dirname(__FILE__) . '/DebugBar') ) {
				$this->createDebugBarExtension();
			}
		}

		protected function createDebugBarExtension() {
			return new Puc_v4p9_DebugBar_Extension($this);
		}

		/**
		 * Display additional configuration details in the Debug Bar panel.
		 *
		 * @param Puc_v4p9_DebugBar_Panel $panel
		 */
		public function onDisplayConfiguration($panel) {
			//Do nothing. Subclasses can use this to add additional info to the panel.
		}

	}

endif;
