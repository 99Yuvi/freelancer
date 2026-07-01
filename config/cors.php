<?php
return [

'paths' => [
    'api/*',
    'sanctum/csrf-cookie',
],

'allowed_methods' => ['*'],

'allowed_origins' => [
    'https://operalyn.com',
    'http://localhost:5174',
    'https://socket.operalyn.com',
],

'allowed_headers' => ['*'],

'supports_credentials' => true,

];