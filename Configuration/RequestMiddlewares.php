<?php
return [
    'frontend' => [
        'causal/fal-protect/fetch-file' => [
            'target' => \Causal\FalProtect\Middleware\FileMiddleware::class,
            'after' => [
                'typo3/cms-frontend/authentication',
                'typo3/cms-frontend/site',
            ],
            'before' => [
                'typo3/cms-frontend/base-redirect-resolver',
            ],
        ],
    ],
];
