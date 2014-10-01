<?php
namespace NamelessCoder\TYPO3RepositoryClient;

/**
 * Class Connection
 */
class Connection {

	const SOAP_RETURN_CODE = 'resultCode';
	const SOAP_CODE_SUCCESS = 10504;
	const WSDL_URL = 'http://typo3.org/wsdl/tx_ter_wsdl.php';
	const FUNCTION_UPLOAD = 'uploadExtension';

	/**
	 * @var string
	 */
	protected $wsdl = NULL;

	/**
	 * @param string $wsdl
	 */
	function __construct($wsdl = self::WSDL_URL) {
		$this->wsdl = $wsdl;
	}

	/**
	 * @param string $function
	 * @param array $parameters
	 * @param string $username
	 * @param string $password
	 * @return array|boolean
	 * @throws \SoapFault
	 */
	public function call($function, array $parameters, $username, $password) {
		$client = $this->getSoapClientForWsdl($this->wsdl);
		$header = $this->getAuthenticationHeader($username, $password);
		$result = $client->__soapCall($function, $parameters, NULL, $header);
		if (TRUE === $result instanceof \SoapFault) {
			throw $result;
		}
		$output = $this->convert($output);
		if (FALSE === isset($output[self::SOAP_RETURN_CODE])) {
			throw new \RuntimeException('TER command "' . $function . '" failed without a return code');
		}
		if (self::SOAP_CODE_SUCCESS !== (integer) $output[self::SOAP_RETURN_CODE]) {
			throw new \RuntimeException('TER command "' . $function . '" failed; code was ' . $output[self::SOAP_RETURN_CODE]);
		}
		return $output;
	}

	/**
	 * @param string $username
	 * @param string $password
	 * @return \SoapHeader
	 */
	protected function getAuthenticationHeader($username, $password) {
		return new \SoapHeader('', 'HeaderLogin', (object) array('username' => $username, 'password' => $password), TRUE);
	}

	/**
	 * @param string $wsdl
	 * @return \SoapClient
	 */
	protected function getSoapClientForWsdl($wsdl) {
		return new \SoapClient($wsdl);
	}

	/**
	 * Convert object to array
	 *
	 * @param	object	$object
	 * @return	array
	 */
	function convert($object) {
		$array = (array) $object;
		foreach ($array as $key => $value) {
			if (TRUE === is_object($object) || TRUE === is_array($object)) {
				$value = $this->convert($value);
			}
			$array[$key] = $value;
		}
		return $array;
	}

}
