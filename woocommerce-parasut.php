<?php

/*
 * Plugin Name: Woocommerce Paraşüt
 * Plugin URI: https://github.com/egekibar/woocommerce-parasut
 * Description: Woocommerce Parasut entegrasyonu
 * Author: egekibar
 * Author URI: kibar.dev
 * Version: 1.0
 * License: GPL2
*/

defined( 'ABSPATH' ) || exit;

require_once 'bootstrap.php';

\Plugin\Helper\Auth::$config = [ // Alanları doğru bilgilerle doldur
	"company_id" => "",
	"username" => "",
	"password" => "",
	"client_id" => "",
	"client_secret" => ""
];