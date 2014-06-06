<?php

class PromotionsEncryption_Plugin extends Promotions_Plugin_Base
{
  
  protected $private_key = false;
  
  /**
   * @wp.action     promotions/init
   */
  public function promotions_init()
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
  
  /**
   * @wp.action   save_post
   */
  public function update_encryption($post_id)
  {
    // If this is just a revision, don't send the email.
    if( wp_is_post_revision( $post_id ) ) return;
    
    if( get_post_type($post_id) !== 'promotion') return;
    if( !($action = @$_POST['encrypt-action']) ) return;
    
    $post = get_post($post_id);
    
    switch( $action ){
      case 'enable':
      case 'change':
        
        $key_size = @$_POST['key_size'];
        if( !$key_size ) $key_size = 1024;
        
        // we are going to create a new key pair
        $config = array(
          "digest_alg" => "sha512",
          "private_key_bits" => $key_size,
          "private_key_type" => OPENSSL_KEYTYPE_RSA,
        );
        $res = openssl_pkey_new($config);
        // Extract the private key from $res to $privKey
        openssl_pkey_export($res, $privKey);
        
        // Extract the public key from $res to $pubKey
        $pubKey = openssl_pkey_get_details($res);
        $pubKey = $pubKey["key"];
        
        // save a temporary file with the private key
        $dir = wp_upload_dir();
        $filename = $dir['path'].'/'.$post->post_name.'.private.key';
        file_put_contents( $filename, $privKey );
        
        // get the current user info
        $current_user = get_userdata( $_REQUEST['encrypt-user'] );
        $subject = 'Important - Private Key for '.$post->post_title;
        $message = implode("\n",array(
          "Hi {$current_user->first_name} {$current_user->last_name}",
          "",
          "You have enabled encryption on the campaign `{$post->post_title}`. It is ".
          "*very important* that you save the attachment in order to retrieve the entries ".
          "for this campaign. If this key is lost, there will be no way to decrypt the ".
          "information in each entry.",
          "",
          "-Promotion Robot"
        ));
        
        // Send an email
        wp_mail($current_user->user_email, $subject, $message, array(
            'BCC: dev@bozuko.com'
        ), $filename );
        
        unlink( $filename );
        
        // Update the public key
        update_post_meta($post_id, 'openssl_public_key', $pubKey);
        update_post_meta($post_id, 'openssl_creator', $current_user->ID);
        update_post_meta($post_id, 'openssl_create_date', time());
        
        // set a flag to let the user know it saved?
        return;
      
      case 'disable':
        update_post_meta($post_id, 'openssl_public_key', '');
        break;
    }
  }
  
  /**
   * @wp.action       promotions/download/form
   */
  public function add_encryption_file_upload( $promotion_id )
  {
    
    if( ($error = Promotions_Flash::get('download_error')) ){
      ?>
      <div class="error">
        <p><?= $error ?></p>
      </div>
      <?php
    }
    
    if( !get_post_meta( $promotion_id, 'openssl_public_key', true) ) return;
    ?>
    <div style="margin: 0 0 10px;">
    <label for="private-key-file"><strong>Please provide the key file</strong></label><br />
    <input type="file" id="private-key-file" name="private_key_file" />
    </div>
    <?php
  }
  
  /**
   * @wp.filter       promotions/download/continue
   */
  public function set_key_file( $value, $promotion_id )
  {
    
    if( !($public_key = get_post_meta( $promotion_id, 'openssl_public_key', true)) )
      return $value;
    
    // check for a key file...
    $key_file = @$_FILES['private_key_file'];
    
    if( !$key_file || !@$key_file['name'] ){
      Promotions_Flash::set('download_error', "You must provide a key file");
      return false;
    }
    
    // validate the key
    $upload_dir = wp_upload_dir();
    $upload_file = $upload_dir['path'].'/'.$key_file['name'];
    
    move_uploaded_file( $key_file['tmp_name'], $upload_file);
    $private_key = file_get_contents( $upload_file );
    unlink( $upload_file );
    
    $verify = 'verify matching private key';
    openssl_public_encrypt($verify, $encrypted, $public_key);
    openssl_private_decrypt($encrypted, $decrypted, $private_key);
    if( $decrypted != $verify ){
      Promotions_Flash::set('download_error', "Invalid key file");
      return false;
    }
    
    $this->private_key = $private_key;
    
    return $value;
    
  }
  
  /**
   * @wp.filter       promotions/registration/meta/save_to_db
   */
  public function encrypt_values_for_db( $value, $name, $form, $post_id )
  {
    $key = get_post_meta($post_id, 'openssl_public_key', true);
    $field = $form->get_field($name);
    if( $key && !$field->get_config('noencrypt') ){
        openssl_public_encrypt($value, $encrypted, $key);
        $value = base64_encode($encrypted);
    }
    return $value;
  }
  
  /**
   * @wp.filter       promotions/registration/meta/fetch_from_db
   */
  public function decrypt_values_from_db( $value, $name, $post_id )
  {
    if( !$this->private_key ) return $value;
    openssl_private_decrypt( base64_decode($value), $value, $this->private_key );
    return $value;
  }
}