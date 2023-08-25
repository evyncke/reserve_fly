<?php
/*
   Copyright 2023 Eric Vyncke

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.

*/

class Payconiq {
	private $api_key ;
	private $merchant_id ;
	private $endpoint ;
	public $response ;
	public $callback ;
	public $paymentId ;
	public $reference ;
	public $description ;
	public $amount ;
	public $status ;
	public $QRCodeURI ;
	public $cancelURI ;

	function __construct() {
		global $pcnq_api_key, $pcnq_merchant_id, $pcnq_endpoint ;

		$this->api_key = $pcnq_api_key ;
		$this->merchant_id = $pcnq_merchant_id ;
		$this->endpoint = $pcnq_endpoint ;
	}

	function receivePayment($amount, $description, $reference, $callbackUrl = NULL) {
		global $mysqli_link, $table_payconiq, $userId ;

		$this->description = $description ;
		$this->reference = $reference ;
		$this->amount = $amount ;
		$this->response = $this->curl('POST', $this->getEndpoint('/payments'), [
			'Content-Type: application/json',
			'Cache-Control: no-cache',
			'Authorization: Bearer ' . $this->api_key
		], [
			'amount' => $amount * 100, // Payment is in eurocents
			'currency' => 'EUR',
			'description' => $description,
			'reference' => $reference,
			'callbackUrl' => $callbackUrl
		]);
		$this->paymentId = $this->response['paymentId'] ;
		$this->QRCodeURI = $this->response['_links']['qrcode']['href'] ;
		$this->cancelURI = $this->response['_links']['cancel']['href'] ;

		mysqli_query($mysqli_link, "INSERT INTO $table_payconiq(payment_id, jom_id, status, created_at, description, reference, amount, qr_uri, cancel_uri, response)
			VALUES('$this->paymentId', $userId, '" . $this->response['status'] . "', '" . $this->response['createdAt'] . "', 
				'$description', '$reference', $amount, '" . $this->QRCodeURI . "', '" .
			$this->cancelURI . "', '" . json_encode($this->response) . "')") 
			or journalise($userId, "F", "Cannot insert into $table_payconiq: " . mysqli_error($mysqli_link)) ;
	}

    function processCallback($signature, $body) {
		global $mysqli_link, $table_payconiq ;

		$this->checkSignature($signature, $body) ;
		$this->callback = json_decode($body, true) ;
		mysqli_query($mysqli_link, "UPDATE $table_payconiq
			SET status = '" . $this->callback['status'] . "', paid_at = '" . $this->callback['succeededAt'] . "', 
				debtor_name = '" . $this->callback['debtor']['name'] . "',
				debtor_iban = '" . $this->callback['debtor']['iban'] . "'
			WHERE payment_id = '" . $this->callback['paymentId'] . "'")
			or journalise(0, 'F', "Cannot update $table_payconiq: " . mysqli_error($mysqli_link)) ;
		journalise(0, 'D', "Payconiq callback for paymentId " . $this->callback['paymentId'] . " with status " . $this->callback['status']) ;
	}

	function loadPayment($id) {
		global $userId, $mysqli_link, $table_payconiq ;

		$paymentId = mysqli_real_escape_string($mysqli_link, $id) ;
		$result = mysqli_query($mysqli_link, "SELECT * FROM $table_payconiq WHERE payment_id = '$paymentId'")
        	or journalise($userId, "E", "Cannot read from $table_payconiq: " . mysqli_error($mysqli_link)) ;
    	if (!$result) return false ;
		$row = mysqli_fetch_array($result) ;
		if (!$row) return false ;
		$this->status = $row['status'] ;
		$this->paymentId = $row['payment_id'] ;
		$this->reference = $row['reference'] ;
		$this->description = $row['description'] ;
		$this->amount = $row['amount'] ;
		$this->QRCodeURI = $row['qr_uri'] ;
		$this->cancelURI = $row['cancel_uri'] ;
		return true ;
	}

	function cancel() {
		global $userId ;

		if (!isset($this->cancelURI) or $this->cancelURI == '') {
			journalise($userId, "E", "No cancel URI for payment $this->paymentId") ;
			return false ;
		}
		$response = $this->cURL('DELETE', $this->cancelURI, [
			'Content-Type: application/json',
			'Cache-Control: no-cache',
			'Authorization: Bearer ' . $this->api_key
		], null) ;
		// HTTP response code is normally 204, i.e., 'no content'
		journalise($userId, "I", "Payment of $this->amount reference $this->reference id $this->paymentId is about to be cancelled (using $this->cancelURI)") ;
		return true ;
	}

	private function getEndpoint($route = null) {
		return $this->endpoint . $route;
	}

    private function cURL($method, $url, $headers=[], $parameters=[]) {
		global $userId ; // For journalise()

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_VERBOSE, 0);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT ,20);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($parameters));

        $response = curl_exec($curl);
		if ($response === false) journalise($userId, "E", "cURL request($method, $url) has failed") ;
//		$response_code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE) ;
//		journalise($userId, "D", "cURL($method, $url) response code is $response_code") ;
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $body = substr($response, $header_size);
        curl_close($curl);

        return json_decode($body,true);
    }

	private function checkSignature($sig, $body) {
		journalise(0, "D", "Blindly trusting payconiq signature $sig") ;
	}
}
?>