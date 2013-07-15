<?php
/**
 * AES Encryption with PKCS7 padding http://en.wikipedia.org/wiki/Padding_(cryptography)#PKCS7
 * https://gist.github.com/RiANOl/1077723
 * http://php.net/manual/en/function.mcrypt-encrypt.php
 * http://stackoverflow.com/questions/7448763/proper-php-mcrypt-encryption-methods
 *
 * User: martinhalamicek
 * Date: 7/8/13
 * Time: 3:06 PM
 */

namespace Keboola\Encryption;

class AESEncryptor implements EncryptorInterface
{

	/**
	 * @var Encryption key
	 */
	private $key;

	/**
	 * @var int Mcrypt initialization vector size
	 */
	private $initializationVectorSize;

	/**
	 * @var resource mcrypt module resource
	 */
	private $mcryptModule;

	/**
	 * @var int encryption block size
	 */
	private $blockSize;

	/**
	 * @param $key encryption key should be 16, 24 or 32 characters long form 128, 192, 256 bit encryption
	 */
	public function __construct($key)
	{
		$this->key = $key;
		$this->mcryptModule = mcrypt_module_open('rijndael-128', '', 'cbc', '');
		if ($this->mcryptModule === false) {
			throw new \InvalidArgumentException("Unknown algorithm/mode");
		}

		if (strlen($key) > ($keyMaxLength = mcrypt_enc_get_key_size($this->mcryptModule))) {
			throw new \InvalidArgumentException("The key length must be less or equal than $keyMaxLength.");
		}

		$this->initializationVectorSize = mcrypt_enc_get_iv_size($this->mcryptModule);
		$this->blockSize = mcrypt_enc_get_block_size($this->mcryptModule);
	}

	/**
	 * @param $data
	 * @return string
	 */
	public function encrypt($data)
	{
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($this->mcryptModule), MCRYPT_DEV_RANDOM);
		mcrypt_generic_init($this->mcryptModule, $this->key, $iv);
		$encrypted = mcrypt_generic($this->mcryptModule, $this->pad($data));
		mcrypt_generic_deinit($this->mcryptModule);
		return $iv. $encrypted;
	}

	/**
	 * @param $encryptedData
	 * @return string
	 */
	public function decrypt($encryptedData)
	{
		$initializationVector = substr($encryptedData, 0, $this->initializationVectorSize);
		mcrypt_generic_init($this->mcryptModule, $this->key, $initializationVector);
		$decryptedData = mdecrypt_generic($this->mcryptModule, substr($encryptedData, $this->initializationVectorSize));
		mcrypt_generic_deinit($this->mcryptModule);
		return $this->unpad($decryptedData);
	}

	private function pad($data)
	{
		$pad = $this->blockSize - (strlen($data) % $this->blockSize);
		return $data . str_repeat(chr($pad), $pad);
	}

	private function unpad($data)
	{
		$pad = ord($data[strlen($data) - 1]);
		return substr($data, 0, -$pad);
	}

	public function __destruct()
	{
		mcrypt_module_close($this->mcryptModule);
	}

}