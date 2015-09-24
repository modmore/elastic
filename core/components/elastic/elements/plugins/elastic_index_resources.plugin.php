<?php
/**
 * @var modX $modx
 * @var array $scriptProperties
 * @var modResource $resource
 */

$path = $modx->getOption('elastic.core_path', null, $modx->getOption('core_path') . 'components/elastic/');
$elastic = $modx->getService('elastic', 'elastic.ElasticService', $path . 'model/');

if (!($elastic instanceof ElasticService)) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'Unable to load ElasticService class, required for indexing resources. ');
    return;
}

$client = $elastic->getClient();

$indexFields = $modx->getOption('elastic.index_fields');
$indexFields = array_map('trim', explode(',',trim($indexFields)));
$body = $resource->get($indexFields);

$params = array();
$params['body']  = $body;
$params['index'] = $modx->getOption('elastic.resource_index');
$params['type'] = $modx->getOption('elastic.resource_type');
$params['id'] = $resource->get('id');

try {
    $ret = $client->index($params);
} catch (Exception $e) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'ERROR: ' . $e->getMessage());
}
