<?php
/**
 * @var modX $modx
 * @var array $scriptProperties
 * @var modResource $resource
 */

$path = $modx->getOption('elastic.core_path', null, $modx->getOption('core_path') . 'components/elastic/');
$elastic = $modx->getService('elastic', 'elastic.ElasticService', $path . 'model/');

if (!($elastic instanceof ElasticService)) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'Unable to load ElasticService class, required for searching resources. ');
    return 'Searching is currently unavailable. Please try again later.';
}


$client = $elastic->getClient();

// properties inspired by SimpleSearch
$docFields = $modx->getOption('docFields', $scriptProperties, 'pagetitle,longtitle,alias,description,introtext,content');
$docFields = array_map('trim', explode(',', $docFields));
$limit = $modx->getOption('limit', $scriptProperties, 10);
$offset = $modx->getOption('offset', $scriptProperties, 0);
$totalVar = $modx->getOption('totalVar', $scriptProperties, 'total');
$ids = $modx->getOption('ids', $scriptProperties, '');
$idType = $modx->getOption('idType', $scriptProperties, 'parents'); // parents or documents
$parentDepth = $modx->getOption('depth', $scriptProperties, 10);
$exclude = $modx->getOption('exclude', $scriptProperties, '');
$contexts = $modx->getOption('contexts', $scriptProperties, $modx->resource->get('context_key'));
$searchIndex = $modx->getOption('searchIndex', $scriptProperties, 'search');
$offsetIndex = $modx->getOption('offsetIndex', $scriptProperties, 'sisea_offset');
$placeholderPrefix = $modx->getOption('placeholderPrefix', $scriptProperties, 'elastic.');
$toPlaceholder = $modx->getOption('toPlaceholder', $scriptProperties, '');
$urlScheme = $modx->getOption('urlScheme', $scriptProperties, $modx->getOption('link_tag_scheme'));
$sortBy = $modx->getOption('sortBy', $scriptProperties, 'relevance', true);
$sortDir = $modx->getOption('sortDir', $scriptProperties, 'DESC');

$noResultsTpl = $modx->getOption('noResultsTpl', $scriptProperties, '');
$resultTpl = $modx->getOption('resultTpl', $scriptProperties, '');
$wrapperTpl = $modx->getOption('wrapperTpl', $scriptProperties, '');

$term = (isset($_REQUEST[$searchIndex])) ? $_REQUEST[$searchIndex] : '';
$term = htmlentities($term, ENT_QUOTES, 'UTF-8');
$modx->setPlaceholder($placeholderPrefix . 'term', $term);
if (!empty($term)) {

    $params = [];
    $params['index'] = $modx->getOption('elastic.resource_index');
    $params['type'] = $modx->getOption('elastic.resource_type');

    $params['body']['query']['filtered']['query']['multi_match']['query'] = $term;
    $params['body']['query']['filtered']['query']['multi_match']['fields'] = $docFields;
    /*
    "highlight" : {
        "fields" : {
            "content" : {}
        }
    }*/
    $params['body']['highlight']['fields'] = array(
        'pagetitle' => (object) array(),
        'introtext' => (object) array(),
        'content' => (object) array()
    );


    $results = $client->search($params);
    $total = $results['hits']['total'];
    $modx->setPlaceholder($totalVar, $total);
    $modx->setPlaceholder($placeholderPrefix . 'total', $total);
    $modx->setPlaceholder($placeholderPrefix . 'timing', number_format($results['took']) . 'ms');

    $out = [];
    foreach ($results['hits']['hits'] as $hit) {
        $data = $hit['_source'];
        $data['highlights'] = array();
        foreach ($hit['highlight'] as $key => $value) {
            $data['highlights'][$key] = implode('<br>',$value);
        }
        $out[] = $modx->getChunk($resultTpl, $data);
    }
    $out = implode("<hr>", $out);

    $modx->setPlaceholder($placeholderPrefix . 'results', $out);
}