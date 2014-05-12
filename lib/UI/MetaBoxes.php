<?php

class PromotionsEncryption_UI_MetaBoxes extends Promotions_UI_MetaBoxes
{
  public function __construct()
  {
    parent::__construct( Promotions_UI_Tabs::get_instance('promotion') );
  }
  
  /**
   * @wp.meta_box
   * @wp.title            Encryption Settings
   * @promotion.tab       encryption
   */
  public function encryption_settings( $post )
  {
    wp_enqueue_script('sweep-encryption', PROMOTIONS_ENCRYPTION_URL.'/assets/javascripts/encryption.js', array('jquery'));
    $key  = get_post_meta( $post->ID, 'openssl_public_key' );
    $user = get_post_meta( $post->ID, 'openssl_creator' );
    if( $user ){
      $user = get_userdata((int)$user);
      $create_date = get_post_meta( $post->ID, 'openssl_create_date' );
    }
    $before = Snap::inst('Promotions_Functions')->is_before_start( $post->ID );
    ?>
  <div class="encryption-box">
    <input type="hidden" name="encrypt-action" value="" />
    <?php
    if( !$before ){
      if( $key ){
        ?>
      <p style="font-weight:bold;">
        Encryption token created <?= date('Y-m-d h:i:s a', (int)$create_date) ?> sent to <?= $user->display_name ?> (<?= $user->user_email ?>) 
      </p>
        <?php
      }
      ?>
      <p>You cannot alter encryption keys or status after a promotion has started.</p>
      <?php
    }
    else{
      if( !$key ){
        ?>
        <p>When you enable encryption, a private key file will be emailed to the email
        associated with your account. All form fields that do not need to be used as an index
        will be encrypted in the database. When you request to download the entries, that key
        will need to be provided, so it should be stored some place safe!</p>
        
        <p>Send key to user:</p>
        <select name="encrypt-user">
          <?php
          foreach( get_users() as $user ){
            ?>
          <option value="<?= $user->ID ?>" <?php if(get_current_user_id() == $user->ID ){ ?>selected<?php } ?>><?= $user->display_name ?> (<?= $user->user_email ?>)</option>
            <?php
          }
          ?>
        </select>
        
        <p><a href="#" class="button button-primary" data-encrypt-action="enable">Enable Encryption</a></p>
        <?php
      }
      else {
        ?>
        <p style="font-weight: bold;">
          Encryption token created <?= date('Y-m-d h:i:s a', (int)$create_date) ?> sent to <?= $user->display_name ?> (<?= $user->user_email ?>) 
        </p>
        <p>
          <a href="#" class="button button-primary" data-encrypt-action="disable">Disable Encryption</a>
        </p>
        <p>
          You can also change the encryption, choose who should receive the key below:
        </p>
        <select name="encrypt-user">
          <?php
          foreach( get_users() as $_user ){
            ?>
          <option value="<?= $_user->ID ?>" <?php if($user->ID == $_user->ID ){ ?>selected<?php } ?>><?= $_user->display_name ?> (<?= $_user->user_email ?>)</option>
            <?php
          }
          ?>
        </select>
        <p>
          <a href="#" class="button button-primary" data-encrypt-action="change" data-email="<?= $user->user_email ?>" data-name="<?= $user->display_name ?>">Change Encryption Key</a>
        </p>
        <?php
      }
    }
    ?>
  </div>
  <?php
  }
}
