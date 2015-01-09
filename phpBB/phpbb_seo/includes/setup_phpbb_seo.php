<?php
/**
*
* @package Ultimate SEO URL phpBB SEO
* @version $Id: setup_phpbb_seo.php 327 2011-09-07 07:24:49Z dcz $
* @copyright (c) 2006 - 2010 www.phpbb-seo.com
* @license http://www.opensource.org/licenses/rpl1.5.txt Reciprocal Public License 1.5
*
*/
/**
* @ignore
*/
if (!defined('IN_PHPBB')) {
	exit;
}
/**
* setup_phpbb_seo Class
* www.phpBB-SEO.com
* @package Ultimate SEO URL phpBB SEO
*/
class setup_phpbb_seo {
	/**
	* Do the init
	*/
	function init_phpbb_seo() {
		global $phpEx, $config, $phpbb_root_path;
		// Let's load config and forum urls, mods adding options in the cache file must do it before
		if ($this->check_cache()) {
			foreach($this->cache_config['dynamic_options'] as $optionname => $optionvalue ) {
				if (@is_array($this->cache_config['settings'][$optionname])) {
					$this->seo_opt[$optionname] = array_merge($this->seo_opt[$optionname], $this->cache_config['settings'][$optionname]);
				} elseif ( @isset($this->cache_config['settings'][$optionvalue]) ) {
					$this->seo_opt[$optionvalue] = $this->cache_config['settings'][$optionvalue];
				}
			}
			$this->modrtype = @isset($this->seo_opt['modrtype']) ? $this->seo_opt['modrtype'] : $this->modrtype;
			if ( $this->modrtype > 1 ) { // Load cached URLs
				$this->seo_url['forum'] =& $this->cache_config['forum'];
			}
		}
		// ====> here starts the add-on and custom set up <====

		// ===> Custom url replacements <===
		// Here you can set up custom replacements to be used in title injection.
		// Example : array( 'find' => 'replace')
		//	$this->url_replace = array(
		//		// Purely cosmetic replace
		//		'$' => 'dollar', '€' => 'euro',
		//		'\'s' => 's', // it's => its / mary's => marys ...
		//		// Language specific replace (German example)
		//		'ß' => 'ss',
		//		'Ä' => 'Ae', 'ä' => 'ae',
		//		'Ö' => 'Oe', 'ö' => 'oe',
		//		'Ü' => 'Ue', 'ü' => 'ue',
		//	);

		// ===> Custom values Delimiters, Static parts and Suffixes <===
		// ==> Delimiters <==
		// Can be overridden, requires .htaccess update <=
		// Example :
		//	$this->seo_delim['forum'] = '-mydelim'; // instead of the default "-f"

		// ==> Static parts <==
		// Can be overridden, requires .htaccess update.
		// Example :
		//	$this->seo_static['post'] = 'message'; // instead of the default "post"
		// !! phpBB files must be treated a bit differently !!
		// Example :
		//	$this->seo_static['file'][ATTACHMENT_CATEGORY_QUICKTIME] = 'quicktime'; // instead of the default "qt"
		//	$this->seo_static['file_index'] = 'my_files_virtual_dir'; // instead of the default "resources"

		// ==> Suffixes <==
		// Can be overridden, requires .htaccess update <=
		// Example :
		// 	$this->seo_ext['topic'] = '/'; // instead of the default ".html"

		// ==> Forum redirect <==
		// In case you are using forum id removing and need to edit some forum urls
		// that where already indexed, you can keep track of them ritgh here
		// NOTE :
		// 	This will only allow the zero duplicate to perform the appropriate redirection
		// 	You need the mod for this to work :
		// 		http://www.phpbb-seo.com/en/zero-duplicate/phpbb-seo-zero-duplicate-t1220.html (en)
		// 		http://www.phpbb-seo.com/fr/zero-duplicate/zero-duplicate-phpbb-seo-t1502.html (fr)
		//
		// Example :
		//
		// $this->forum_redirect = array(
		// 	// 'old-url-without-id-nor-suffix' => forum_id,
		// 	'old-forum-url' => 23,
		// 	'another-one' => 32,
		// 	'anoter-version-of-the-same' => 32,
		// );
		//

		// ==> Special for lazy French, others may delete this part
		if ( strpos($config['default_lang'], 'fr') !== false ) {
			$this->seo_static['user'] = 'membre';
			$this->seo_static['group'] = 'groupe';
			$this->seo_static['global_announce'] = 'annonces';
			$this->seo_static['leaders'] = 'equipe';
			$this->seo_static['atopic'] = 'sujets-actifs';
			$this->seo_static['utopic'] = 'sans-reponses';
			$this->seo_static['npost'] = 'nouveaux-messages';
			$this->seo_static['urpost'] = 'non-lu';
			$this->seo_static['file_index'] = 'ressources';
		}
		// <== Special for lazy French, others may delete this part

		// Let's make sure that settings are consistent
		$this->check_config();
	}
	// Here start the add-on methods
}
?>