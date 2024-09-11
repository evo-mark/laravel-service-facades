<?php

return [
    'locations' => [
        [
            'name' => 'Application',
            'service_namespace' => 'App\\Services',
            'facade_namespace' => 'App\\Facades',
            'service_path' => app_path('Services'),
            'facade_path' => app_path('Facades'),
            'exclude' => []
        ]
    ]
];
