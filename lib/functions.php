<?php
if (!function_exists('gfa_get')) {
	function gfa_get( $name, $array = null ) {
		if ( ! isset( $array ) ) {
			$array = $_GET;
		}

		if ( ! is_array( $array ) ) {
			if (is_object( $array ))
				$array = (array) $array;
			else
				return '';
		}

		if ( isset( $array[ $name ] ) ) {
			return $array[ $name ];
		}

		return '';
	}
}

if (!function_exists('gfa_post')) {
	function gfa_post( $name, $do_stripslashes = true ) {
		if ( isset( $_POST[ $name ] ) ) {
			return $do_stripslashes ? stripslashes_deep( $_POST[ $name ] ) : $_POST[ $name ];
		}

		return '';
	}
}

if (!function_exists('gfa_ar')) {
	function gfa_ar( $array, $prop, $default = null ) {

		if ( ! is_array( $array ) && ! ( is_object( $array ) && $array instanceof ArrayAccess ) ) {
			return $default;
		}

		if ( isset( $array[ $prop ] ) ) {
			$value = $array[ $prop ];
		} else {
			$value = '';
		}

		return empty( $value ) && $default !== null ? $default : $value;
	}
}

if (!function_exists('gfa_ars')) {
	function gfa_ars( $array, $name, $default = null ) {

		if ( ! is_array( $array ) && ! ( is_object( $array ) && $array instanceof ArrayAccess ) ) {
			return $default;
		}

		$names = explode( '/', $name );
		$val   = $array;
		foreach ( $names as $current_name ) {
			$val = gfa_ar( $val, $current_name, $default );
		}

		return $val;
	}
}

if (!function_exists('gfa_empty')) {
	function gfa_empty( $name, $array = null ) {

		if ( is_array( $name ) ) {
			return empty( $name );
		}

		if ( ! $array ) {
			$array = $_POST;
		}

		$val = gfa_ar( $array, $name );

		return empty( $val );
	}
}

if (!function_exists('gfa_blank')) {
	function gfa_blank( $text ) {
		return empty( $text ) && strval( $text ) != '0';
	}
}