<?php
	
	class bouwsoftAPI {
		
		private $clientNr;
		private $token;
		private $refreshToken;
		
		private $api_uri = 'https://Charon.bouwsoft.be/api/v1/Authorize';
		private $server;
		
		public function __construct( $clientnr, $token, $refreshToken, $server ){
			
			$this->setClientNr( $clientnr );
			$this->setToken( $token );
			$this->setRefreshToken( $refreshToken );
			$this->setServer( $server );
		
		}
		
		public function call( $url, $headers=array(), $put=false, $data=null ){
			
			if( empty($headers) ){
				$headers = $this->buildHeaders();
			}
			
			// cURL settings
			$settings = array(
			    CURLOPT_RETURNTRANSFER => 1,
			    CURLOPT_URL => $url,
			    CURLOPT_HTTPHEADER => $headers,
			);
			
			if( $put ){
				$settings[CURLOPT_CUSTOMREQUEST] = 'PUT';
				//$settings[CURLOPT_POSTFIELDS] = $data;
			}
			
			// Get cURL resource
			$curl = curl_init();
			// Set some options - we are passing in a useragent too here
			curl_setopt_array($curl, $settings);
			// Send the request & save response to $resp
			$resp = curl_exec($curl);
			// Close request to clear up some resources
			curl_close($curl);
			
			return json_decode( $resp, true );
			
		}
		
		public function setToken( $token ){
			$this->token = $token;
		}
		
		public function setRefreshToken( $refreshToken ){
			$this->refreshToken = $refreshToken;
		}
		
		public function setClientNr( $clientNr ){
			$this->clientNr = $clientNr;
		}
		
		public function setServer( $server ){
			$this->server = $server;
		}
		
		public function getClientNr(){
			return $this->clientNr;
		}
		
		public function getToken(){
			return $this->token;
		}
		
		public function getRefreshToken(){
			return $this->refreshToken;
		}
		
		public function getServer(){
			return $this->server . '/api/v1';
		}
		
		public function buildHeaders(){
			return array(
				'clientnr: ' . $this->getClientNr(),
				'accesstoken: ' . $this->getToken(),
			);
		}
		
		public function buildRefreshHeaders(){
			return array(
				'clientnr: ' . $this->getClientNr(),
				'refreshtoken: ' . $this->getRefreshToken(),
			);
		}
		
		public function refreshToken(){
			
			$token = $this->call( $this->api_uri . '/AccessToken', $this->buildRefreshHeaders() );
			
			$this->setToken( $token['AccessToken'] );
			
		}
		
		public function getAddresses(){

			$this->refreshToken();
			return $this->call( $this->getServer().'/Addresses' );
			
		}
		
		public function findAddress( $filter, $limit=50 ){
			
			$this->refreshToken();
			return $this->call( $this->getServer()."/Addresses/?filter='".$filter."'&limit=" . $limit );
			
		}
		
		public function createAddress( $fields ){
			
			$this->refreshToken();
			
			$query = array(
				"countrycode='BE'"
			);
			foreach( $fields as $key => $value ){
				if( $key == 'address'){
					$value = preg_replace("/[^a-zA-Z0-9 :]/", "", $value);
				}
				$query[] = $key . "='".$value."'";
			}
			
			return $this->call(
				// rawurlencode before sending
				$this->getServer()."/addresses/?columns=". rawurlencode( implode( ',', $query ) ),
				$this->buildHeaders(),
				true,
				array('columns' => implode(',', $query))
			);
			
		}
		
	}