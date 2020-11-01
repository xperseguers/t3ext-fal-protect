<?php
return [
    'frontend' => [
        'causal/fal-protect/fetch-file' => [
            'target' => \Causal\FalProtect\Middleware\FileMiddleware::class,
            'after' => [
                'typo3/cms-frontend/authentication',
            ],
            'before' =>
                version_compare((new \TYPO3\CMS\Core\Information\Typo3Version())->getBranch(), '10.4', '<')
                    ? [
                    'typo3/cms-frontend/backend-user-authentication',
                ]
                    : [
                    'typo3/cms-frontend/base-redirect-resolver',
                ],
        ],
    ],
];
