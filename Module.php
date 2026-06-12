<?php
namespace EadXmlDataType;

use Doctrine\Common\Collections\Criteria;
use EadXmlDataType\Form\Element\ConvertToEadXml;
use Omeka\Module\AbstractModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            'Omeka\Form\ResourceBatchUpdateForm',
            'form.add_elements',
            function (Event $event) {
                $form = $event->getTarget();
                $form->add([
                    'type' => ConvertToEadXml::class,
                    'name' => 'ead_xml_convert',
                ]);
            }
        );

        $eventIds = [
            'Omeka\Api\Adapter\ItemAdapter',
            'Omeka\Api\Adapter\ItemSetAdapter',
            'Omeka\Api\Adapter\MediaAdapter',
        ];
        foreach ($eventIds as $eventId) {
            $sharedEventManager->attach(
                $eventId,
                'api.preprocess_batch_update',
                function (Event $event) {
                    $data = $event->getParam('data');
                    $rawData = $event->getParam('request')->getContent();
                    if ($this->convertToEadXmlDataIsValid($rawData)) {
                        $data['ead_xml_convert'] = $rawData['ead_xml_convert'];
                    }
                    $event->setParam('data', $data);
                }
            );
            $sharedEventManager->attach(
                $eventId,
                'api.hydrate.post',
                [$this, 'convertToEadXml']
            );
        }
    }

    public function convertToEadXml(Event $event)
    {
        $entity = $event->getParam('entity');
        if ($entity instanceof \Omeka\Entity\Item) {
            $resource = 'items';
        } elseif ($entity instanceof \Omeka\Entity\ItemSet) {
            $resource = 'item_sets';
        } elseif ($entity instanceof \Omeka\Entity\Media) {
            $resource = 'media';
        } else {
            return;
        }

        $data = $event->getParam('request')->getContent();
        if (!$this->convertToEadXmlDataIsValid($data)) {
            return;
        }

        $propertyId = (int) $data['ead_xml_convert']['property'];

        $services = $this->getServiceLocator();
        $entityManager = $services->get('Omeka\EntityManager');
        $dataType = $services->get('Omeka\DataTypeManager')->get('ead:xml');
        $adapter = $services->get('Omeka\ApiAdapterManager')->get($resource);

        $dql = 'SELECT p FROM Omeka\Entity\Property p WHERE p.id = :id';
        $property = $entityManager->createQuery($dql)
            ->setParameter('id', $propertyId)
            ->getOneOrNullResult();
        if (null === $property) {
            return;
        }

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('property', $property))
            ->andWhere(Criteria::expr()->eq('type', 'literal'));
        $values = $entity->getValues()->matching($criteria);
        foreach ($values as $value) {
            $valueObject = ['@value' => $value->getValue()];
            if ($dataType->isValid($valueObject)) {
                $value->setType('ead:xml');
                $dataType->hydrate($valueObject, $value, $adapter);
            }
        }
    }

    public function convertToEadXmlDataIsValid(array $data)
    {
        return (
            isset($data['ead_xml_convert']['property'])
            && is_numeric($data['ead_xml_convert']['property'])
        );
    }
}

