<?php

return [
    'disable' => env('CAPTCHA_DISABLE', false),
    'characters' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
        'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'a', 'b', 'c', 'd',
        'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's',
        't', 'u', 'v', 'w', 'x', 'y', 'z', 0, 1, 2, 3, 4, 5, 6, 7, 8, 9],


    'default' => [
        'length' => 4,
        'width' => 150,
        'height' => 46,
        'quality' => 90,
        'math' => false,
        'expire' => 180,
        'encrypt' => true,
        'fontColors' => ['#2c3e50', '#34495e', '#1a56db', '#7c3aed'],
        'bgColor' => '#f8fafc',
        'lines' => 3,
    ],

    'math' => [
        'length' => 9,
        'width' => 150,
        'height' => 46,
        'quality' => 90,
        'math' => true,
        'expire' => 180,
        'encrypt' => true,
        'fontColors' => ['#2c3e50', '#34495e', '#1a56db', '#7c3aed'],
        'bgColor' => '#f8fafc',
        'lines' => 2,
    ],


    'inverse' => [
        'length' => 5,
        'width' => 150,
        'height' => 46,
        'quality' => 90,
        'sensitive' => true,
        'angle' => 12,
        'sharpen' => 10,
        'blur' => 2,
        'invert' => true,
        'contrast' => -5,
    ],



    'flat' => [
        'length' => 6,
        'fontColors' => ['#2c3e50', '#c0392b', '#16a085', '#c0392b', '#8e44ad', '#303f9f', '#f57c00', '#795548'],
        'width' => 345,
        'height' => 65,
        'math' => false,
        'quality' => 100,
        'lines' => 2,
        'bgImage' => true,
        'bgColor' => '#f8f9fa',
        'contrast' => 0,
    ],

];
