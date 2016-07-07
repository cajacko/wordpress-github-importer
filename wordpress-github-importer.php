<?php
/*
Plugin Name: Wordpress Github Importer
Description: Automatically import github commits into Wordpress
Version:     1.0.0
Author:      Charlie Jackson
Author URI:  https://charliejackson.com
Text Domain: wordpress-github-importer
*/

define('WGI_GITHUB_USER', 'wgi_user');
define('WGI_GITHUB_PASSWORD', 'wgi_password');
define('WGI_WHITELIST', 'wgi_whitelist');
define('WGI_PLUGIN_ID', 'wordpress_github_importer');
define('WGI_PLUGIN_NAME', 'Wordpress Github Importer');
define('WGI_OPTIONS_SECTION', 'wgi_main');
define('WGI_OPTIONS_SLUG', 'wgi_options');
define('WGI_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WGI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WGI_POST_TYPE', 'commit');
define('WGI_HASHTAG_TAX', 'github_repo');
define('WTI_META_COMMIT_SHA', 'commit_sha');
define('WTI_META_BRANCH', 'branch');
define('WGI_CRON', 'wgi_get_commits');
define('WGI_ACTION_LATEST', 'wgi-get-latest-commits');
define('WGI_ACTION_OLDER', 'wgi-get-older-commits');
define('WGI_SCHEDULE', 'five_minutes');
define('WGI_COMMITS_FROM', 'wgi_commits_from');

require_once(WGI_PLUGIN_PATH .'setup.php');
require_once(WGI_PLUGIN_PATH .'options.php');
require_once(WGI_PLUGIN_PATH .'process.php');
