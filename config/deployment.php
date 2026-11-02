<?php

return [
    'container' => (bool) env('VERCEL_CONTAINER', false),

    'trusted_proxies' => env('TRUSTED_PROXIES', '*'),
];
