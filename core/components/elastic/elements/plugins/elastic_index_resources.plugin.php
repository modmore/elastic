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

// Reload the resource from the database to get rid of transient data that may have been POSTed
/** @var modResource $reloaded */
$reloaded = $modx->getObject('modResource', $resource->get('id'));
if (!$reloaded) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'Unable to index resource ' . $resource->get('id') . ' to elastic search; failed to reload the resource. ', '', 'elastic_index_resources', __FILE__, __LINE__);
    return;
}

$params = array();
$params['body']  = $elastic->generateResourceBody($reloaded);
$params['index'] = $modx->getOption('elastic.resource_index');
$params['type'] = $modx->getOption('elastic.resource_type');
$params['id'] = $resource->get('id');

try {
    $ret = $client->index($params);
} catch (Exception $e) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'Exception ' . $e->getCode() . ' while indexing resource ' . $resource->get('id') . ': ' . $e->getMessage(), '', 'elastic_index_resources', __FILE__, __LINE__);
}
