<?php

return [

    // Editing the access authorization for a folder
    'folder_edit' => [
        'path' => '/folder/edit',
        'target' => \Causal\FalProtect\Controller\Folder\EditFolderController::class . '::mainAction'
    ],

];
