<?php

class PromotionsEncryption_Plugin extends Promotions_Plugin_Base
{
  
  /**
   * @wp.action     promotions/init
   */
  public function init()
  {
    Snap::inst('PromotionsEncryption_UI_MetaBoxes');
  }
  
  /**
   * @wp.filter     promotions/tabs/promotion/register
   * @wp.priority   10
   */
  public function register_tab( $tabs )
  {
    $tabs['encryption'] = 'Encryption';
    return $tabs;
  }
}