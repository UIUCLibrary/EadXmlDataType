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
    'view_helpers' => [
        'invokables' => [
            'formEadXmlConvertToEadXml' => EadXmlDataType\View\Helper\ConvertToEadXml::class,
        ],
        'delegators' => [
            'Laminas\Form\View\Helper\FormElement' => [
                EadXmlDataType\Service\Delegator\FormElementDelegatorFactory::class,
            ],
        ],
    ],
    'form_elements' => [
        'factories' => [
            'EadXmlDataType\Form\Element\ConvertToEadXml' => EadXmlDataType\Service\Form\Element\ConvertToEadXmlFactory::class,
        ],
    ],
];
