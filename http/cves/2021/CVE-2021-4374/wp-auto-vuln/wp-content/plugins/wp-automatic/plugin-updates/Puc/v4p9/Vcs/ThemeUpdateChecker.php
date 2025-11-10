<?php

if ( !class_exists('Puc_v4p9_Vcs_ThemeUpdateChecker', false) ):

	class Puc_v4p9_Vcs_ThemeUpdateChecker extends Puc_v4p9_Theme_UpdateChecker implements Puc_v4p9_Vcs_BaseChecker {
		/**
		 * @var string The branch where to look for updates. Defaults to "master".
		 */
		protected $branch = 'master';

		/**
		 * @var Puc_v4p9_Vcs_Api Repository API client.
		 */
		protected $api = null;

		/**
		 * Puc_v4p9_Vcs_ThemeUpdateChecker constructor.
		 *
		 * @param Puc_v4p9_Vcs_Api $api
		 * @param null $stylesheet
		 * @param null $customSlug
		 * @param int $checkPeriod
		 * @param string $optionName
		 */
		public function __construct($api, $stylesheet = null, $customSlug = null, $checkPeriod = 12, $optionName = '') {
			$this->api = $api;
			$this->api->setHttpFilterName($this->getUniqueName('request_update_options'));

			parent::__construct($api->getRepositoryUrl(), $stylesheet, $customSlug, $checkPeriod, $optionName);

			$this->api->setSlug($this->slug);
		}

		public function requestUpdate() {
			$api = $this->api;
			$api->setLocalDirectory($this->package->getAbsoluteDirectoryPath());

			$update = new Puc_v4p9_Theme_Update();
			$update->slug = $this->slug;

			//Figure out which reference (tag or branch) we'll use to get the latest version of the theme.
			$updateSource = $api->chooseReference($this->branch);
			if ( $updateSource ) {
				$ref = $updateSource->name;
				$update->download_url = $updateSource->downloadUrl;
			} else {
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
				$ref = $this->branch;
			}

			//Get headers from the main stylesheet in this branch/tag. Its "Version" header and other metadata
			//are what the WordPress install will actually see after upgrading, so they take precedence over releases/tags.
			$remoteHeader = $this->package->getFileHeader($api->getRemoteFile('style.css', $ref));
			$update->version = Puc_v4p9_Utils::findNotEmpty(array(
				$remoteHeader['Version'],
				Puc_v4p9_Utils::get($updateSource, 'version'),
			));

			//The details URL defaults to the Theme URI header or the repository URL.
			$update->details_url = Puc_v4p9_Utils::findNotEmpty(array(
				$remoteHeader['ThemeURI'],
				$this->package->getHeaderValue('ThemeURI'),
				$this->metadataUrl,
			));

			if ( empty($update->version) ) {
				//It looks like we didn't find a valid update after all.
				$update = null;
			}

			$update = $this->filterUpdateResult($update);
			return $update;
		}

		//FIXME: This is duplicated code. Both theme and plugin subclasses that use VCS share these methods.

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
