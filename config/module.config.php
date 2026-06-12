<?php
return [
    'view_manager' => [
        'template_path_stack' => [
            OMEKA_PATH . '/modules/EadXmlDataType/view',
        ],
    ],
    'data_types' => [
        'invokables' => [
            'ead:xml' => EadXmlDataType\DataType\EadXml::class,
        ],
    ],
];
