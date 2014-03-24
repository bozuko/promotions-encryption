<?php
/*
Plugin Name: Promotions: Encryption
Plugin URI: http://bozuko.com
Description: Enable encryption for promotions
Version: 1.0.0
Author: Bozuko
Author URI: http://bozuko.com
License: Proprietary
*/

add_action('promotions/plugins/load', function()
{
  define('PROMOTIONS_ENCRYPTION_DIR', dirname(__FILE__));
  define('PROMOTIONS_ENCRYPTION_URI', plugins_url('/', __FILE__));
  
  Snap_Loader::register( 'PromotionsEncryption', PROMOTIONS_ENCRYPTION_DIR . '/lib' );
  Snap::inst('PromotionsEncryption_Plugin');
}, 100);