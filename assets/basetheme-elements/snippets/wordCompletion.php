<?php
$properties =& $scriptProperties;

$properties['num'] = !empty($properties['num']) ? (int)$properties['num'] : 0;
$properties['item'] = !empty($properties['item']) ? $properties['item'] : '';
$properties['end_1'] = !empty($properties['end_1']) ? $properties['end_1'] : '';
$properties['end_2'] = !empty($properties['end_2']) ? $properties['end_2'] : '';
$properties['end_3'] = !empty($properties['end_3']) ? $properties['end_3'] : '';

$result = '';

$cacheOptions = array(
    xPDO::OPT_CACHE_KEY => 'siteCache',
    //xPDO::OPT_CACHE_HANDLER => 'cache.xPDOAPCCache'
);

$cacheKey = 'wordComplection/'.md5(serialize($properties));
 
if($modx->getCacheManager() && is_null($result = $modx->cacheManager->get($cacheKey, $cacheOptions))) {
    $stri = array( $properties['end_1'], $properties['end_2'], $properties['end_3'] );
    $index = $properties['num'] % 100;
    if ( $index >=11 && $index <= 14 ) $index = 0;
    else $index = ( $index %= 10 ) < 5 ? ( $index > 2 ? 2 : $index ): 0;
    
    $result = $properties['item'] . $stri[$index];
    
    $modx->cacheManager->set($cacheKey, $result, 0, $cacheOptions);
}

return $result;