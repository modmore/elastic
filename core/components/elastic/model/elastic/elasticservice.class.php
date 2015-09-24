<?php
require dirname(dirname(dirname(__FILE__))) . '/vendor/autoload.php';

use Elasticsearch\Client;

/**
 * Class ElasticService
 *
 * @package modmore\Elastic
 */
class ElasticService {
    public $modx;


    protected $client;

    /**
     * @param \modX $modx
     */
    public function __construct(\modX $modx)
    {
        $this->modx = $modx;
    }

    /**
     * Gets a preconfigured Client instance
     *
     * @return Client
     */
    public function getClient()
    {
        if (!$this->client) {
            $hosts = $this->modx->getOption('elastic.hosts');
            $hosts = array_map('trim', explode(',', trim($hosts)));

            $params = array(
                'hosts' => $hosts,
            );
            $this->client = new Client($params);
        }
        return $this->client;
    }
}