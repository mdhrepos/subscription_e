<?php

defined( 'ABSPATH' ) || exit ;

/**
 * Parse about any English textual datetime description into a Unix timestamp
 * 
 * @param string $date
 * @param int|string $base
 * @return int
 */
function _enr_maybe_strtotime( $date, $base = null ) {
	if ( ! $date ) {
		return time() ;
	}

	if ( is_numeric( $date ) ) {
		return absint( $date ) ;
	}

	if ( is_string( $date ) ) {
		if ( $base ) {
			$base = _enr_maybe_strtotime( $base ) ;
		}

		return $base ? strtotime( "$date UTC", $base ) : strtotime( "$date UTC" ) ;
	}
	return time() ;
}

/**
 * Get the time formatted in GMT/UTC 0 or +/- offset
 * 
 * @param string $type Type of time to retrieve. Accepts 'mysql', 'timestamp', or PHP date format string (e.g. 'Y-m-d').
 * @param array $args Accepted values are [
 *              'time' => Optional. A valid date/time string. If null then it returns the current time
 *              'base' => Optional. The timestamp which is used as a base for the calculation of relative dates
 *              ]
 * @return mixed
 */
function _enr_get_time( $type = 'mysql', $args = array() ) {
	$args = wp_parse_args( $args, array(
		'time' => null,
		'base' => null,
			) ) ;

	$time      = _enr_maybe_strtotime( $args[ 'time' ], $args[ 'base' ] ) ;
	$date_time = new WC_DateTime( "@{$time}", new DateTimeZone( 'UTC' ) ) ;

	if ( 'timestamp' === $type ) {
		$time = intval( $date_time->getTimestamp() ) ;
	} else if ( 'mysql' === $type ) {
		$time = $date_time->format( 'Y-m-d H:i:s' ) ;
	} else {
		$time = $date_time->format( $type ) ;
	}

	return $time ;
}

/**
 * Retrieve the array of dates between the given dates.
 * 
 * @param mixed $start_time A valid date/time string
 * @param mixed $end_time A valid date/time string
 * @param array $days_count Should be either array( '1','2','3',... ) or array( ...,'3','2','1' )
 * @return array
 */
function _enr_get_dates( $start_time, $end_time, $days_count ) {
	$dates = array() ;

	if ( empty( $days_count ) || ! is_array( $days_count ) ) {
		return $dates ;
	}

	$start_time = _enr_maybe_strtotime( $start_time ) ;
	$end_time   = _enr_maybe_strtotime( $end_time ) ;
	$sorted_by  = _enr_array_sorted_by( $days_count ) ;

	foreach ( $days_count as $day_count ) {
		$day_count = absint( $day_count ) ;

		if ( $day_count ) {
			if ( count( $days_count ) > 1 && 'asc' === $sorted_by ) {
				$datetime = _enr_get_time( 'timestamp', array( 'time' => "+{$day_count} days", 'base' => $start_time ) ) ;

				if ( $datetime <= $end_time ) {
					$dates[] = $datetime ;
				}
			} else {
				$datetime = _enr_get_time( 'timestamp', array( 'time' => "-{$day_count} days", 'base' => $end_time ) ) ;

				if ( $datetime >= $start_time ) {
					$dates[] = $datetime ;
				}
			}
		}
	}

	if ( $dates ) {
		$dates = array_unique( $dates ) ;
		sort( $dates ) ;
	}

	return $dates ;
}
