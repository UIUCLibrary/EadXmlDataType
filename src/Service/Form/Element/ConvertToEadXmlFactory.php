<?php
namespace EadXmlDataType\Service\Form\Element;

use EadXmlDataType\Form\Element\ConvertToEadXml;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ConvertToEadXmlFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new ConvertToEadXml;
        $element->setFormElementManager($services->get('FormElementManager'));
        return $element;
    }
}
