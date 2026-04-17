<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "fal_protect".
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'FAL Protect',
    'description' => 'Protect everything within /fileadmin/ based on associated folder and file restrictions (visibility, user groups and dates of publication).',
    'category' => 'services',
    'version' => '1.8.0-dev',
    'state' => 'stable',
    'author' => 'Xavier Perseguers',
    'author_email' => 'xavier@causal.ch',
    'author_company' => 'Causal Sàrl',
    'constraints' => [
        'depends' => [
            'php' => '8.2.0-8.5.99',
            'typo3' => '12.4.0-14.3.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
