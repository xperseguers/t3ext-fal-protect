<?php
return [
    'frontend' => [
        'causal/fal-protect-file' => [
            'target' => \Causal\FalProtect\Middleware\FileMiddleware::class,
            'after' => [
                'typo3/cms-frontend/authentication',
            ],
            'before' => [
                'typo3/cms-frontend/backend-user-authentication',
            ],
        ],
    ],
];
