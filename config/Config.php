<?php

// Config helpers
$current_directory = dirname(__FILE__);
$root_diretory = dirname($current_directory);

// Config
define('CONFIG', array(
    'log_file_path' => $root_diretory.'/web-eid-authtoken-validation-php.log',
	'nonce_length' => 32
));
