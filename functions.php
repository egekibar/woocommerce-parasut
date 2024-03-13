<?php

define("PLUGIN_DIR_FOR_URL", "/wp-content/plugins/".plugin_basename(__DIR__));

function view($template, $global) {
	$varible = "view_{$template}";
	global $$varible;
	$$varible = $global;
	include "public/view/{$template}.php";
}

function convert_date($datetime, $format = 'd.m.Y') {
	return (new DateTime($datetime))->format($format);
}

function abort($code, $message, $die = true) {
	http_response_code($code);
	if ($die) die($message);
	else return $message;
}