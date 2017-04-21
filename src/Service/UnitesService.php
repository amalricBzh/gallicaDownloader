<?php

namespace Service;

class UnitesService {

	public function __construct() {
	}

	/** Convertit une taille en octet en une chaîne lisible par un humain */
	public function getSize( $size = 0 ) {
		$txt = '-';
		if ( $size >= 1073741824 ) {
			$txt = number_format( $size / 1073741824, 2 ) . ' Go';
		} elseif ( $size >= 1048576 ) {
			$txt = number_format( $size / 1048576, 2 ) . ' Mo';
		} elseif ( $size >= 1024 ) {
			$txt = number_format( $size / 1024, 2 ) . ' Ko';
		} elseif ( $size > 0 ) {
			$txt = $size . ' o';
		}

		return $txt;
	}

	// Duration in s
	public function getTime( $duration = 0 ) {
		$res      = '';
		$heures   = floor( $duration / 3600 );
		$minutes  = floor( $duration / 60 % 60 );
		$secondes = floor( $duration % 60 );
		if ( $heures > 0 ) {
			$res .= ' ' . $heures . ' heure';
			if ( $heures > 1 ) {
				$res .= 's';
			}
		}
		if ( $minutes > 0 ) {
			$res .= ' ' . $minutes . ' minute';
			if ( $minutes > 1 ) {
				$res .= 's';
			}
		}
		if ( $secondes > 0 ) {
			$res .= ' ' . $secondes . ' seconde';
			if ( $secondes > 1 ) {
				$res .= 's';
			}
		}

		if ( $res === '' ) {
			$res = '0 seconde';
		}

		return trim( $res );
	}
}
