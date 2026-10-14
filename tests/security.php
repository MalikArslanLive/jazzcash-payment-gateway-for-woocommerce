<?php
/** Focused adversarial checks; synthetic data only. */
require __DIR__ . '/run.php';
$security_start = $passed;
$g->settings['mode'] = 'sandbox';
foreach (array("T42RABCDEF12' OR 1=1--", '../../wp-config.php', '<script>alert(1)</script>', 'T42RABCDEF12'.chr(0)) as $payload) {
    rejects(fn() => $g->handle_notification(array('pp_TxnRefNo' => $payload)), 'malicious transaction reference rejected');
}
$o = reset_order();
rejects(fn() => $g->handle_notification(array('pp_TxnRefNo' => $ref, 'pp_SecureHash' => array('x'))), 'array-valued signature rejected');
rejects(fn() => $g->handle_notification(array('pp_TxnRefNo' => $ref, 'pp_X' => str_repeat('x',4097))), 'oversized field rejected');
rejects(fn() => $g->handle_notification(array_fill_keys(array_map(fn($n)=>'pp_X'.$n,range(1,81)), 'x')), 'excessive callback fields rejected');
rejects(fn() => $g->handle_notification(signed(array('pp_RetreivalReferenceNo'=>'<svg/onload=alert(1)>'))), 'signed script in payment reference rejected');
rejects(fn() => $g->handle_notification(signed(array('pp_ResponseCode'=>'<x>'))), 'signed markup in status rejected');
$data=signed(); $data['pp_ResponseMessage']='<?php system($_GET["x"]); ?>';
rejects(fn() => $g->handle_notification($data), 'unsigned executable-text insertion fails authentication');
check($o->complete_count===0, 'attack probes cannot mark order paid');
$message='<script>alert(document.cookie)</script><?php system("id"); ?>';
$g->handle_notification(signed(array('pp_ResponseMessage'=>$message)));
check(!str_contains(implode(' ', $o->notes), $message), 'authenticated provider message never stored in notes');
$soap_base='<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><DoUpdatePaymentStatus xmlns="http://tempuri.org/"><pp_TxnRefNo>'.$ref.'</pp_TxnRefNo></DoUpdatePaymentStatus></s:Body></s:Envelope>';
rejects(fn()=>AGJC_Protocol::parse_soap('<!DOCTYPE x [<!ENTITY a "expansion">]>'.$soap_base), 'internal entity expansion declaration rejected');
rejects(fn()=>AGJC_Protocol::parse_soap(str_replace($ref,'<nested>value</nested>',$soap_base)), 'nested XML injection rejected');
rejects(fn()=>AGJC_Protocol::parse_soap(str_replace('DoUpdatePaymentStatus','RunCommand',$soap_base)), 'arbitrary SOAP operation rejected');
rejects(fn()=>AGJC_Protocol::parse_form('pp_TxnRefNo=x&%70p_TxnRefNo=y'), 'encoded duplicate field rejected');
rejects(fn()=>AGJC_Protocol::parse_form('pp_TxnRefNo%00=x'), 'null-byte field rejected');
$nested=AGJC_Protocol::parse_form('Response='.urlencode('{"pp_TxnRefNo":"'.$ref.'","pp_SecureHash":{"x":"y"}}'));
rejects(fn()=>$g->handle_notification($nested), 'JSON object cannot become executable callback value');
$secret_method=new ReflectionMethod(AGJC_Gateway::class,'secret');$secret_method->setAccessible(true);
$original=$g->settings['sandbox_salt'];$cipher=base64_decode(substr($original,5));$cipher[20]=chr(ord($cipher[20])^1);$g->settings['sandbox_salt']='enc1:'.base64_encode($cipher);
check($secret_method->invoke($g,'sandbox','salt')==='', 'modified encrypted settings fail GCM authentication');
$g->settings['sandbox_salt']=$original;
rejects(fn()=>$g->validate_secret_field('sandbox_salt',array('bad')), 'array credential rejected');
echo 'Focused security checks: '.($passed-$security_start)." passed.\n";
