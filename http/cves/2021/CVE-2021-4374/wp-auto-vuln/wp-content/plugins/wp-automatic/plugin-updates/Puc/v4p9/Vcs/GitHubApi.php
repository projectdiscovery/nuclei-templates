<?php

if ( !class_exists('Puc_v4p9_Vcs_GitHubApi', false) ):

	class Puc_v4p9_Vcs_GitHubApi extends Puc_v4p9_Vcs_Api {
		/**
		 * @var string GitHub username.
		 */
		protected $userName;
		/**
		 * @var string GitHub repository name.
		 */
		protected $repositoryName;

		/**
		 * @var string Either a fully qualified repository URL, or just "user/repo-name".
		 */
		protected $repositoryUrl;

		/**
		 * @var string GitHub authentication token. Optional.
		 */
		protected $accessToken;

		/**
		 * @var bool Whether to download release assets instead of the auto-generated source code archives.
		 */
		protected $releaseAssetsEnabled = false;

		/**
		 * @var string|null Regular expression that's used to filter release assets by name. Optional.
		 */
		protected $assetFilterRegex = null;

		/**
		 * @var string|null The unchanging part of a release asset URL. Used to identify download attempts.
		 */
		protected $assetApiBaseUrl = null;

		/**
		 * @var bool
		 */
		private $downloadFilterAdded = false;

		public function __construct($repositoryUrl, $accessToken = null) {
			$path = parse_url($repositoryUrl, PHP_URL_PATH);
			if ( preg_match('@^/?(?P<username>[^/]+?)/(?P<repository>[^/#?&]+?)/?$@', $path, $matches) ) {
				$this->userName = $matches['username'];
				$this->repositoryName = $matches['repository'];
			} else {
				throw new InvalidArgumentException('Invalid GitHub repository URL: "' . $repositoryUrl . '"');
			}

			parent::__construct($repositoryUrl, $accessToken);
		}

		/**
		 * Get the latest release from GitHub.
		 *
		 * @return Puc_v4p9_Vcs_Reference|null
		 */
		public function getLatestRelease() {
			$release = $this->api('/repos/:user/:repo/releases/latest');
			if ( is_wp_error($release) || !is_object($release) || !isset($release->tag_name) ) {
				return null;
			}

			$reference = new Puc_v4p9_Vcs_Reference(array(
				'name'        => $release->tag_name,
				'version'     => ltrim($release->tag_name, 'v'), //Remove the "v" prefix from "v1.2.3".
				'downloadUrl' => $release->zipball_url,
				'updated'     => $release->created_at,
				'apiResponse' => $release,
			));

			if ( isset($release->assets[0]) ) {
				$reference->downloadCount = $release->assets[0]->download_count;
			}

			if ( $this->releaseAssetsEnabled && isset($release->assets, $release->assets[0]) ) {
				//Use the first release asset that matches the specified regular expression.
				$matchingAssets = array_filter($release->assets, array($this, 'matchesAssetFilter'));
				if ( !empty($matchingAssets) ) {
					if ( $this->isAuthenticationEnabled() ) {
						/**
						 * Keep in mind that we'll need to add an "Accept" header to download this asset.
						 *
						 * @see setUpdateDownloadHeaders()
						 */
						$reference->downloadUrl = $matchingAssets[0]->url;
					} else {
						//It seems that browser_download_url only works for public repositories.
						//Using an access_token doesn't help. Maybe OAuth would work?
						$reference->downloadUrl = $matchingAssets[0]->browser_download_url;
					}

					$reference->downloadCount = $matchingAssets[0]->download_count;
				}
			}

			if ( !empty($release->body) ) {
				/** @noinspection PhpUndefinedClassInspection */
				$reference->changelog = Parsedown::instance()->text($release->body);
			}

			return $reference;
		}

		/**
		 * Get the tag that looks like the highest version number.
		 *
		 * @return Puc_v4p9_Vcs_Reference|null
		 */
		public function getLatestTag() {
			$tags = $this->api('/repos/:user/:repo/tags');

			if ( is_wp_error($tags) || !is_array($tags) ) {
				return null;
			}

			$versionTags = $this->sortTagsByVersion($tags);
			if ( empty($versionTags) ) {
				return null;
			}

			$tag = $versionTags[0];
			return new Puc_v4p9_Vcs_Reference(array(
				'name'        => $tag->name,
				'version'     => ltrim($tag->name, 'v'),
				'downloadUrl' => $tag->zipball_url,
				'apiResponse' => $tag,
			));
		}

		/**
		 * Get a branch by name.
		 *
		 * @param string $branchName
		 * @return null|Puc_v4p9_Vcs_Reference
		 */
		public function getBranch($branchName) {
			$branch = $this->api('/repos/:user/:repo/branches/' . $branchName);
			if ( is_wp_error($branch) || empty($branch) ) {
				return null;
			}

			$reference = new Puc_v4p9_Vcs_Reference(array(
				'name'        => $branch->name,
				'downloadUrl' => $this->buildArchiveDownloadUrl($branch->name),
				'apiResponse' => $branch,
			));

			if ( isset($branch->commit, $branch->commit->commit, $branch->commit->commit->author->date) ) {
				$reference->updated = $branch->commit->commit->author->date;
			}

			return $reference;
		}

		/**
		 * Get the latest commit that changed the specified file.
		 *
		 * @param string $filename
		 * @param string $ref Reference name (e.g. branch or tag).
		 * @return StdClass|null
		 */
		public function getLatestCommit($filename, $ref = 'master') {
			$commits = $this->api(
				'/repos/:user/:repo/commits',
				array(
					'path' => $filename,
					'sha'  => $ref,
				)
			);
			if ( !is_wp_error($commits) && isset($commits[0]) ) {
				return $commits[0];
			}
			return null;
		}

		/**
		 * Get the timestamp of the latest commit that changed the specified branch or tag.
		 *
		 * @param string $ref Reference name (e.g. branch or tag).
		 * @return string|null
		 */
		public function getLatestCommitTime($ref) {
			$commits = $this->api('/repos/:user/:repo/commits', array('sha' => $ref));
			if ( !is_wp_error($commits) && isset($commits[0]) ) {
				return $commits[0]->commit->author->date;
			}
			return null;
		}

		/**
		 * Perform a GitHub API request.
		 *
		 * @param string $url
		 * @param array $queryParams
		 * @return mixed|WP_Error
		 */
		protected function api($url, $queryParams = array()) {
			$baseUrl = $url;
			$url = $this->buildApiUrl($url, $queryParams);

			$options = array('timeout' => 10);
			if ( $this->isAuthenticationEnabled() ) {
				$options['headers'] = array('Authorization' => $this->getAuthorizationHeader());
			}

			if ( !empty($this->httpFilterName) ) {
				$options = apply_filters($this->httpFilterName, $options);
			}
			$response = wp_remote_get($url, $options);
			if ( is_wp_error($response) ) {
				do_action('puc_api_error', $response, null, $url, $this->slug);
				return $response;
			}

			$code = wp_remote_retrieve_response_code($response);
			$body = wp_remote_retrieve_body($response);
			if ( $code === 200 ) {
				$document = json_decode($body);
				return $document;
			}

			$error = new WP_Error(
				'puc-github-http-error',
				sprintf('GitHub API error. Base URL: "%s",  HTTP status code: %d.', $baseUrl, $code)
			);
			do_action('puc_api_error', $error, $response, $url, $this->slug);

			return $error;
		}

		/**
		 * Build a fully qualified URL for an API request.
		 *
		 * @param string $url
		 * @param array $queryParams
		 * @return string
		 */
		protected function buildApiUrl($url, $queryParams) {
			$variables = array(
				'user' => $this->userName,
				'repo' => $this->repositoryName,
			);
			foreach ($variables as $name => $value) {
				$url = str_replace('/:' . $name, '/' . urlencode($value), $url);
			}
			$url = 'https://api.github.com' . $url;

			if ( !empty($queryParams) ) {
				$url = add_query_arg($queryParams, $url);
			}

			return $url;
		}

		/**
		 * Get the contents of a file from a specific branch or tag.
		 *
		 * @param string $path File name.
		 * @param string $ref
		 * @return null|string Either the contents of the file, or null if the file doesn't exist or there's an error.
		 */
		public function getRemoteFile($path, $ref = 'master') {
			$apiUrl = '/repos/:user/:repo/contents/' . $path;
			$response = $this->api($apiUrl, array('ref' => $ref));

			if ( is_wp_error($response) || !isset($response->content) || ($response->encoding !== 'base64') ) {
				return null;
			}
			return base64_decode($response->content);
		}

		/**
		 * Generate a URL to download a ZIP archive of the specified branch/tag/etc.
		 *
		 * @param string $ref
		 * @return string
		 */
		public function buildArchiveDownloadUrl($ref = 'master') {
			$url = sprintf(
				'https://api.github.com/repos/%1$s/%2$s/zipball/%3$s',
				urlencode($this->userName),
				urlencode($this->repositoryName),
				urlencode($ref)
			);
			return $url;
		}

		/**
		 * Get a specific tag.
		 *
		 * @param string $tagName
		 * @return void
		 */
		public function getTag($tagName) {
			//The current GitHub update checker doesn't use getTag, so I didn't bother to implement it.
			throw new LogicException('The ' . __METHOD__ . ' method is not implemented and should not be used.');
		}

		public function setAuthentication($credentials) {
			parent::setAuthentication($credentials);
			$this->accessToken = is_string($credentials) ? $credentials : null;

			//Optimization: Instead of filtering all HTTP requests, let's do it only when
			//WordPress is about to download an update.
			add_filter('upgrader_pre_download', array($this, 'addHttpRequestFilter'), 10, 1); //WP 3.7+
		}

		/**
		 * Figure out which reference (i.e tag or branch) contains the latest version.
		 *
		 * @param string $configBranch Start looking in this branch.
		 * @return null|Puc_v4p9_Vcs_Reference
		 */
		public function chooseReference($configBranch) {
			$updateSource = null;

			if ( $configBranch === 'master' ) {
				//Use the latest release.
				$updateSource = $this->getLatestRelease();
				if ( $updateSource === null ) {
					//Failing that, use the tag with the highest version number.
					$updateSource = $this->getLatestTag();
				}
			}
			//Alternatively, just use the branch itself.
			if ( empty($updateSource) ) {
				$updateSource = $this->getBranch($configBranch);
			}

			return $updateSource;
		}

		/**
		 * Enable updating via release assets.
		 *
		 * If the latest release contains no usable assets, the update checker
		 * will fall back to using the automatically generated ZIP archive.
		 *
		 * Private repositories will only work with WordPress 3.7 or later.
		 *
		 * @param string|null $fileNameRegex Optional. Use only those assets where the file name matches this regex.
		 */
		public function enableReleaseAssets($fileNameRegex = null) {
			$this->releaseAssetsEnabled = true;
			$this->assetFilterRegex = $fileNameRegex;
			$this->assetApiBaseUrl = sprintf(
				'//api.github.com/repos/%1$s/%2$s/releases/assets/',
				$this->userName,
				$this->repositoryName
			);
		}

		/**
		 * Does this asset match the file name regex?
		 *
		 * @param stdClass $releaseAsset
		 * @return bool
		 */
		protected function matchesAssetFilter($releaseAsset) {
			if ( $this->assetFilterRegex === null ) {
				//The default is to accept all assets.
				return true;
			}
			return isset($releaseAsset->name) && preg_match($this->assetFilterRegex, $releaseAsset->name);
		}

		/**
		 * @internal
		 * @param bool $result
		 * @return bool
		 */
		public function addHttpRequestFilter($result) {
			if ( !$this->downloadFilterAdded && $this->isAuthenticationEnabled() ) {
				add_filter('http_request_args', array($this, 'setUpdateDownloadHeaders'), 10, 2);
				add_action('requests-requests.before_redirect', array($this, 'removeAuthHeaderFromRedirects'), 10, 4);
				$this->downloadFilterAdded = true;
			}
			return $result;
		}

		/**
		 * Set the HTTP headers that are necessary to download updates from private repositories.
		 *
		 * See GitHub docs:
		 * @link https://developer.github.com/v3/repos/releases/#get-a-single-release-asset
		 * @link https://developer.github.com/v3/auth/#basic-authentication
		 *
		 * @internal
		 * @param array $requestArgs
		 * @param string $url
		 * @return array
		 */
		public function setUpdateDownloadHeaders($requestArgs, $url = '') {
			//Is WordPress trying to download one of our release assets?
			if ( $this->releaseAssetsEnabled && (strpos($url, $this->assetApiBaseUrl) !== false) ) {
				$requestArgs['headers']['Accept'] = 'application/octet-stream';
			}
			//Use Basic authentication, but only if the download is from our repository.
			$repoApiBaseUrl = $this->buildApiUrl('/repos/:user/:repo/', array());
			if ( $this->isAuthenticationEnabled() && (strpos($url, $repoApiBaseUrl)) === 0 ) {
				$requestArgs['headers']['Authorization'] = $this->getAuthorizationHeader();
			}
			return $requestArgs;
		}

		/**
		 * When following a redirect, the Requests library will automatically forward
		 * the authorization header to other hosts. We don't want that because it breaks
		 * AWS downloads and can leak authorization information.
		 *
		 * @internal
		 * @param string $location
		 * @param array $headers
		 */
		public function removeAuthHeaderFromRedirects(&$location, &$headers) {
			$repoApiBaseUrl = $this->buildApiUrl('/repos/:user/:repo/', array());
			if ( strpos($location, $repoApiBaseUrl) === 0 ) {
				return; //This request is going to GitHub, so it's fine.
			}
			//Remove the header.
			if ( isset($headers['Authorization']) ) {
				unset($headers['Authorization']);
			}
		}

		/**
		 * Generate the value of the "Authorization" header.
		 *
		 * @return string
		 */
		protected function getAuthorizationHeader() {
			return 'Basic ' . base64_encode($this->userName . ':' . $this->accessToken);
		}
	}

endif;
