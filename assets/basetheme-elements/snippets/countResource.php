<?php
$parentsLog = (!empty($parents) || $parents === '0') ? $parents : $modx->resource->get('id');
$parents = (!empty($parents) || $parents === '0') ? explode(',', $parents) : array($modx->resource->get('id'));
array_walk($parents, 'trim');
$parents = array_unique($parents);
$depth = isset($depth) ? (integer) $depth : 10;

$where = !empty($where) ? $modx->fromJSON($where) : array();
$showUnpublished = !empty($showUnpublished) ? true : false;
$showDeleted = !empty($showDeleted) ? true : false;

$dbCacheFlag = !isset($dbCacheFlag) ? false : $dbCacheFlag;
if (is_string($dbCacheFlag) || is_numeric($dbCacheFlag)) {
    if ($dbCacheFlag == '0') {
        $dbCacheFlag = false;
    } elseif ($dbCacheFlag == '1') {
        $dbCacheFlag = true;
    } else {
        $dbCacheFlag = (integer) $dbCacheFlag;
    }
}
$cacheOptions = array(
    xPDO::OPT_CACHE_KEY => 'siteCache',
    //xPDO::OPT_CACHE_HANDLER => 'cache.xPDOAPCCache'
);

$cacheKey = 'countResource/'.md5(serialize($scriptProperties));

if($modx->getCacheManager() && is_null($total = $modx->cacheManager->get($cacheKey, $cacheOptions))) {
    /* multiple context support */
    $contextArray = array();
    $contextSpecified = false;
    if (!empty($context)) {
        $contextArray = explode(',',$context);
        array_walk($contextArray, 'trim');
        $contexts = array();
        foreach ($contextArray as $ctx) {
            $contexts[] = $modx->quote($ctx);
        }
        $context = implode(',',$contexts);
        $contextSpecified = true;
        unset($contexts,$ctx);
    } else {
        $context = $modx->quote($modx->context->get('key'));
    }

    $pcMap = array();
    $pcQuery = $modx->newQuery('modResource', array('id:IN' => $parents), $dbCacheFlag);
    $pcQuery->select(array('id', 'context_key'));
    if ($pcQuery->prepare() && $pcQuery->stmt->execute()) {
        foreach ($pcQuery->stmt->fetchAll(PDO::FETCH_ASSOC) as $pcRow) {
            $pcMap[(integer) $pcRow['id']] = $pcRow['context_key'];
        }
    }

    $children = array();
    $parentArray = array();
    foreach ($parents as $parent) {
        $parent = (integer) $parent;
        if ($parent === 0) {
            $pchildren = array();
            if ($contextSpecified) {
                foreach ($contextArray as $pCtx) {
                    if (!in_array($pCtx, $contextArray)) {
                        continue;
                    }
                    $options = $pCtx !== $modx->context->get('key') ? array('context' => $pCtx) : array();
                    $pcchildren = $modx->getChildIds($parent, $depth, $options);
                    if (!empty($pcchildren)) $pchildren = array_merge($pchildren, $pcchildren);
                }
            } else {
                $cQuery = $modx->newQuery('modContext', array('key:!=' => 'mgr'));
                $cQuery->select(array('key'));
                if ($cQuery->prepare() && $cQuery->stmt->execute()) {
                    foreach ($cQuery->stmt->fetchAll(PDO::FETCH_COLUMN) as $pCtx) {
                        $options = $pCtx !== $modx->context->get('key') ? array('context' => $pCtx) : array();
                        $pcchildren = $modx->getChildIds($parent, $depth, $options);
                        if (!empty($pcchildren)) $pchildren = array_merge($pchildren, $pcchildren);
                    }
                }
            }
            $parentArray[] = $parent;
        } else {
            $pContext = array_key_exists($parent, $pcMap) ? $pcMap[$parent] : false;
            if ($debug) $modx->log(modX::LOG_LEVEL_ERROR, "context for {$parent} is {$pContext}");
            if ($pContext && $contextSpecified && !in_array($pContext, $contextArray, true)) {
                $parent = next($parents);
                continue;
            }
            $parentArray[] = $parent;
            $options = !empty($pContext) && $pContext !== $modx->context->get('key') ? array('context' => $pContext) : array();
            $pchildren = $modx->getChildIds($parent, $depth, $options);
        }
        if (!empty($pchildren)) $children = array_merge($children, $pchildren);
        $parent = next($parents);
    }
    $parents = array_merge($parentArray, $children);

    /* build query */
    $criteria = array("modResource.parent IN (" . implode(',', $parents) . ")");
    if ($contextSpecified) {
        $contextResourceTbl = $modx->getTableName('modContextResource');
        $criteria[] = "(modResource.context_key IN ({$context}) OR EXISTS(SELECT 1 FROM {$contextResourceTbl} ctx WHERE ctx.resource = modResource.id AND ctx.context_key IN ({$context})))";
    }
    if (empty($showDeleted)) {
        $criteria['deleted'] = '0';
    }
    if (empty($showUnpublished)) {
        $criteria['published'] = '1';
    }
    if (empty($showHidden)) {
        $criteria['hidemenu'] = '0';
    }
    if (!empty($hideContainers)) {
        $criteria['isfolder'] = '0';
    }
    $criteria = $modx->newQuery('modResource', $criteria);

    if (!empty($where)) {
        $criteria->where($where);
    }
    $total = $modx->getCount('modResource', $criteria);
    
    $modx->cacheManager->set($cacheKey, $total, 0, $cacheOptions);

}

return $total;