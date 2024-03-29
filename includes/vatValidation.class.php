<?php
class vatValidation
{
	const WSDL = "https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl";
	private $_client = null;
	private $_options  = array(
						'debug' => false,
						);

	private $_failed = false;
	private $_valid = false;
	private $_data = array();
	
	public function __construct($options = array()) {
		
		foreach($options as $option => $value) {
			$this->_options[$option] = $value;
		}
		
		if(!class_exists('SoapClient')) {
			throw new Exception('The Soap library has to be installed and enabled');
		}
				
		try {
			$this->_client = new SoapClient(self::WSDL, array('trace' => true) );
		} catch(Exception $e) {
			$this->trace('Vat Translation Error', $e->getMessage());
		}
	}
	public function check($countryCode, $vatNumber) {
		// ensure previous results are cleared
		$this->_failed = false;
		$this->_valid = false;
		$this->_data = array();

		try {
			// Fix this issue for Greece.
			if ( $countryCode == 'GR' ) {
				$countryCode = 'EL';
			}

			// Strip the country code from the vat number
			$vatNumber = preg_replace( '/^' . $countryCode . '/', '', $vatNumber) ;
			
			$rs = $this->_client->checkVat( array('countryCode' => $countryCode, 'vatNumber' => $vatNumber) );

			$this->trace('Web Service result', $this->_client->__getLastResponse());

				if($rs->valid) {
				$this->_valid = true;
				list($denomination,$name) = explode(" " ,$rs->name,2);
				$this->_data = array(
										'denomination' => 	$denomination, 
										'name' => 			$this->cleanUpString($name), 
										'address' => 		$this->cleanUpString($rs->address),
									);
				return true;
			} else {
				return false;
			}

		} catch(Exception $e) {
			$this->trace( 'Web Service exception', $e->getMessage() );
			$this->_failed = true;
			return false;
		}	
	}

	public function isValid() {
		return $this->_valid;
	}

	public function isFailed() {
		return $this->_failed;
	}

	public function getDenomination() {
		return $this->_data['denomination'];
	}
	
	public function getName() {
		return $this->_data['name'];
	}
	
	public function getAddress() {
		return $this->_data['address'];
	}
	
	public function isDebug() {
		return false !== $this->_options['debug'];
	}
	private function trace($title,$body) {
		if ( $this->isDebug() ) {
			if ( $this->isDebug() ) {
				if ( 'log' === $this->_options['debug'] ) {
					error_log( 'TRACE: ' . $title . "\n" . $body . "\n" );
				} else {
					echo '<h2>TRACE: ' . $title . '</h2><pre>' . htmlentities( $body ) . '</pre>';
				}
			}
		}
	}
	private function cleanUpString($string) {
        for($i=0;$i<100;$i++)
        {               
            $newString = str_replace("  "," ",$string);
            if($newString === $string) {
            	break;
            } else {
            	$string = $newString;
			}
        }
                        
        $newString = "";
        $words = explode(" ",$string);
        foreach($words as $k=>$w)
        {                       
           	$newString .= ucfirst(strtolower($w))." "; 
        }
        return $newString;
	}
}