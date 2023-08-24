<?php
require '../dbi.php' ;
require 'payconiq.php' ;

$pcnq = new Payconiq() ;

$amount = $_REQUEST['amount'] ;
$reference = $_REQUEST['reference'] ;
$description = $_REQUEST['description'] ;
$cb = $_REQUEST['cb'] ;

$pcnq->receivePayment($amount, $description, $reference, 'https://www.spa-aviation.be/resa/payconiq/callback.php') ;
?><!DOCTYPE html>
<html>
<header>
<title>Payer avec Payconiq</title>
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script>
var paymentId = "<?=$pcnq->paymentId?>" ;
statusSpan = false ;

function refreshStatus() {
        var XHR=new XMLHttpRequest();
        XHR.onreadystatechange = function() {
                if(XHR.readyState  == 4) {
                        if(XHR.status  == 200) {
                                console.log("refreshStatus() call-back") ;
                                try {
                                        var status_response = eval('(' + XHR.responseText.trim() + ')') ;
                                } catch(err) {
                                        return ;
                                }
				console.log("New status: " + status_response.status ) ;
				statusSpan.innerHTML = status_response.status ;
				if(status_response.status != 'EXPIRED' && status_response.status != 'SUCCEEDED') {
					setTimeout(refreshStatus, 2 * 1000) ;
                                        statusSpan.innerHTML = '<div class="spinner-border text-black"></div>' + statusSpan.innerHTML ;
					// TODO display a right message and possible redirect to the referer
				}
			} // XHR.status  == 200
		} // XHR.readyState  == 4
		} ;
	XHR.open("GET", 'status.php?paymentId=' + paymentId, false) ;
        // TODO try/catch to handle exceptions
        XHR.send(null) ;
}

function init() {
	statusSpan = document.getElementById('statusSpan') ;
	refreshStatus() ;
}

</script>
</header>
<body onload="init();">
<h1>Payer avec Payconiq</h1>
<p class="lead">Payment amount: <?=$amount?> &euro;<br/>
Payment description: <?=$description?><br/>
Payment reference: <?=$reference?>
</p>
<p class="text-muted">
paymentId: <?=$pcnq->paymentId?><br/>
expiresAt: <?=$pcnq->response['expiresAt']?><br/>
Cancel URI: <a href="<?=$pcnq->cancelURI?>"><?=$pcnq->cancelURI?></a><br/>
QR code URI: <?=$pcnq->QRCodeURI?>
</p>

<img src="<?=$pcnq->QRCodeURI?>">

<div class="bg-info"><span id="statusSpan"><div class="spinner-border text-info"></div> Waiting for status</span></div>
</body>
</html>
