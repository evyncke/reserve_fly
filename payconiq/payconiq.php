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
	public $QRCodeURI ;

	function __construct() {
		global $pcnq_api_key, $pcnq_merchant_id, $pcnq_endpoint ;

		$this->api_key = $pcnq_api_key ;
		$this->merchant_id = $pcnq_merchant_id ;
		$this->endpoint = $pcnq_endpoint ;
	}

	function receivePayment($amount, $description, $reference, $callbackUrl = NULL) {
		global $mysqli_link, $table_payconiq, $userId ;


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

		mysqli_query($mysqli_link, "INSERT INTO $table_payconiq(payment_id, jom_id, status, created_at, description, reference, amount, response)
			VALUES('$this->paymentId', $userId, '" . $this->response['status'] . "', '" . $this->response['createdAt'] . "', 
				'$description', '$reference', $amount, '" . json_encode($this->response) . "')") 
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

	private function getEndpoint($route = null) {
		return $this->endpoint . $route;
	}

    private function cURL($method, $url, $headers=[], $parameters=[]) {
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