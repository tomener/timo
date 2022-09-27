<?php
return [
    'router' => [
        'mode' => 1
    ],
    'url' => [
        'mode' => 2,
    ],
    'default_return_type' => 'json',
    'cache' => [
        'type' => 'File',
        'sub_dir' => 'cache',
        'use_random_dir' => true,
        'path_level' => 2,
    ],
];
