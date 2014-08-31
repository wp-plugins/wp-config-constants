<?php
/*
Plugin Name: WP Config Constants
Description: Shows you the values of constants defined in your wp-config.php file
Version: 1.0
Author: Chris Taylor
Author URI: http://www.stillbreathing.co.uk
Plugin URI: http://www.stillbreathing.co.uk/wordpress/wp-config-constants/
Date: 2014-08-26
*/

add_action('admin_menu', 'wp_config_constants_menu');

/**
* Adds the WP Config Constants menu option
*
* @since   1.0.0
*/
function wp_config_constants_menu() {
	add_options_page('WP Config Constants', 'WP Config Constants', 'update_core', 'constants', 'wp_config_constants');
}

/**
* Displays the constants
*
* @since   1.0.0
*/
function wp_config_constants() {

	$constants = wp_config_constants_get_constants();
    
	echo '<div class="wrap">
	<h2>' . __('Your wp-config.php file PHP constants', 'wp_config_constants') . '</h2>
	<table class="widefat">
	';
	if ( count( $constants ) == 0 ) {
	    
	    echo '
		<p>' . __('No constants found in your wp-config.php file.', 'wp_config_constants') . '</p>
		';
	    
	} else {
	echo '
	<thead>
		<tr>
			<th>Constant</th>
			<th>Value</th>
			<th>Active</th>
		</tr>
	</thead>
	<tbody>
	';
	foreach( $constants as $constant ) {
	$constant = wp_config_constants_obfuscate_security_constant( $constant );
	echo '
		<tr>
			<td>' . $constant->name . '</td>
			<td>' . $constant->value . '</td>
			<td>' . ( $constant->active === true ? __('Active', 'wp_config_constants') : __('Inactive', 'wp_config_constants') ) . '</td>
		</tr>
		';
		if ( strlen( $constant->line ) > 0 ) {
		echo '
		<tr>
			<td colspan="3"><div class="howto">' . $constant->line . '</div></td>
		</tr>
		';
		}
	}
	echo '
	</tbody>
	</table>
	';
	}
	echo '
	</div>';

}

/**
* Checks if the given constant is one fo the security constants, and if so obfuscates the value
*
* @since   1.0.0
*/
function wp_config_constants_obfuscate_security_constant( $constant ) {

	$security_constants = array( 
		'DB_PASSWORD', 
		'AUTH_KEY', 
		'SECURE_AUTH_KEY', 
		'LOGGED_IN_KEY', 
		'NONCE_KEY', 
		'AUTH_SALT', 
		'SECURE_AUTH_SALT', 
		'LOGGED_IN_SALT',
		'NONCE_SALT'
	);
	
	if ( in_array( $constant->name, $security_constants ) ) {
		$constant->value = "*************";
		$constant->line = "";
	}
	
	return $constant;
	
}

/**
* Gets the location of the wp-config.php file
*
* @since   1.0.0
*/
function wp_config_constants_get_file() {
    
    if ( file_exists( ABSPATH . "wp-config.php" ) ) {
	return ABSPATH . "wp-config.php";
    }
    
    if ( file_exists( ABSPATH . "../wp-config.php" ) ) {
	return ABSPATH . "../wp-config.php";
    }
    
    return "";
    
}

/**
* Extracts the constant definitions from the config file
*
* @since   1.0.0
*/
function wp_config_constants_get_constants() {
    
    $file = wp_config_constants_get_file();
    
    if ( "" == $file ) {
	return [];
    }
    
    $contents = file_get_contents( $file );
    
    if ( "" == $contents ) {
	return [];
    }
    
    $lines = explode( "\n", $contents );
    
    $constants = [];
    foreach( $lines as $line ) {
	
	$define = strpos( $line, 'define(' );
	if ( $define !== false) {
	
	    $constant = wp_config_constants_get_constant( $define, $line );
	    
	    if ( $constant === false ) {
		break;
	    }
	    
	    $constants[] = $constant;
	    
	}
    }
    
    return $constants;
}

/**
* Gets the definition of a constant from a line in the config file
*
* @since   1.0.0
*/
function wp_config_constants_get_constant( $define, $line ) {
    
    $constant = new stdClass();
    $constant->line = $line;

    if ( strpos( $line, "//" ) !== false || strpos( $line, "/*" ) !== false ) {
	$constant->active = false;
    } else {
	$constant->active = true;
    }
    
    $start = $define + 7;
    $close = strrpos( $line, ')' );
    $length = $close - $start;
    $str = substr( $line, $start, $length );
    
    if ( strlen( trim( $str ) ) === 0 ) {
	return false;
    }
    
    $comma = strpos( $str, "," );
    
    if ( $comma === false ) {
	return false;
    }
    
    $namestr = trim( substr( $str, 0, $comma ) );
    $valuestr = trim( substr( $str, $comma + 1 ) );
    
    if ( substr( $namestr, 0, 1 ) == "'" || substr( $namestr, 0, 1 ) == '"' ) {
	$constant->name = substr( $namestr, 1, strlen( $namestr ) - 2 );
    } else {
	$constant->name = $namestr;
    }
    
    if ( substr( $valuestr, 0, 1 ) == "'" || substr( $valuestr, 0, 1 ) == '"' ) {
	$constant->value = substr( $valuestr, 1, strlen( $valuestr ) - 2 );
    } else {
	$constant->value = $valuestr;
    }
    
    return $constant;
    
}
?>