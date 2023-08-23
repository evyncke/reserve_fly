<?php
require '../dbi.php' ;
require 'payconiq.php' ;

$pcnq = new Payconiq() ;

$amount = $_REQUEST['amount'] ;
$reference = $_REQUEST['reference'] ;
$description = $_REQUEST['description'] ;
$cb = $_REQUEST['cb'] ;

$pcnq->receivePayment($amount, $description, $reference, 'https://www.spa-aviation.be/resa/payconiq/callback.php') ;
?>
<html>
<header>
<title>Payer avec Payconiq</title>
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
Payment amount: <?=$amount?> &euro;<br/>
Payment description: <?=$description?><br/>
Payment reference: <?=$reference?><br/>
QR code URI: <?=$pcnq->QRCodeURI?><br/>
paymentId: <?=$pcnq->paymentId?><br/>
expiresAt: <?=$pcnq->response['expiresAt']?><br/>

<img src="<?=$pcnq->QRCodeURI?>">

<span id="statusSpan">Waiting for status</span>
</body>
</html>
