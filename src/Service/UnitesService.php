<?php

namespace Service;

class UnitesService
{
    
    public function __construct()
    {
    }
    
    public function getSize($size = 0)
    {
        if ($size >= 1073741824) {
            $size = number_format($size / 1073741824, 2) . ' Go';
        } elseif ($size >= 1048576) {
            $size = number_format($size / 1048576, 2) . ' Mo';
        } elseif ($size >= 1024) {
            $size = number_format($size / 1024, 2) . ' Ko';
        } elseif ($size > 0) {
            $size = $size . ' o';
        } else {
            $size = '-';
        }

        return $size;
    }
}
