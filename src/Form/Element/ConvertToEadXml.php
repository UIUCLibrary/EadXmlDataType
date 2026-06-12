<?php
namespace EadXmlDataType\Form\Element;

use Omeka\Form\Element\PropertySelect;
use Laminas\Form\Element;
use Laminas\ServiceManager\ServiceLocatorInterface;

class ConvertToEadXml extends Element
{
    protected $formElements;
    protected $propertyElement;

    public function setFormElementManager(ServiceLocatorInterface $formElements)
    {
        $this->formElements = $formElements;
    }

    public function init()
    {
        $this->setAttribute('data-collection-action', 'replace');
        $this->setLabel('Convert to EAD XML'); // @translate
        $this->propertyElement = $this->formElements->get(PropertySelect::class)
            ->setName('ead_xml_convert[property]')
            ->setEmptyOption('Select property') // @translate
            ->setAttributes([
                'class' => 'chosen-select',
                'data-placeholder' => 'Select property', // @translate
            ]);
    }

    public function getPropertyElement()
    {
        return $this->propertyElement;
    }
}
