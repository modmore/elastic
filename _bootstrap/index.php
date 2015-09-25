<?php
/* Get the core config */
if (!file_exists(dirname(dirname(__FILE__)).'/config.core.php')) {
    die('ERROR: missing '.dirname(dirname(__FILE__)).'/config.core.php file defining the MODX core path.');
}

echo "<pre>";
/* Boot up MODX */
echo "Loading modX...\n";
require_once dirname(dirname(__FILE__)).'/config.core.php';
require_once MODX_CORE_PATH.'model/modx/modx.class.php';
$modx = new modX();
echo "Initializing manager...\n";
$modx->initialize('mgr');
$modx->getService('error','error.modError', '', '');

$componentPath = dirname(dirname(__FILE__));

/** @var Scheduler $scheduler */
$path = $modx->getOption('scheduler.core_path', null, $modx->getOption('core_path') . 'components/scheduler/');
$scheduler = $modx->getService('scheduler', 'Scheduler', $path . 'model/scheduler/');


/* Namespace */
if (!createObject('modNamespace',array(
    'name' => 'elastic',
    'path' => $componentPath.'/core/components/elastic/',
    'assets_path' => $componentPath.'/assets/components/elastic/',
),'name', false)) {
    echo "Error creating namespace elastic.\n";
}

/* Path settings */
if (!createObject('modSystemSetting', array(
    'key' => 'elastic.core_path',
    'value' => $componentPath.'/core/components/elastic/',
    'xtype' => 'textfield',
    'namespace' => 'elastic',
    'area' => 'Paths',
    'editedon' => time(),
), 'key', false)) {
    echo "Error creating elastic.core_path setting.\n";
}

/* Fetch assets url */
$url = 'http';
if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) {
    $url .= 's';
}
$url .= '://'.$_SERVER["SERVER_NAME"];
if ($_SERVER['SERVER_PORT'] != '80') {
    $url .= ':'.$_SERVER['SERVER_PORT'];
}
$requestUri = $_SERVER['REQUEST_URI'];
$bootstrapPos = strpos($requestUri, '_bootstrap/');
$requestUri = rtrim(substr($requestUri, 0, $bootstrapPos), '/').'/';
$assetsUrl = "{$url}{$requestUri}assets/components/elastic/";

/*if (!createObject('modSystemSetting', array(
    'key' => 'elastic.assets_url',
    'value' => $assetsUrl,
    'xtype' => 'textfield',
    'namespace' => 'elastic',
    'area' => 'Paths',
    'editedon' => time(),
), 'key', false)) {
    echo "Error creating elastic.assets_url setting.\n";
}*/

if (!createObject('modSystemSetting', array(
    'key' => 'elastic.hosts',
    'value' => 'localhost:9200',
    'xtype' => 'textfield',
    'namespace' => 'elastic',
    'area' => 'Client',
    'editedon' => time(),
), 'key', false)) {
    echo "Error creating elastic.hosts setting.\n";
}
if (!createObject('modSystemSetting', array(
    'key' => 'elastic.resource_index',
    'value' => 'resources',
    'xtype' => 'textfield',
    'namespace' => 'elastic',
    'area' => 'Indexing',
    'editedon' => time(),
), 'key', false)) {
    echo "Error creating elastic.resource_index setting.\n";
}
if (!createObject('modSystemSetting', array(
    'key' => 'elastic.resource_type',
    'value' => 'content',
    'xtype' => 'textfield',
    'namespace' => 'elastic',
    'area' => 'Indexing',
    'editedon' => time(),
), 'key', false)) {
    echo "Error creating elastic.resource_type setting.\n";
}

if (!createObject('modCategory', array(
    'category' => 'Elastic',
    'parent' => 0,
), 'category', false)) {
    echo "Error creating Category.\n";
}
$categoryId = 0;
$category = $modx->getObject('modCategory', array('category' => 'Elastic'));
if ($category instanceof modCategory) {
    $categoryId = $category->get('id');
}

if (!createObject('modSnippet', array(
    'name' => 'ElasticSearch',
    'static' => true,
    'static_file' => '[[++elastic.core_path]]elements/snippets/elasticsearch.snippet.php',
    'category' => $categoryId,
), 'name', true)) {
    echo "Error creating snippet.\n";
}


/**
 * Plugins
 */
if (!createObject('modPlugin', array(
    'name' => 'elastic_index_resources',
    'static' => true,
    'static_file' => '[[++elastic.core_path]]elements/plugins/elastic_index_resources.plugin.php',
), 'name', false)) {
    echo "Error creating modPlugin.\n";
}
$indexPlugin = $modx->getObject('modPlugin', array('name' => 'elastic_index_resources'));
if ($indexPlugin) {
    if (!createObject('modPluginEvent', array(
        'pluginid' => $indexPlugin->get('id'),
        'event' => 'OnDocFormSave',
        'priority' => 0,
    ), array('pluginid','event'), false)) {
        echo "Error creating modPluginEvent.\n";
    }
}

if ($scheduler instanceof Scheduler) {
    if (!createObject('sTask', array(
        'class_key' => 'sFileTask',
        'content' => 'elements/tasks/elastic_index_all.task.php',
        'namespace' => 'elastic',
        'reference' => 'elastic_index_all',
        'description' => 'Indexes all resources in an ElasticSearch server.'
    ), 'reference', true)
    ) {
        echo "Error creating sTask object";
    }
}
$manager = $modx->getManager();

$modx->getCacheManager()->refresh();

/**
 * Creates an object.
 *
 * @param string $className
 * @param array $data
 * @param string $primaryField
 * @param bool $update
 * @return bool
 */
function createObject ($className = '', array $data = array(), $primaryField = '', $update = true) {
    global $modx;
    /* @var xPDOObject $object */
    $object = null;

    /* Attempt to get the existing object */
    if (!empty($primaryField)) {
        if (is_array($primaryField)) {
            $condition = array();
            foreach ($primaryField as $key) {
                $condition[$key] = $data[$key];
            }
        }
        else {
            $condition = array($primaryField => $data[$primaryField]);
        }
        $object = $modx->getObject($className, $condition);
        if ($object instanceof $className) {
            if ($update) {
                $object->fromArray($data);
                return $object->save();
            } else {
                $condition = $modx->toJSON($condition);
                echo "Skipping {$className} {$condition}: already exists.\n";
                return true;
            }
        }
    }

    /* Create new object if it doesn't exist */
    if (!$object) {
        $object = $modx->newObject($className);
        $object->fromArray($data, '', true);
        return $object->save();
    }

    return false;
}
