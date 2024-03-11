<?php
$typo3Branch = class_exists(\TYPO3\CMS\Core\Information\Typo3Version::class)
    ? (new \TYPO3\CMS\Core\Information\Typo3Version())->getBranch()
    : TYPO3_branch;
return [
    'frontend' => [
        'causal/fal-protect/fetch-file' => [
            'target' => \Causal\FalProtect\Middleware\FileMiddleware::class,
            'after' => [
                'typo3/cms-frontend/authentication',
                'typo3/cms-frontend/site',
            ],
            'before' =>
                version_compare($typo3Branch, '10.4', '<')
                    ? [
                    'typo3/cms-frontend/preview-simulator',
                ]
                    : [
                    'typo3/cms-frontend/base-redirect-resolver',
                ],
        ],
    ],
];
