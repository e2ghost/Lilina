<?php
/**
 * Functions that work with serialized files
 * @author Ryan McCue <cubegames@gmail.com>
 * @package Lilina
 * @version 1.0
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

defined('LILINA_PATH') or die('Restricted access');

/**
 * lilina_load_feeds() - {{@internal Missing Short Description}}}
 *
 * {{@internal Missing Long Description}}}
 */
function lilina_load_feeds($data_file) {
	$data = file_get_contents($data_file) ;
	$data = unserialize( base64_decode($data) ) ;
	if(!$data || !is_array($data)) {
		$data = array();
	}
	return $data;
}

/**
 * available_templates() - {{@internal Missing Short Description}}}
 *
 * {{@internal Missing Long Description}}}
 */
function available_templates() {
	//Make sure we open it correctly
	if ($handle = opendir(LILINA_INCPATH . '/templates/')) {
		//Go through all entries
		while (false !== ($dir = readdir($handle))) {
			// just skip the reference to current and parent directory
			if ($dir != '.' && $dir != '..') {
				if (is_dir(LILINA_INCPATH . '/templates/' . $dir)) {
					if(file_exists(LILINA_INCPATH . '/templates/' . $dir . '/style.css')) {
						$list[] = LILINA_INCPATH . '/templates/' . $dir . '/style.css';
					}
				} 
			}
		}
		// ALWAYS remember to close what you opened
		closedir($handle);
	}
	foreach($list as $the_template) {
		$temp_data = implode('', file($the_template));
		preg_match("|Name:(.*)|i", $temp_data, $name);
		preg_match("|Real Name:(.*)|i", $temp_data, $real_name);
		preg_match("|Description:(.*)|i", $temp_data, $desc);
		$templates[]	= array(
								'name' => trim($name[1]),
								'real_name' => trim($real_name[1]),
								'description' => trim($desc[1])
								);
	}
	return $templates;
}

/**
 * available_locales() - {{@internal Missing Short Description}}}
 *
 * {{@internal Missing Long Description}}}
 */
function available_locales() {
	$locale_list = array();
	$locales = array();
	//Make sure we open it correctly
	if ($handle = opendir(LILINA_INCPATH . '/locales/')) {
		//Go through all entries
		while (false !== ($file = readdir($handle))) {
			// just skip the reference to current and parent directory
			if ($file != '.' && $file != '..') {
				if (!is_dir(LILINA_INCPATH . '/locales/' . $file)) {
					//Only add plugin files
					if(strpos($file,'.mo') !== FALSE) {
						$locale_list[] = $file;
					}
				}
			}
		}
		// ALWAYS remember to close what you opened
		closedir($handle);
	}
	/** Special case for English */
	$locales[]	= array('name' => 'en',
						'file' => '');
	foreach($locale_list as $locale) {
		echo $locale;
		//Quick and dirty name
		$locales[]	= array('name' => str_replace('.mo', '', $locale),
							'file' => $locale);
	}
	return $locales;
}

/**
 * recursive_array_code() - {{@internal Missing Short Description}}}
 *
 * {{@internal Missing Long Description}}}
 */
function recursive_array_code($vars) {
	global $level_count;
	foreach($vars as $var => $value) {
		if(is_array($value)) {
			$content .= "\n" . str_repeat("\t", $level_count) . 'array(';
			$level_count++;
			$content .= recursive_array_code($value);
		}
		else
			$content .= "\n" . str_repeat("\t", $level_count) . "'$var' => '$value',";
	}
	while($level_count > 1) {
		$level_count--;
		$content .= "\n" . str_repeat("\t", $level_count) . '),';
	}
	return $content;
}

/**
 * save_settings() - {@internal Missing Short Description}}
 *
 *
 */
function save_settings() {
	global $settings, $default_settings;
	$vars = array_diff_assoc_recursive($settings, $default_settings);
	/** We want to ignore these, as they are set in conf.php */
	unset($vars['cachedir'], $vars['files']);
	$content = '<' . '?php';

	global $level_count; $level_count = 1;
	foreach($vars as $var => $value) {
		if(is_array($value)) {
			$content .= "\n\$settings['{$var}'] = array(";
			$content .= recursive_array_code($value);
			$content .= "\n);";
		}
		elseif(is_object($value)) {
			$content .= "\n\$settings['{$var}'] = '" . base64_encode(serialize($value)) . "';";
		}
		else
			$content .= "\n\$settings['{$var}'] = '{$value}';";
	}
	var_dump($content);
}

/**
 * generate_nonce() - Generates nonce
 *
 * Uses the current time
 * @global array Need settings for user and password
 * @param string $nonce Supplied nonce
 * @return bool True if nonce is equal, false if not
 */
function generate_nonce() {
	$user_settings = get_option('auth');
	$time = ceil(time() / 43200);
	return md5($time . get_option('auth', 'user') . get_option('auth', 'pass'));
}

/**
 * check_nonce() - Checks whether supplied nonce matches current nonce
 * @global array Need settings for user and password
 * @param string $nonce Supplied nonce
 * @return bool True if nonce is equal, false if not
 */
function check_nonce($nonce) {
	$user_settings = get_option('auth');
	$time = ceil(time() / 43200);
	$current_nonce = md5($time . get_option('auth', 'user') . get_option('auth', 'pass]'));
	if($nonce !== $current_nonce) {
		return false;
	}
	return true;
}

/**
 * get_temp_dir() - Get a temporary directory to try writing files to
 *
 * {@internal Missing Long Description}}
 * @author WordPress
 */
function get_temp_dir() {
	if ( defined('LILINA_TEMP_DIR') )
		return trailingslashit(LILINA_TEMP_DIR);

	$temp = LILINA_PATH . '/cache';
	if ( is_dir($temp) && is_writable($temp) )
		return $temp;

	if  ( function_exists('sys_get_temp_dir') )
		return trailingslashit(sys_get_temp_dir());

	return '/tmp/';
}
?>