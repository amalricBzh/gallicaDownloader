<?php

namespace Service;

class UnitesService
{
    
    public function __construct()
    {
    }

    /** Convertit une taille en octet en une chaîne lisible par un humain */
    public function getSize($size = 0)
    {
	    $txt = '-';
        if ($size >= 1073741824) {
	        $txt = number_format($size / 1073741824, 2) . ' Go';
        } elseif ($size >= 1048576) {
	        $txt = number_format($size / 1048576, 2) . ' Mo';
        } elseif ($size >= 1024) {
	        $txt = number_format($size / 1024, 2) . ' Ko';
        } elseif ($size > 0) {
	        $txt = $size . ' o';
        }

        return $txt;
    }
}
