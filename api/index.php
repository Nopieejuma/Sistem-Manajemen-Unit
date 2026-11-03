<?php

$compiledViews = '/tmp/views';

if (! is_dir($compiledViews)) {
    mkdir($compiledViews, 0777, true);
}

require __DIR__.'/../public/index.php';

