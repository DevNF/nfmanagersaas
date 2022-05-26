<?php
namespace NFService\ManagerSaas;

use Exception;

/**
 * Classe Tools
 *
 * Classe responsável pela comunicação com a API ManagerSaas da Tecnospeed
 *
 * @category  NFService
 * @package   NFService\ManagerSaas\Tools
 * @author    Diego Almeida <diego.feres82 at gmail dot com>
 * @copyright 2022 NFSERVICE
 * @license   https://opensource.org/licenses/MIT MIT
 */
class Tools
{
    /**
     * Variável responsável por armazenar os dados a serem utilizados para comunicação com a API
     * Dados como token, cnpj, ambiente(produção ou homologação)
     *
     * @var array
     */
    private $config = [
        'cnpj' => '',
        'token' => '',
        'grupo' => '',
        'production' => false,
        'debug' => false,
        'upload' => false,
        'decode' => false
    ];

    /**
     * Metodo contrutor da classe
     *
     * @param boolean $isProduction Define se o ambiente é produção
     */
    public function __construct(bool $isProduction = true)
    {
        $this->setProduction($isProduction);
    }

    /**
     * Define se a classe deve se comunicar com API de Produção ou com a API de Homologação
     *
     * @param bool $isProduction Boleano para definir se é produção ou não
     *
     * @access public
     * @return void
     */
    public function setProduction(bool $isProduction) :void
    {
        $this->config['production'] = $isProduction;
    }

    /**
     * Função responsável por setar o CNPJ a ser utilizado na comunicação com a API do ManagerSaas
     *
     * @param string $cnpj CNPJ da SofterHouse
     *
     * @access public
     * @return void
     */
    public function setCnpj(string $cnpj) :void
    {
        $this->config['cnpj'] = $cnpj;
    }

    /**
     * Função responsável por setar o Grupo a ser utilizado na comunicação com a API do ManagerSaas
     *
     * @param string $grupo Grupo do Manager
     *
     * @access public
     * @return void
     */
    public function setGrupo(string $grupo) :void
    {
        $this->config['grupo'] = $grupo;
    }

    /**
     * Função responsável por setar o token a ser utilizada na comunicação com a API do ManagerSaas
     *
     * @param string $token Token de acesso (Token deve ser um base64 no formato base64_encode('usuario:senha'))
     *
     * @access public
     * @return void
     */
    public function setToken(string $token) :void
    {
        $this->config['token'] = $token;
    }

    /**
     * Define se a classe realizará um upload
     *
     * @param bool $isUpload Boleano para definir se é upload ou não
     *
     * @access public
     * @return void
     */
    public function setUpload(bool $isUpload) :void
    {
        $this->config['upload'] = $isUpload;
    }

    /**
     * Define se a classe realizará o decode do retorno
     *
     * @param bool $decode Boleano para definir se fa decode ou não
     *
     * @access public
     * @return void
     */
    public function setDecode(bool $decode) :void
    {
        $this->config['decode'] = $decode;
    }

    /**
     * Retorna se o ambiente setado é produção ou não
     *
     *
     * @access public
     * @return bool
     */
    public function getProduction() : bool
    {
        return $this->config['production'];
    }

    /**
     * Recupera o cnpj setado na comunicação com a API
     *
     * @access public
     * @return string
     */
    public function getCnpj() :string
    {
        return $this->config['cnpj'];
    }

    /**
     * Recupera o cnpj do cedente setado na comunicação com a API
     *
     * @access public
     * @return string
     */
    public function getGrupo() :string
    {
        return $this->config['grupo'];
    }

    /**
     * Recupera o token setado na comunicação com a API
     *
     * @access public
     * @return string
     */
    public function getToken() :string
    {
        return $this->config['token'];
    }

    /**
     * Recupera se é upload ou não
     *
     *
     * @access public
     * @return bool
     */
    public function getUpload() : bool
    {
        return $this->config['upload'];
    }

    /**
     * Recupera se faz decode ou não
     *
     *
     * @access public
     * @return bool
     */
    public function getDecode() : bool
    {
        return $this->config['decode'];
    }

    /**
     * Função responsável por definir se está em modo de debug ou não a comunicação com a API
     * Utilizado para pegar informações da requisição
     *
     * @param bool $isDebug Boleano para definir se é produção ou não
     *
     * @access public
     * @return void
     */
    public function setDebug(bool $isDebug) : void
    {
        $this->config['debug'] = $isDebug;
    }

    /**
     * Retorna os cabeçalhos padrão para comunicação com a API
     *
     * @access private
     * @return array
     */
    private function getDefaultHeaders() :array
    {
        $headers = [
            'Authorization: Basic '.$this->config['token'],
        ];

        if (!$this->config['upload']) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        } else {
            $headers[] = 'Content-Type: multipart/form-data';
        }

        return $headers;
    }

    /**
     * Busca as MDF-es no Manager
     *
     * @access public
     * @return array
     */
    public function consultaMDFe(array $params = []): array
    {
        try {
            $dados = $this->get('mdfe/consulta', $params, []);

            $result = explode(',', $dados['body']);

            if ($result[0] != 'EXCEPTION') {
                return $dados;
            }

            throw new \Exception($result[2], 1);
        } catch (Exception $th) {
            throw new \Exception($th->getMessage(), 1);
        }
    }

    /**
     * Emite um MDF-e por xml
     *
     * @param string $xml String da XML
     *
     * @access public
     * @return array
     */
    public function emiteMDFeXML(string $xml, array $params = [])
    {
        try {
            $params = array_filter($params, function($item) {
                return $item['name'] !== 'Arquivo';
            }, ARRAY_FILTER_USE_BOTH);

            $params[] = [
                'name' => 'Arquivo',
                'value' => "formato=xml\r\n$xml"
            ];

            $dados = $this->post('mdfe/envia', [], $params);

            $result = explode(',', $dados['body']);

            if ($result[0] != 'EXCEPTION') {
                return $dados;
            }

            throw new \Exception($result[2], 1);
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage(), 1);
        }
    }

    /**
     * Realiza o encerramento de um MDFe
     *
     * @param array $params Parametro para a requisição
     *
     * @access public
     * @return array
     */
    public function encerraMDFe(array $params)
    {
        try {
            $dados = $this->post('mdfe/encerra', [], $params);

            $result = explode(',', $dados['body']);

            if ($result[0] != 'EXCEPTION') {
                return $dados;
            }

            throw new \Exception($result[2], 1);
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage(), 1);
        }
    }

    /**
     * Realiza o cancelamento de um MDFe
     *
     * @param array $params Parametro para a requisição
     *
     * @access public
     * @return array
     */
    public function cancelaMDFe(array $params)
    {
        try {
            $dados = $this->post('mdfe/cancela', [], $params);

            $result = explode(',', $dados['body']);

            if ($result[0] != 'EXCEPTION') {
                return $dados;
            }

            throw new \Exception($result[2], 1);
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage(), 1);
        }
    }

    /**
     * Realiza o descarte de um MDFe
     *
     * @param array $params Parametro para a requisição
     * @param string $chave Chave da MDF-e
     *
     * @access public
     * @return array
     */
    public function descartaMDFe(string $chave, array $params = [])
    {
        try {
            $params = array_filter($params, function($item) {
                return $item['name'] !== 'ChaveNota';
            }, ARRAY_FILTER_USE_BOTH);

            $params[] = [
                'name' => 'ChaveNota',
                'value' => $chave
            ];

            $dados = $this->post('mdfe/descarta', [], $params);

            $result = explode(',', $dados['body']);

            if ($result[0] != 'EXCEPTION') {
                return $dados;
            }

            throw new \Exception($result[2], 1);
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage(), 1);
        }
    }

    /**
     * Faz a busca do XML de um MDFe
     *
     * @param string $chave Chave do MDFe
     * @param int $type Tipo do XML (1 - Autorização, 2 - Encerramento ou 3 - Cancelamento)
     * @param array $params Parametro adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function buscaXML(string $chave, int $type = 1, array $params = [])
    {
        try {
            $params = array_filter($params, function($item) {
                return !in_array($item['name'], ['ChaveNota', 'Documento']);
            }, ARRAY_FILTER_USE_BOTH);

            $params[] = [
                'name' => 'ChaveNota',
                'value' => $chave
            ];

            if (in_array($type, [2, 3])) {
                $params[] = [
                    'name' => 'Documento',
                    'value' => $type === 2 ? 'Encerramento' : 'Cancelamento'
                ];
            }

            $dados = $this->get('mdfe/xml', $params);

            $result = explode(',', $dados['body']);

            if ($result[0] != 'EXCEPTION') {
                return $dados;
            }

            throw new \Exception($result[2], 1);
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage(), 1);
        }
    }


    /**
     * Execute a GET Request
     *
     * @param string $path
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     * @return array
     */
    private function get(string $path, array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders()
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a POST Request
     *
     * @param string $path
     * @param string $body
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     * @return array
     */
    private function post(string $path, array $body = [], array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => !$this->config['upload'] ? $body : $this->convertToFormData($body),
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders()
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a PUT Request
     *
     * @param string $path
     * @param string $body
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     * @return array
     */
    private function put(string $path, array $body = [], array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders(),
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => $body
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a DELETE Request
     *
     * @param string $path
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     * @return array
     */
    private function delete(string $path, array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders(),
            CURLOPT_CUSTOMREQUEST => "DELETE"
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a OPTION Request
     *
     * @param string $path
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     * @return array
     */
    private function options(string $path, array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_CUSTOMREQUEST => "OPTIONS"
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Função responsável por realizar a requisição e devolver os dados
     *
     * @param string $path Rota a ser acessada
     * @param array $opts Opções do CURL
     * @param array $params Parametros query a serem passados para requisição
     *
     * @access private
     * @return array
     */
    private function execute(string $path, array $opts = [], array $params = []) :array
    {
        if (!preg_match("/^\//", $path)) {
            $path = '/' . $path;
        }

        $url = 'https://managersaas.tecnospeed.com.br:8081/ManagerAPIWeb';
        if (!$this->config['production']) {
            $url = 'https://managersaashom.tecnospeed.com.br:7071/ManagerAPIWeb';
        }
        $url .= $path;

        $curlC = curl_init();

        if (!empty($opts)) {
            curl_setopt_array($curlC, $opts);
        }

        $params = array_filter($params, function($item) {
            return !in_array($item['name'], ['Grupo', 'CNPJ']);
        }, ARRAY_FILTER_USE_BOTH);

        $params[] = [
            'name' => 'Grupo',
            'value' => $this->config['grupo']
        ];

        $params[] = [
            'name' => 'CNPJ',
            'value' => $this->config['cnpj']
        ];

        if (!empty($params)) {
            $paramsJoined = [];

            foreach ($params as $param) {
                if (isset($param['name']) && !empty($param['name']) && isset($param['value']) && !empty($param['value'])) {
                    $paramsJoined[] = urlencode($param['name'])."=".urlencode($param['value']);
                }
            }

            if (!empty($paramsJoined)) {
                $params = '?'.implode('&', $paramsJoined);
                $url = $url.$params;
            }
        }

        curl_setopt($curlC, CURLOPT_URL, $url);
        curl_setopt($curlC, CURLOPT_RETURNTRANSFER, true);
        if (!empty($dados)) {
            curl_setopt($curlC, CURLOPT_POSTFIELDS, $dados);
        }
        $retorno = curl_exec($curlC);
        $info = curl_getinfo($curlC);
        $return["body"] = ($this->config['decode'] || !$this->config['decode'] && $info['http_code'] != '200') ? json_decode($retorno) : $retorno;
        $return["httpCode"] = curl_getinfo($curlC, CURLINFO_HTTP_CODE);
        if ($this->config['debug']) {
            $return['info'] = curl_getinfo($curlC);
        }
        curl_close($curlC);

        return $return;
    }

    /**
     * Função responsável por montar o corpo de uma requisição no formato aceito pelo FormData
     */
    private function convertToFormData($data)
    {
        $dados = [];

        $recursive = false;
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                $dados[$key] = $value;
            } else {
                foreach ($value as $subkey => $subvalue) {
                    $dados[$key.'['.$subkey.']'] = $subvalue;

                    if (is_array($subvalue)) {
                        $recursive = true;
                    }
                }
            }
        }

        if ($recursive) {
            return $this->convertToFormData($dados);
        }

        return $dados;
    }
}
