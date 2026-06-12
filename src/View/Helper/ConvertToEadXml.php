<?php
namespace EadXmlDataType\View\Helper;

use Laminas\Form\ElementInterface;
use Laminas\Form\View\Helper\AbstractHelper;

class ConvertToEadXml extends AbstractHelper
{
    public function __invoke(ElementInterface $element)
    {
        return $this->render($element);
    }

    public function render(ElementInterface $element)
    {
        $view = $this->getView();
        return $view->formSelect($element->getPropertyElement());
    }
}
