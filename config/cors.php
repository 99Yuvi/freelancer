<?php
return [

'paths' => [
    'api/*',
    'sanctum/csrf-cookie',
],

'allowed_methods' => ['*'],

'allowed_origins' => [
    'https://operalyn.com',
    'https://socket.operalyn.com',
],

'allowed_headers' => ['*'],

'supports_credentials' => true,

];