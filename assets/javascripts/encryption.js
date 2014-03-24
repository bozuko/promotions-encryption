jQuery(function($){
  $(document).on('click', '[data-encrypt-action]', onClick);
  
  function onClick(e){
    e.preventDefault();
    var action = $(this).data('encrypt-action');
    if( promptAction( action ) ){
      $('input[name=encrypt-action]').val( action );
      $('form#post').submit();
    }
  }
  
  function promptAction( action ){
    switch( action ){
      case 'change':
        return confirm('Are you sure you want to change the encryption key?');
      
      case 'disable':
        return confirm('Are you sure you want to disable encryption?');
        
      default:
        return true;
    }
  }
});