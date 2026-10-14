<?php
/** Prepare synthetic callback fixtures in a disposable local WordPress store. */
if (!defined('WP_CLI') || !WP_CLI || wp_get_environment_type() !== 'local') { exit; }
add_filter('pre_wp_mail','__return_false');
$_SERVER['HTTPS']='on';update_option('woocommerce_currency','PKR');
$agjc_fixture=[];
foreach(['sandbox','live'] as $agjc_mode){
 $agjc_g=new AGJC_Gateway();$agjc_settings=['enabled'=>'yes','mode'=>$agjc_mode,'expiry_hours'=>'72','wallet_enabled'=>'yes'];
 foreach(['sandbox','live'] as $agjc_m){
  foreach(['merchant_id'=>'QA-MERCHANT','password'=>'QA-PAYMENT-PASSWORD','salt'=>'QA-INTEGRITY-SALT'] as $agjc_k=>$agjc_v){$agjc_settings[$agjc_m.'_'.$agjc_k]=$agjc_g->validate_secret_field($agjc_m.'_'.$agjc_k,$agjc_v);}
  $agjc_settings[$agjc_m.'_url']='https://'.($agjc_m==='live'?'payments':'sandbox').'.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/';
 }
 update_option('woocommerce_jccg_settings',$agjc_settings,false);
 update_option('woocommerce_jccg_wallet_settings',['enabled'=>'yes'],false);
 foreach(['card','wallet'] as $agjc_instrument){
 $agjc_g=$agjc_instrument==='wallet'?new AGJC_Wallet_Gateway():new AGJC_Gateway();
 foreach(['return','ipn'] as $agjc_type){
  $agjc_o=wc_create_order();$agjc_o->set_payment_method($agjc_g);$agjc_o->set_currency('PKR');$agjc_o->set_total('100.25');$agjc_o->save();
  $_GET['key']=$agjc_o->get_order_key();ob_start();$agjc_g->receipt($agjc_o->get_id());$agjc_html=ob_get_clean();
  $agjc_doc=new DOMDocument();@$agjc_doc->loadHTML($agjc_html);$agjc_data=[];
  foreach($agjc_doc->getElementsByTagName('input') as $agjc_i){$agjc_data[$agjc_i->getAttribute('name')]=$agjc_i->getAttribute('value');}
  if(!isset($agjc_data['pp_TxnRefNo']))throw new RuntimeException('Fixture receipt failed');
  $agjc_data['pp_ResponseCode']='000';$agjc_data['pp_RetreivalReferenceNo']='QA'.$agjc_o->get_id();
  if($agjc_type==='return'){unset($agjc_data['pp_Password']);}else{$agjc_data=array_intersect_key($agjc_data,array_flip(['pp_Version','pp_TxnType','pp_Password','pp_TxnRefNo','pp_TxnDateTime','pp_ResponseCode','pp_RetreivalReferenceNo','pp_SecureHash']));}
  $agjc_data['pp_SecureHash']=AGJC_Protocol::hash($agjc_data,'QA-INTEGRITY-SALT');
  $agjc_wrong=$agjc_data;$agjc_wrong['pp_TxnType']=$agjc_instrument==='wallet'?'MPAY':'MWALLET';$agjc_wrong['pp_SecureHash']=AGJC_Protocol::hash($agjc_wrong,'QA-INTEGRITY-SALT');
  $agjc_fixture[]=['mode'=>$agjc_mode,'instrument'=>$agjc_instrument,'type'=>$agjc_type,'id'=>$agjc_o->get_id(),'data'=>$agjc_data,'wrong_type_data'=>$agjc_wrong];
 }
 }
}
update_option('woocommerce_jccg_wallet_settings',['enabled'=>'no'],false);
$agjc_settings['enabled']='no';$agjc_settings['wallet_enabled']='no';update_option('woocommerce_jccg_settings',$agjc_settings,false);
echo wp_json_encode($agjc_fixture);
