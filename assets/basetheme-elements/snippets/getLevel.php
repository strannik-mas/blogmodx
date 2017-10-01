<?php
$properties =& $scriptProperties;

$properties['id'] = isset($properties['id']) ? $properties['id'] : $modx->resource->get('id');

$output = 0;

$cacheOptions = array(
    xPDO::OPT_CACHE_KEY => 'siteCache',
);

$cacheKey = 'getLevel/'.md5(serialize($properties));

if($modx->getCacheManager() && is_null($output = $modx->cacheManager->get($cacheKey, $cacheOptions))) {
    $pids = $modx->getParentIds($id, 100);
    
    $output = count($pids);
    
    $modx->cacheManager->set($cacheKey, $output, 0, $cacheOptions);
}

return $output;