<?php
/**
 * OpenSSL.php
 *
 * The main configuration file for mysql_websocket_chat
 *
 * PHP version 7
 *
 * @category Security
 * @package  Mysql_Websocket_Chat
 * @author   Johnny Mast <mastjohnny@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     https://github.com/johnnymast/mysql_websocket_chat
 * @since    GIT:1.0
 */

namespace JM\WebsocketChat\SSL;

/**
 * Class OpenSSL
 *
 * The main Chat controller for mysql_websocket_chat
 *
 * PHP version 7.2
 *
 * @category Security
 * @package  Mysql_Websocket_Chat
 * @author   Johnny Mast <mastjohnny@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     https://github.com/johnnymast/mysql_websocket_chat
 * @since    GIT:1.0
 */
class OpenSSL
{
    /**
     * This will hold the list of domains to
     * create a certificate bundle for.
     *
     * @var array
     */
    protected $domains = [];

    /**
     * This will contain the configuration options.
     *
     * @var array
     */
    protected $config = [];

    /**
     * This information will be added to the certificate.
     *
     * @var array
     */
    private $certInfo;

    /**
     * OpenSSL constructor.
     *
     * @param array $domains  An array of domains to create a cert for
     * @param array $certinfo Information about the certificate
     * @param array $config   Configuration for the certificate.
     */
    public function __construct($domains = [], $certinfo = [], $config = [])
    {
        $this->domains = $domains;
        $this->certInfo = $certinfo;
        $this->config = $config;
    }

    /**
     * Write the temporary configuration file we use to create the new
     * certificate bundle.
     *
     * @return $this
     */
    public function createConfig()
    {
        $content = readFromFile($this->config['OPENSSL_CONFIG_TEMPLATE']);

        foreach ($this->domains as $index => $domain) {
            $num = ($index + 1);
            $content .= "DNS.{$num}={$this->domains[$index]}".PHP_EOL;
        }

        writeToFile($this->config['OPENSSL_CONFIG'], $content);

        return $this;
    }

    /**
     * Create the bundle file.
     *
     * @param bool $debug
     * @return $this
     */
    public function createBundle($debug = false)
    {
        $config = [
          'config' => OPENSSL_CONFIG,
          'digest_alg' => 'sha256',
        ];


        $csrConfig = $config + ['req_extensions' => 'v3_req'];

        $certConfig = $config + ['x509_extensions' => 'usr_cert'];

        $privateKey = openssl_pkey_new();

        $dn = [
          "countryName" => "NL",
          "stateOrProvinceName" => "North Holland",
          "localityName" => "Amsterdam",
          "organizationName" => "Johnny Mast",
          "organizationalUnitName" => "Mysql Websocket Chat - Dev team",
          "emailAddress" => "mastjohnny@gmail.com",
        ];

        $csr = openssl_csr_new($dn, $privateKey, $csrConfig);

        $certificate = openssl_csr_sign(
            $csr,
            $this->config['CA_CERT'],
            [$this->config['CA_KEY'], $this->config['CA_PASSPHRASE']],
            1825,
            $certConfig,
            rand(0, 10000)
        );

        $pem = [];
        openssl_csr_export($csr, $pem[0]);
        openssl_x509_export($certificate, $pem[1]);
        openssl_pkey_export($privateKey, $pem[2]);
        $pem = implode($pem);

        if ($debug) {
            while (($e = openssl_error_string()) !== false) {
                echo $e."\n";
            }
        }

        writeToFile($this->config['PEM_FILE'], $pem);

        return $this;
    }

    /**
     * Remove the create configuration file.
     *
     * @return $this
     */
    public function cleanUp()
    {
        unlink($this->config['OPENSSL_CONFIG']);
        return $this;
    }
}