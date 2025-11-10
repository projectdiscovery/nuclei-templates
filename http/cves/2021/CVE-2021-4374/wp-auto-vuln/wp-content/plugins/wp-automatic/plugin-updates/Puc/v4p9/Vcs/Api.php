<?php
if ( !class_exists('Puc_v4p9_Vcs_Api') ):

	abstract class Puc_v4p9_Vcs_Api {
		protected $tagNameProperty = 'name';
		protected $slug = '';

		/**
		 * @var string
		 */
		protected $repositoryUrl = '';

		/**
		 * @var mixed Authentication details for private repositories. Format depends on service.
		 */
		protected $credentials = null;

		/**
		 * @var string The filter tag that's used to filter options passed to wp_remote_get.
		 * For example, "puc_request_info_options-slug" or "puc_request_update_options_theme-slug".
		 */
		protected $httpFilterName = '';

		/**
		 * @var string|null
		 */
		protected $localDirectory = null;

		/**
		 * Puc_v4p9_Vcs_Api constructor.
		 *
		 * @param string $repositoryUrl
		 * @param array|string|null $credentials
		 */
		public function __construct($repositoryUrl, $credentials = null) {
			$this->repositoryUrl = $repositoryUrl;
			$this->setAuthentication($credentials);
		}

		/**
		 * @return string
		 */
		public function getRepositoryUrl() {
			return $this->repositoryUrl;
		}

		/**
		 * Figure out which reference (i.e tag or branch) contains the latest version.
		 *
		 * @param string $configBranch Start looking in this branch.
		 * @return null|Puc_v4p9_Vcs_Reference
		 */
		abstract public function chooseReference($configBranch);

		/**
		 * Get the readme.txt file from the remote repository and parse it
		 * according to the plugin readme standard.
		 *
		 * @param string $ref Tag or branch name.
		 * @return array Parsed readme.
		 */
		public function getRemoteReadme($ref = 'master') {
			$fileContents = $this->getRemoteFile($this->getLocalReadmeName(), $ref);
			if ( empty($fileContents) ) {
				return array();
			}

			$parser = new PucReadmeParser();
			return $parser->parse_readme_contents($fileContents);
		}

		/**
		 * Get the case-sensitive name of the local readme.txt file.
		 *
		 * In most cases it should just be called "readme.txt", but some plugins call it "README.txt",
		 * "README.TXT", or even "Readme.txt". Most VCS are case-sensitive so we need to know the correct
		 * capitalization.
		 *
		 * Defaults to "readme.txt" (all lowercase).
		 *
		 * @return string
		 */
		public function getLocalReadmeName() {
			static $fileName = null;
			if ( $fileName !== null ) {
				return $fileName;
			}

			$fileName = 'readme.txt';
			if ( isset($this->localDirectory) ) {
				$files = scandir($this->localDirectory);
				if ( !empty($files) ) {
					foreach ($files as $possibleFileName) {
						if ( strcasecmp($possibleFileName, 'readme.txt') === 0 ) {
							$fileName = $possibleFileName;
							break;
						}
					}
				}
			}
			return $fileName;
		}

		/**
		 * Get a branch.
		 *
		 * @param string $branchName
		 * @return Puc_v4p9_Vcs_Reference|null
		 */
		abstract public function getBranch($branchName);

		/**
		 * Get a specific tag.
		 *
		 * @param string $tagName
		 * @return Puc_v4p9_Vcs_Reference|null
		 */
		abstract public function getTag($tagName);

		/**
		 * Get the tag that looks like the highest version number.
		 * (Implementations should skip pre-release versions if possible.)
		 *
		 * @return Puc_v4p9_Vcs_Reference|null
		 */
		abstract public function getLatestTag();

		/**
		 * Check if a tag name string looks like a version number.
		 *
		 * @param string $name
		 * @return bool
		 */
		protected function looksLikeVersion($name) {
			//Tag names may be prefixed with "v", e.g. "v1.2.3".
			$name = ltrim($name, 'v');

			//The version string must start with a number.
			if ( !is_numeric(substr($name, 0, 1)) ) {
				return false;
			}

			//The goal is to accept any SemVer-compatible or "PHP-standardized" version number.
			return (preg_match('@^(\d{1,5}?)(\.\d{1,10}?){0,4}?($|[abrdp+_\-]|\s)@i', $name) === 1);
		}

		/**
		 * Check if a tag appears to be named like a version number.
		 *
		 * @param stdClass $tag
		 * @return bool
		 */
		protected function isVersionTag($tag) {
			$property = $this->tagNameProperty;
			return isset($tag->$property) && $this->looksLikeVersion($tag->$property);
		}

		/**
		 * Sort a list of tags as if they were version numbers.
		 * Tags that don't look like version number will be removed.
		 *
		 * @param stdClass[] $tags Array of tag objects.
		 * @return stdClass[] Filtered array of tags sorted in descending order.
		 */
		protected function sortTagsByVersion($tags) {
			//Keep only those tags that look like version numbers.
			$versionTags = array_filter($tags, array($this, 'isVersionTag'));
			//Sort them in descending order.
			usort($versionTags, array($this, 'compareTagNames'));

			return $versionTags;
		}

		/**
		 * Compare two tags as if they were version number.
		 *
		 * @param stdClass $tag1 Tag object.
		 * @param stdClass $tag2 Another tag object.
		 * @return int
		 */
		protected function compareTagNames($tag1, $tag2) {
			$property = $this->tagNameProperty;
			if ( !isset($tag1->$property) ) {
				return 1;
			}
			if ( !isset($tag2->$property) ) {
				return -1;
			}
			return -version_compare(ltrim($tag1->$property, 'v'), ltrim($tag2->$property, 'v'));
		}

		/**
		 * Get the contents of a file from a specific branch or tag.
		 *
		 * @param string $path File name.
		 * @param string $ref
		 * @return null|string Either the contents of the file, or null if the file doesn't exist or there's an error.
		 */
		abstract public function getRemoteFile($path, $ref = 'master');

		/**
		 * Get the timestamp of the latest commit that changed the specified branch or tag.
		 *
		 * @param string $ref Reference name (e.g. branch or tag).
		 * @return string|null
		 */
		abstract public function getLatestCommitTime($ref);

		/**
		 * Get the contents of the changelog file from the repository.
		 *
		 * @param string $ref
		 * @param string $localDirectory Full path to the local plugin or theme directory.
		 * @return null|string The HTML contents of the changelog.
		 */
		public function getRemoteChangelog($ref, $localDirectory) {
			$filename = $this->findChangelogName($localDirectory);
			if ( empty($filename) ) {
				return null;
			}

			$changelog = $this->getRemoteFile($filename, $ref);
			if ( $changelog === null ) {
				return null;
			}

			/** @noinspection PhpUndefinedClassInspection */
			return Parsedown::instance()->text($changelog);
		}

		/**
		 * Guess the name of the changelog file.
		 *
		 * @param string $directory
		 * @return string|null
		 */
		protected function findChangelogName($directory = null) {
			if ( !isset($directory) ) {
				$directory = $this->localDirectory;
			}
			if ( empty($directory) || !is_dir($directory) || ($directory === '.') ) {
				return null;
			}

			$possibleNames = array('CHANGES.md', 'CHANGELOG.md', 'changes.md', 'changelog.md');
			$files = scandir($directory);
			$foundNames = array_intersect($possibleNames, $files);

			if ( !empty($foundNames) ) {
				return reset($foundNames);
			}
			return null;
		}

		/**
		 * Set authentication credentials.
		 *
		 * @param $credentials
		 */
		public function setAuthentication($credentials) {
			$this->credentials = $credentials;
		}

		public function isAuthenticationEnabled() {
			return !empty($this->credentials);
		}

		/**
		 * @param string $url
		 * @return string
		 */
		public function signDownloadUrl($url) {
			return $url;
		}

		/**
		 * @param string $filterName
		 */
		public function setHttpFilterName($filterName) {
			$this->httpFilterName = $filterName;
		}

		/**
		 * @param string $directory
		 */
		public function setLocalDirectory($directory) {
			if ( empty($directory) || !is_dir($directory) || ($directory === '.') ) {
				$this->localDirectory = null;
			} else {
				$this->localDirectory = $directory;
			}
		}

		/**
		 * @param string $slug
		 */
		public function setSlug($slug) {
			$this->slug = $slug;
		}
	}

endif;
