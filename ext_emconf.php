<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "fal_protect".
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'FAL Protect',
    'description' => 'Protect everything within /fileadmin/ based on associated folder and file restrictions (visibility, user groups and dates of publication).',
    'category' => 'services',
    'version' => '1.6.1',
    'state' => 'stable',
    'author' => 'Xavier Perseguers',
    'author_email' => 'xavier@causal.ch',
    'author_company' => 'Causal Sàrl',
    'constraints' => [
        'depends' => [
            'php' => '7.2.0-8.4.99',
            'typo3' => '10.4.0-13.4.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
