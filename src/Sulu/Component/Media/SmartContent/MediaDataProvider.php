<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Media\SmartContent;

use JMS\Serializer\SerializerInterface;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManagerInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\SmartContent\Configuration\ComponentConfiguration;
use Sulu\Component\SmartContent\DatasourceItem;
use Sulu\Component\SmartContent\Orm\BaseDataProvider;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Media DataProvider for SmartContent.
 */
class MediaDataProvider extends BaseDataProvider
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var CollectionManagerInterface
     */
    private $collectionManager;

    public function __construct(
        DataProviderRepositoryInterface $repository,
        CollectionManagerInterface $collectionManager,
        SerializerInterface $serializer,
        RequestStack $requestStack
    ) {
        parent::__construct($repository, $serializer);

        $this->configuration = $this->initConfiguration(true, false, true, true, true, []);
        $this->configuration->setDatasource(
            new ComponentConfiguration(
                'media-datasource@sulumedia',
                [
                    'rootUrl' => '/admin/api/collections?sortBy=title&limit=9999&locale={locale}',
                    'selectedUrl' => '/admin/api/collections/{datasource}?tree=true&locale={locale}',
                    'resultKey' => 'collections',
                ]
            )
        );
        $this->requestStack = $requestStack;
        $this->collectionManager = $collectionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPropertyParameter()
    {
        return [
            'mimetype_parameter' => new PropertyParameter('mimetype_parameter', 'mimetype', 'string'),
            'type_parameter' => new PropertyParameter('type_parameter', 'type', 'string'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function resolveDatasource($datasource, array $propertyParameter, array $options)
    {
        if (empty($datasource)) {
            return;
        }

        $entity = $this->collectionManager->getById($datasource, $options['locale']);

        return new DatasourceItem($entity->getId(), $entity->getTitle(), $entity->getTitle());
    }

    /**
     * {@inheritdoc}
     */
    protected function getOptions(
        array $propertyParameter,
        array $options = []
    ) {
        $request = $this->requestStack->getCurrentRequest();

        $result = [];

        if (array_key_exists('mimetype_parameter', $propertyParameter)) {
            $result['mimetype'] = $request->get($propertyParameter['mimetype_parameter']->getValue());
        }
        if (array_key_exists('type_parameter', $propertyParameter)) {
            $result['type'] = $request->get($propertyParameter['type_parameter']->getValue());
        }

        return array_filter($result);
    }

    /**
     * {@inheritdoc}
     */
    protected function decorateDataItems(array $data)
    {
        return array_map(
            function ($item) {
                return new MediaDataItem($item);
            },
            $data
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getSerializationContext()
    {
        return parent::getSerializationContext()->setGroups(['fullMedia']);
    }
}
