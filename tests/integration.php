<?php
/** Run only on a disposable WordPress test store: wp eval-file tests/integration.php */
if (!defined('WP_CLI') || !WP_CLI || wp_get_environment_type() !== 'local') { exit; }
add_filter('pre_wp_mail', '__return_false');
global $agjc_passed;
$agjc_passed = 0;
function agjc_assert($ok, $label) { global $agjc_passed; if (!$ok) { throw new RuntimeException('FAIL: '.$label); } ++$agjc_passed; WP_CLI::log('PASS: '.$label); }
function agjc_reject($fn, $label) { try { $fn(); } catch (Throwable $e) { agjc_assert(true,$label); return; } agjc_assert(false,$label); }
$_SERVER['HTTPS']='on';
wp_set_current_user(1);
update_option('woocommerce_currency','PKR');
update_option('woocommerce_manage_stock','yes');
update_option('woocommerce_hold_stock_minutes','4320');
$agjc_settings=['enabled'=>'yes','mode'=>'sandbox','expiry_hours'=>'72','title'=>'JazzCash Credit / Debit Card','description'=>'Pay by card.','debug'=>'yes'];
$agjc_gateway=new AGJC_Gateway();
foreach(['sandbox','live'] as $agjc_mode) {
 foreach(['merchant_id'=>'QA-MERCHANT','password'=>'QA-PAYMENT-PASSWORD','salt'=>'QA-INTEGRITY-SALT'] as $agjc_name=>$agjc_value) {
  $agjc_settings[$agjc_mode.'_'.$agjc_name]=$agjc_gateway->validate_secret_field($agjc_mode.'_'.$agjc_name,$agjc_value);
 }
 $agjc_settings[$agjc_mode.'_url']='https://'.($agjc_mode==='live'?'payments':'sandbox').'.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/';
}
update_option('woocommerce_jccg_settings',$agjc_settings,false);
update_option('woocommerce_jccg_wallet_settings',['enabled'=>'yes','title'=>'JazzCash Mobile Wallet','description'=>'Pay from your JazzCash wallet.'],false);
foreach(['no','yes'] as $agjc_hpos) {
 $agjc_sync=wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer::class);
 while($agjc_batch=$agjc_sync->get_next_batch_to_process(100)) {$agjc_sync->process_batch($agjc_batch);}
 update_option('woocommerce_custom_orders_table_enabled',$agjc_hpos);
 foreach(['sandbox','live'] as $agjc_mode) {
  $agjc_settings['mode']=$agjc_mode;update_option('woocommerce_jccg_settings',$agjc_settings,false);
  foreach(['card','wallet'] as $agjc_instrument) {
  $agjc_gateway=$agjc_instrument==='wallet'?new AGJC_Wallet_Gateway():new AGJC_Gateway();
  agjc_assert($agjc_gateway->is_available(),"$agjc_mode $agjc_instrument gateway available (HPOS $agjc_hpos)");
  $agjc_product=new WC_Product_Simple();$agjc_product->set_name('Disposable QA product');$agjc_product->set_regular_price('100.25');$agjc_product->set_manage_stock(true);$agjc_product->set_stock_quantity(10);$agjc_product->save();
  $agjc_order=wc_create_order();$agjc_order->set_payment_method($agjc_gateway);$agjc_order->set_currency('PKR');$agjc_order->add_product($agjc_product,1);$agjc_order->calculate_totals();$agjc_order->save();
  agjc_assert(str_contains($agjc_order->get_data_store()->get_current_class_name(), $agjc_hpos==='yes' ? 'OrdersTableDataStore' : 'WC_Order_Data_Store_CPT'), 'expected authoritative order data store active');
  $agjc_id=$agjc_order->get_id();
  $agjc_process=$agjc_gateway->process_payment($agjc_id);
  agjc_assert($agjc_process['result']==='success' && str_contains($agjc_process['redirect'],'key='),'checkout produces authenticated pay link');
  $_GET['key']=$agjc_order->get_order_key();ob_start();$agjc_gateway->receipt($agjc_id);$agjc_html=ob_get_clean();
  $agjc_doc=new DOMDocument();@$agjc_doc->loadHTML($agjc_html);$agjc_fields=[];
  foreach($agjc_doc->getElementsByTagName('input') as $agjc_input) {$agjc_fields[$agjc_input->getAttribute('name')]=$agjc_input->getAttribute('value');}
  agjc_assert(isset($agjc_fields['pp_SecureHash']) && AGJC_Protocol::verify($agjc_fields,'QA-INTEGRITY-SALT'),'real receipt emits verifiable request');
  agjc_assert($agjc_fields['pp_Amount']==='10025' && $agjc_fields['pp_TxnType']===($agjc_instrument==='wallet'?'MWALLET':'MPAY'),'real receipt uses paisa and saved instrument');
  agjc_assert(!str_contains($agjc_html,'QA-INTEGRITY-SALT'),'integrity salt absent from receipt');
  if($agjc_instrument==='wallet'){
   agjc_assert($agjc_fields['pp_Version']==='1.1' && $agjc_fields['pp_BankID']==='TBANK' && $agjc_fields['pp_ProductID']==='RETL','wallet receipt matches official hosted profile');
   agjc_assert(!isset($agjc_fields['pp_IsRegisteredCustomer']) && !isset($agjc_fields['pp_MobileNumber']) && !isset($agjc_fields['pp_CNIC']) && !isset($agjc_fields['pp_MPIN']),'wallet credentials and identity are collected only by JazzCash');
   $agjc_pending=wc_get_order($agjc_id);
   agjc_assert($agjc_pending->get_meta('_jccg_attempts')[$agjc_fields['pp_TxnRefNo']]['instrument']==='wallet','wallet instrument is saved before redirect');
  }
  $agjc_start=DateTimeImmutable::createFromFormat('YmdHis',$agjc_fields['pp_TxnDateTime'],new DateTimeZone('Asia/Karachi'));
  $agjc_end=DateTimeImmutable::createFromFormat('YmdHis',$agjc_fields['pp_TxnExpiryDateTime'],new DateTimeZone('Asia/Karachi'));
  agjc_assert($agjc_end->getTimestamp()-$agjc_start->getTimestamp()===259200,'receipt expiry is 72 hours');
  ob_start();$agjc_gateway->receipt($agjc_id);$agjc_again=ob_get_clean();
  agjc_assert(str_contains($agjc_again,$agjc_fields['pp_TxnRefNo']),'reload reuses same attempt');
  $agjc_response=$agjc_fields;unset($agjc_response['pp_Password']);$agjc_response['pp_ResponseCode']='000';$agjc_response['pp_RetreivalReferenceNo']='QA'.$agjc_id;$agjc_response['pp_SecureHash']=AGJC_Protocol::hash($agjc_response,'QA-INTEGRITY-SALT');
  $agjc_tampered=$agjc_response;$agjc_tampered['pp_Amount']='1';
  agjc_reject(fn()=>$agjc_gateway->handle_notification($agjc_tampered),'forged payment rejected on real order');
  $agjc_gateway->handle_notification($agjc_response);
  $agjc_order=wc_get_order($agjc_id);agjc_assert($agjc_order->is_paid(),'valid signed success pays real order');
  $agjc_stock=wc_get_product($agjc_product->get_id())->get_stock_quantity();agjc_assert($agjc_stock===9,'stock reduced once');
  $agjc_gateway->handle_notification($agjc_response);
  agjc_assert(wc_get_product($agjc_product->get_id())->get_stock_quantity()===9,'duplicate success does not reduce stock again');
  $agjc_response['pp_ResponseCode']='199';$agjc_response['pp_SecureHash']=AGJC_Protocol::hash($agjc_response,'QA-INTEGRITY-SALT');$agjc_gateway->handle_notification($agjc_response);
  agjc_assert(wc_get_order($agjc_id)->is_paid(),'failure cannot downgrade real paid order');
  $_GET['key']='bad';ob_start();$agjc_gateway->receipt($agjc_id);$agjc_denied=ob_get_clean();agjc_assert(!str_contains($agjc_denied,'pp_Password'),'invalid order key exposes no payment form');
  }
 }
}
$agjc_gateway=new AGJC_Gateway();ob_start();$agjc_gateway->admin_options();$agjc_admin=ob_get_clean();
agjc_assert(!str_contains($agjc_admin,'QA-PAYMENT-PASSWORD') && !str_contains($agjc_admin,'QA-INTEGRITY-SALT'),'admin never renders saved credentials');
wp_set_current_user(0);agjc_assert($agjc_gateway->process_admin_options()===false,'unauthorized settings update rejected');wp_set_current_user(1);
$agjc_gateway->settings['live_url']='https://payments.jazzcash.com.pk.evil.test/path';agjc_assert(!$agjc_gateway->ready(),'lookalike endpoint rejected');
$agjc_gateway->settings['live_url']='http://payments.jazzcash.com.pk/path';agjc_assert(!$agjc_gateway->ready(),'HTTP endpoint rejected');
$agjc_gateway->settings['live_url']='https://user:pass@payments.jazzcash.com.pk/path';agjc_assert(!$agjc_gateway->ready(),'userinfo endpoint rejected');
$agjc_gateway->settings['live_url']='https://payments.jazzcash.com.pk:8443/path';agjc_assert(!$agjc_gateway->ready(),'alternate endpoint port rejected');
$agjc_gateway->settings['live_url']=$agjc_settings['live_url'];$_SERVER['HTTPS']='off';agjc_assert(!$agjc_gateway->ready(),'HTTP checkout unavailable');$_SERVER['HTTPS']='on';
update_option('woocommerce_currency','USD');agjc_assert(!$agjc_gateway->ready(),'non-PKR store unavailable');update_option('woocommerce_currency','PKR');
if(class_exists('AGJC_Blocks')) {
 $agjc_block=new AGJC_Blocks();$agjc_block->initialize();$agjc_block_data=$agjc_block->get_payment_method_data();
 agjc_assert(!isset($agjc_block_data['password']) && !isset($agjc_block_data['salt']),'Blocks public settings contain no credentials');
 agjc_assert(in_array('jccg-blocks',$agjc_block->get_payment_method_script_handles(),true),'Blocks script registered');
}
global $wpdb;
$agjc_lock_method=new ReflectionMethod(AGJC_Gateway::class,'lock');$agjc_lock_method->setAccessible(true);
$agjc_unlock_method=new ReflectionMethod(AGJC_Gateway::class,'unlock');$agjc_unlock_method->setAccessible(true);
$agjc_lock_method->invoke($agjc_gateway,987654321);
$agjc_db2=new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);
$agjc_lock_name='jccg_'.md5($wpdb->prefix.':987654321');
agjc_assert((string)$agjc_db2->get_var($agjc_db2->prepare('SELECT GET_LOCK(%s, 0)',$agjc_lock_name))==='0','another database connection cannot acquire held order mutex');
$agjc_unlock_method->invoke($agjc_gateway,987654321);
agjc_assert((string)$agjc_db2->get_var($agjc_db2->prepare('SELECT GET_LOCK(%s, 0)',$agjc_lock_name))==='1','order mutex released for next connection');
$agjc_db2->get_var($agjc_db2->prepare('SELECT RELEASE_LOCK(%s)',$agjc_lock_name));$agjc_db2->close();
$agjc_gateway=new AGJC_Gateway();wp_set_current_user(1);
$agjc_die_filter=static function(){return static function(){throw new RuntimeException('Nonce rejected');};};
add_filter('wp_die_handler',$agjc_die_filter);
$_REQUEST['_wpnonce']='invalid';
agjc_reject(fn()=>$agjc_gateway->process_admin_options(),'invalid settings nonce rejected');
remove_filter('wp_die_handler',$agjc_die_filter);
$_REQUEST['_wpnonce']=wp_create_nonce('woocommerce-settings');
$agjc_post=[];
foreach($agjc_gateway->form_fields as $agjc_k=>$agjc_field){
 if($agjc_field['type']==='title')continue;
 if($agjc_field['type']==='checkbox'){if($agjc_gateway->get_option($agjc_k)==='yes'){$agjc_post[$agjc_gateway->get_field_key($agjc_k)]='1';}continue;}
 $agjc_post[$agjc_gateway->get_field_key($agjc_k)]=$agjc_field['type']==='secret'?'':wp_slash($agjc_gateway->get_option($agjc_k));
}
$agjc_new_password="QA'\\&PASSWORD";
$agjc_post[$agjc_gateway->get_field_key('live_password')]=wp_slash($agjc_new_password);
$agjc_gateway->set_post_data($agjc_post);$agjc_gateway->process_admin_options();
$agjc_fresh=new AGJC_Gateway();$agjc_secret_method=new ReflectionMethod(AGJC_Gateway::class,'secret');$agjc_secret_method->setAccessible(true);
agjc_assert($agjc_secret_method->invoke($agjc_fresh,'live','password')===$agjc_new_password,'settings save preserves slashed credential characters');
agjc_assert(!in_array($wpdb->get_var($wpdb->prepare('SELECT autoload FROM '.$wpdb->options.' WHERE option_name=%s','woocommerce_jccg_settings')),['yes','on','auto','auto-on'],true),'credential settings excluded from autoload');
$agjc_guard_order=wc_create_order();$agjc_guard_order->set_payment_method('jccg');$agjc_guard_order->set_currency('PKR');$agjc_guard_order->set_total('100.25');$agjc_guard_order->save();
$agjc_guard=new AGJC_Gateway();
foreach(['', 'wrong-order-key', '../wp-config.php'] as $agjc_bad_key){
 $_GET['key']=$agjc_bad_key;ob_start();$agjc_guard->receipt($agjc_guard_order->get_id());$agjc_denied=ob_get_clean();
 agjc_assert(!str_contains($agjc_denied,'<form') && !wc_get_order($agjc_guard_order->get_id())->get_meta('_jccg_active'),'unauthorized pending-order receipt creates no payment attempt');
}
$agjc_injection='<svg/onload=alert(1)>';
$agjc_guard->settings['live_password']=$agjc_guard->validate_secret_field('live_password',$agjc_injection);
$_GET['key']=$agjc_guard_order->get_order_key();ob_start();$agjc_guard->receipt($agjc_guard_order->get_id());$agjc_escaped=ob_get_clean();
agjc_assert(!str_contains($agjc_escaped,$agjc_injection) && str_contains($agjc_escaped,'&lt;svg/'),'hosted form escapes executable markup in credential attributes');
$agjc_customer=wp_create_user('qa-security-customer-'.wp_generate_password(8,false),'SyntheticTestOnly!'.wp_generate_password(20));
wp_set_current_user($agjc_customer);agjc_assert($agjc_guard->process_admin_options()===false,'ordinary customer cannot change payment settings');
ob_start();$agjc_guard->admin_options();$agjc_customer_admin=ob_get_clean();agjc_assert($agjc_customer_admin==='','ordinary customer cannot render gateway administration');
wp_set_current_user(1);
$agjc_both=$agjc_settings;$agjc_both['enabled']='no';update_option('woocommerce_jccg_settings',$agjc_both,false);
$agjc_wallet_only=new AGJC_Wallet_Gateway();$agjc_card_off=new AGJC_Gateway();
agjc_assert($agjc_wallet_only->is_available() && !$agjc_card_off->is_available(),'wallet can be enabled independently of cards');
$agjc_both['live_wallet_url']='https://attacker.example/path';update_option('woocommerce_jccg_settings',$agjc_both,false);agjc_assert(!$agjc_wallet_only->ready(),'wallet rejects off-domain endpoint');
$agjc_both['live_wallet_url']='http://payments.jazzcash.com.pk/path';update_option('woocommerce_jccg_settings',$agjc_both,false);agjc_assert(!$agjc_wallet_only->ready(),'wallet rejects HTTP endpoint');
$agjc_both['live_wallet_url']='https://user:pass@payments.jazzcash.com.pk/path';update_option('woocommerce_jccg_settings',$agjc_both,false);agjc_assert(!$agjc_wallet_only->ready(),'wallet rejects URL credentials');
$agjc_both['live_wallet_url']='https://payments.jazzcash.com.pk:8443/path';update_option('woocommerce_jccg_settings',$agjc_both,false);agjc_assert(!$agjc_wallet_only->ready(),'wallet rejects alternate endpoint port');
$agjc_both['live_wallet_url']='https://payments.jazzcash.com.pk/merchant-wallet';update_option('woocommerce_jccg_settings',$agjc_both,false);agjc_assert($agjc_wallet_only->ready(),'wallet accepts approved HTTPS origin');
$agjc_wallet_only->update_option('enabled','no');
agjc_assert(get_option('woocommerce_jccg_settings')['enabled']==='no' && get_option('woocommerce_jccg_wallet_settings')['enabled']==='no','wallet settings toggle changes only its own option');
$agjc_wallet_only->update_option('enabled','yes');
agjc_assert(get_option('woocommerce_jccg_settings')['enabled']==='no' && get_option('woocommerce_jccg_wallet_settings')['enabled']==='yes','wallet can re-enable without enabling cards');
$agjc_api=new WP_REST_Request('PUT','/wc/v3/payment_gateways/jccg_wallet');$agjc_api->set_param('enabled',true);$agjc_api->set_param('title','Wallet API title');$agjc_api_result=rest_do_request($agjc_api);
agjc_assert($agjc_api_result->get_status()===200 && get_option('woocommerce_jccg_settings')['enabled']==='no' && get_option('woocommerce_jccg_wallet_settings')['enabled']==='yes','WooCommerce REST wallet update cannot enable the card gateway');
$agjc_api_data=$agjc_api_result->get_data();agjc_assert(!isset($agjc_api_data['settings']['live_salt']) && !isset($agjc_api_data['settings']['live_password']),'wallet REST settings contain no shared credential fields');
wp_set_current_user(0);$agjc_api_denied=rest_do_request($agjc_api);agjc_assert(in_array($agjc_api_denied->get_status(),[401,403],true),'anonymous wallet REST settings update rejected');wp_set_current_user(1);
update_option('woocommerce_jccg_wallet_settings',['enabled'=>'no'],false);agjc_assert(!(new AGJC_Wallet_Gateway())->is_available(),'wallet opt-out removes availability');
update_option('woocommerce_jccg_settings',$agjc_settings,false);
update_option('woocommerce_jccg_wallet_settings',['enabled'=>'yes'],false);
$agjc_switch_card=new AGJC_Gateway();$agjc_switch_wallet=new AGJC_Wallet_Gateway();
$agjc_switch_order=wc_create_order();$agjc_switch_order->set_payment_method('jccg');$agjc_switch_order->set_currency('PKR');$agjc_switch_order->set_total('100.25');$agjc_switch_order->save();
$_GET['key']=$agjc_switch_order->get_order_key();ob_start();$agjc_switch_card->receipt($agjc_switch_order->get_id());ob_end_clean();
$agjc_switch_order=wc_get_order($agjc_switch_order->get_id());$agjc_ref_before=$agjc_switch_order->get_meta('_jccg_active');$agjc_switch_order->set_payment_method('jccg_wallet');$agjc_switch_order->save();
ob_start();$agjc_switch_wallet->receipt($agjc_switch_order->get_id());$agjc_switch_html=ob_get_clean();
agjc_assert(!str_contains($agjc_switch_html,'<form') && wc_get_order($agjc_switch_order->get_id())->get_meta('_jccg_active')===$agjc_ref_before,'switching instrument after an attempt cannot create another charge');
if(class_exists('AGJC_Blocks')){
 WC()->payment_gateways()->init();
 $agjc_wallet_block=new AGJC_Blocks('jccg_wallet');$agjc_wallet_block->initialize();$agjc_wallet_data=$agjc_wallet_block->get_payment_method_data();
 agjc_assert($agjc_wallet_block->get_name()==='jccg_wallet' && $agjc_wallet_data['enabled'],'wallet Blocks method uses separate active ID');
 agjc_assert(!str_contains(wp_json_encode($agjc_wallet_data),'QA-PAYMENT-PASSWORD') && !str_contains(wp_json_encode($agjc_wallet_data),'QA-INTEGRITY-SALT'),'wallet Blocks data exposes no merchant secrets');
}
WP_CLI::success($agjc_passed.' WordPress/WooCommerce integration checks passed. Provider responses were locally signed fixtures, not real JazzCash transactions.');
