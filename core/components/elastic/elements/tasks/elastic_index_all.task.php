<?php
/**
 * @var modX $modx
 * @var array $scriptProperties
 * @var modResource $resource
 * @var sTask $task
 * @var sTaskRun $run
 */
set_time_limit(0);

$task->schedule(time() - 60);

$path = $modx->getOption('elastic.core_path', null, $modx->getOption('core_path') . 'components/elastic/');
$elastic = $modx->getService('elastic', 'elastic.ElasticService', $path . 'model/');

if (!($elastic instanceof ElasticService)) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'Unable to load ElasticService class, required for indexing resources. ');
    return;
}

$stime = microtime(true);

$client = $elastic->getClient();

$params = array();
$params['index'] = $modx->getOption('elastic.resource_index');
$params['type'] = $modx->getOption('elastic.resource_type');
$params['body'] = [];

$c = $modx->newQuery('modResource');
$c->where(array(
    'deleted' => false,
    'published' => true,
    'searchable' => true,
));

$count = 0;
foreach ($modx->getIterator('modResource', $c) as $resource) {
    $count++;
    $body = $elastic->generateResourceBody($resource);

    $params['body'][] = array(
        'index' => array(
            '_id' => $resource->get('id'),
        )
    );
    $params['body'][] = $body;
}

$responses = [];
try {
    $responses = $client->bulk($params);
}catch (Exception $e) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'ERROR: ' . $e->getMessage());
    $run->addError('exception', array(
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
    ));
}

$ttime = microtime(true) - $stime;
$ttime = number_format($ttime, 4);
return 'Indexing ' . $count . ' resources took ' . $ttime . 's. Response: ' . print_r($responses, true);
