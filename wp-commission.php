<?php
/**
 * Plugin Name: WP Commission
 * Plugin URI:
 * Description: WP Commission is a simple but powerful tool for website owners to enhance commission revenue with Amazon. The team behind WP Commission is dedicated to helping independent creators profit from quality content.
 * Version:     1.0.6
 * Author:      WP Commission LLC
 * Author URI:  https://wpcommission.com/
 * License:     GPLv2+
 * Text Domain: wp-commission
  */

/**
 * Copyright (c) 2017 WP Commission. (email : sku@wpcommission.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

defined('WPINC') or die;

include(dirname(__FILE__) . '/lib/requirements-check.php');

$wp_commission_requirements_check = new WP_Commission_Requirements_Check(array(
	'title' => 'WP Commission',
	'php' => '5.4',
	'wp' => '4.7',
	'file' => __FILE__,
));

if ($wp_commission_requirements_check->passes()) {
	$GLOBALS['WP_COMMISSION_VERSION'] = get_file_data(__FILE__, array(
		'Version' => 'Version'
	) )['Version'];

	// Pull in the plugin classes and initialize
	include(dirname(__FILE__) . '/lib/wp-stack-plugin.php');
	include(dirname(__FILE__) . '/vendor/autoload.php');
	include(dirname(__FILE__) . '/classes/helpers.php');
	include(dirname(__FILE__) . '/classes/WPCommissionClient.php');
	include(dirname(__FILE__) . '/classes/api-handlers.php');
	include(dirname(__FILE__) . '/classes/WPCommissionTransientStorage.php');
	include(dirname(__FILE__) . '/classes/WPCommissionRelaxedPublicCacheStrategy.php');
	include(dirname(__FILE__) . '/classes/plugin.php');
	WP_Commission_Plugin::start(__FILE__);
}

unset($wp_commission_requirements_check);
