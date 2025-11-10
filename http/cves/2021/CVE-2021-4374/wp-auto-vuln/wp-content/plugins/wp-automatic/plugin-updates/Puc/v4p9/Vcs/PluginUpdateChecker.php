<?php
if ( !class_exists('Puc_v4p9_Vcs_PluginUpdateChecker') ):

	class Puc_v4p9_Vcs_PluginUpdateChecker extends Puc_v4p9_Plugin_UpdateChecker implements Puc_v4p9_Vcs_BaseChecker {
		/**
		 * @var string The branch where to look for updates. Defaults to "master".
		 */
		protected $branch = 'master';

		/**
		 * @var Puc_v4p9_Vcs_Api Repository API client.
		 */
		protected $api = null;

		/**
		 * Puc_v4p9_Vcs_PluginUpdateChecker constructor.
		 *
		 * @param Puc_v4p9_Vcs_Api $api
		 * @param string $pluginFile
		 * @param string $slug
		 * @param int $checkPeriod
		 * @param string $optionName
		 * @param string $muPluginFile
		 */
		public function __construct($api, $pluginFile, $slug = '', $checkPeriod = 12, $optionName = '', $muPluginFile = '') {
			$this->api = $api;
			$this->api->setHttpFilterName($this->getUniqueName('request_info_options'));

			parent::__construct($api->getRepositoryUrl(), $pluginFile, $slug, $checkPeriod, $optionName, $muPluginFile);

			$this->api->setSlug($this->slug);
		}

		public function requestInfo($unusedParameter = null) {
			//We have to make several remote API requests to gather all the necessary info
			//which can take a while on slow networks.
			if ( function_exists('set_time_limit') ) {
				@set_time_limit(60);
			}

			$api = $this->api;
			$api->setLocalDirectory($this->package->getAbsoluteDirectoryPath());

			$info = new Puc_v4p9_Plugin_Info();
			$info->filename = $this->pluginFile;
			$info->slug = $this->slug;

			$this->setInfoFromHeader($this->package->getPluginHeader(), $info);

			//Pick a branch or tag.
			$updateSource = $api->chooseReference($this->branch);
			if ( $updateSource ) {
				$ref = $updateSource->name;
				$info->version = $updateSource->version;
				$info->last_updated = $updateSource->updated;
				$info->download_url = $updateSource->downloadUrl;

				if ( !empty($updateSource->changelog) ) {
					$info->sections['changelog'] = $updateSource->changelog;
				}
				if ( isset($updateSource->downloadCount) ) {
					$info->downloaded = $updateSource->downloadCount;
				}
			} else {
				//There's probably a network problem or an authentication error.
				do_action(
					'puc_api_error',
					new WP_Error(
						'puc-no-update-source',
						'Could not retrieve version information from the repository. '
						. 'This usually means that the update checker either can\'t connect '
						. 'to the repository or it\'s configured incorrectly.'
					),
					null, null, $this->slug
				);
				return null;
			}

			//Get headers from the main plugin file in this branch/tag. Its "Version" header and other metadata
			//are what the WordPress install will actually see after upgrading, so they take precedence over releases/tags.
			$mainPluginFile = basename($this->pluginFile);
			$remotePlugin = $api->getRemoteFile($mainPluginFile, $ref);
			if ( !empty($remotePlugin) ) {
				$remoteHeader = $this->package->getFileHeader($remotePlugin);
				$this->setInfoFromHeader($remoteHeader, $info);
			}

			//Try parsing readme.txt. If it's formatted according to WordPress.org standards, it will contain
			//a lot of useful information like the required/tested WP version, changelog, and so on.
			if ( $this->readmeTxtExistsLocally() ) {
				$this->setInfoFromRemoteReadme($ref, $info);
			}

			//The changelog might be in a separate file.
			if ( empty($info->sections['changelog']) ) {
				$info->sections['changelog'] = $api->getRemoteChangelog($ref, $this->package->getAbsoluteDirectoryPath());
				if ( empty($info->sections['changelog']) ) {
					$info->sections['changelog'] = __('There is no changelog available.', 'plugin-update-checker');
				}
			}

			if ( empty($info->last_updated) ) {
				//Fetch the latest commit that changed the tag or branch and use it as the "last_updated" date.
				$latestCommitTime = $api->getLatestCommitTime($ref);
				if ( $latestCommitTime !== null ) {
					$info->last_updated = $latestCommitTime;
				}
			}

			$info = apply_filters($this->getUniqueName('request_info_result'), $info, null);
			return $info;
		}

		/**
		 * Check if the currently installed version has a readme.txt file.
		 *
		 * @return bool
		 */
		protected function readmeTxtExistsLocally() {
			return $this->package->fileExists($this->api->getLocalReadmeName());
		}

		/**
		 * Copy plugin metadata from a file header to a Plugin Info object.
		 *
		 * @param array $fileHeader
		 * @param Puc_v4p9_Plugin_Info $pluginInfo
		 */
		protected function setInfoFromHeader($fileHeader, $pluginInfo) {
			$headerToPropertyMap = array(
				'Version' => 'version',
				'Name' => 'name',
				'PluginURI' => 'homepage',
				'Author' => 'author',
				'AuthorName' => 'author',
				'AuthorURI' => 'author_homepage',

				'Requires WP' => 'requires',
				'Tested WP' => 'tested',
				'Requires at least' => 'requires',
				'Tested up to' => 'tested',
			);
			foreach ($headerToPropertyMap as $headerName => $property) {
				if ( isset($fileHeader[$headerName]) && !empty($fileHeader[$headerName]) ) {
					$pluginInfo->$property = $fileHeader[$headerName];
				}
			}

			if ( !empty($fileHeader['Description']) ) {
				$pluginInfo->sections['description'] = $fileHeader['Description'];
			}
		}

		/**
		 * Copy plugin metadata from the remote readme.txt file.
		 *
		 * @param string $ref GitHub tag or branch where to look for the readme.
		 * @param Puc_v4p9_Plugin_Info $pluginInfo
		 */
		protected function setInfoFromRemoteReadme($ref, $pluginInfo) {
			$readme = $this->api->getRemoteReadme($ref);
			if ( empty($readme) ) {
				return;
			}

			if ( isset($readme['sections']) ) {
				$pluginInfo->sections = array_merge($pluginInfo->sections, $readme['sections']);
			}
			if ( !empty($readme['tested_up_to']) ) {
				$pluginInfo->tested = $readme['tested_up_to'];
			}
			if ( !empty($readme['requires_at_least']) ) {
				$pluginInfo->requires = $readme['requires_at_least'];
			}

			if ( isset($readme['upgrade_notice'], $readme['upgrade_notice'][$pluginInfo->version]) ) {
				$pluginInfo->upgrade_notice = $readme['upgrade_notice'][$pluginInfo->version];
			}
		}

		public function setBranch($branch) {
			$this->branch = $branch;
			return $this;
		}

		public function setAuthentication($credentials) {
			$this->api->setAuthentication($credentials);
			return $this;
		}

		public function getVcsApi() {
			return $this->api;
		}

		public function getUpdate() {
			$update = parent::getUpdate();

			if ( isset($update) && !empty($update->download_url) ) {
				$update->download_url = $this->api->signDownloadUrl($update->download_url);
			}

			return $update;
		}

		public function onDisplayConfiguration($panel) {
			parent::onDisplayConfiguration($panel);
			$panel->row('Branch', $this->branch);
			$panel->row('Authentication enabled', $this->api->isAuthenticationEnabled() ? 'Yes' : 'No');
			$panel->row('API client', get_class($this->api));
		}
	}

endif;
