<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use PHPCR\SessionInterface;
use Prophecy\Argument;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Subscriber\SecuritySubscriber;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;

class SecuritySubscriberTest extends SubscriberTestCase
{
    /**
     * @var ObjectProphecy
     */
    private $liveSession;

    /**
     * @var SecuritySubscriber
     */
    private $subscriber;

    public function setUp(): void
    {
        parent::setUp();

        $this->liveSession = $this->prophesize(SessionInterface::class);
        $this->subscriber = new SecuritySubscriber(
            ['view' => 64, 'add' => 32, 'edit' => 16, 'delete' => 8],
            $this->liveSession->reveal()
        );
    }

    public function testPersist()
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('sec:role-1');
        $this->node->getProperties('sec:role-*')->willReturn([$property->reveal()]);
        $liveNode = $this->prophesize(NodeInterface::class);
        $liveProperty = $this->prophesize(PropertyInterface::class);
        $liveNode->getProperty('sec:role-2')->willReturn($liveProperty->reveal());

        /** @var SecurityBehavior $document */
        $document = $this->prophesize(SecurityBehavior::class);
        $document->willImplement(PathBehavior::class);
        $document->getPath()->willReturn('/some/path');
        $document->getPermissions()->willReturn(
            [1 => ['view' => true, 'add' => true, 'edit' => true, 'delete' => false]]
        );

        $this->liveSession->getNode('/some/path')->willReturn($liveNode->reveal());

        $this->persistEvent->getDocument()->willReturn($document);

        $this->node->setProperty('sec:role-1', ['view', 'add', 'edit'])->shouldBeCalled();
        $property->remove()->shouldNotBeCalled();

        $liveNode->setProperty('sec:role-1', ['view', 'add', 'edit'])->shouldBeCalled();
        $liveProperty->remove()->shouldNotBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    public function testPersistWithoutPath()
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('sec:role-1');
        $this->node->getProperties('sec:role-*')->willReturn([$property->reveal()]);

        /** @var SecurityBehavior $document */
        $document = $this->prophesize(SecurityBehavior::class);
        $document->willImplement(PathBehavior::class);
        $document->getPath()->willReturn(null);
        $document->getPermissions()->willReturn(
            [1 => ['view' => true, 'add' => true, 'edit' => true, 'delete' => false]]
        );

        $this->liveSession->getNode(Argument::any())->shouldNotBeCalled();

        $this->persistEvent->getDocument()->willReturn($document);

        $this->node->setProperty('sec:role-1', ['view', 'add', 'edit'])->shouldBeCalled();
        $property->remove()->shouldNotBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    public function testPersistWithDeletingRoles()
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('sec:role-2');
        $this->node->getProperties('sec:role-*')->willReturn([$property->reveal()]);
        $liveNode = $this->prophesize(NodeInterface::class);
        $liveProperty = $this->prophesize(PropertyInterface::class);
        $liveNode->getProperty('sec:role-2')->willReturn($liveProperty->reveal());
        $liveNode->hasProperty('sec:role-2')->willReturn(true);

        /** @var SecurityBehavior $document */
        $document = $this->prophesize(SecurityBehavior::class);
        $document->willImplement(PathBehavior::class);
        $document->getPath()->willReturn('/some/path');
        $document->getPermissions()->willReturn(
            [1 => ['view' => true, 'add' => true, 'edit' => true, 'delete' => false]]
        );

        $this->liveSession->getNode('/some/path')->willReturn($liveNode->reveal());

        $this->persistEvent->getDocument()->willReturn($document);

        $this->node->setProperty('sec:role-1', ['view', 'add', 'edit'])->shouldBeCalled();
        $property->remove()->shouldBeCalled();

        $liveNode->setProperty('sec:role-1', ['view', 'add', 'edit'])->shouldBeCalled();
        $liveProperty->remove()->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    public function testHydrate()
    {
        /** @var SecurityBehavior $document */
        $document = $this->prophesize(SecurityBehavior::class);
        $node = $this->prophesize(NodeInterface::class);

        /** @var PropertyInterface $roleProperty1 */
        $roleProperty1 = $this->prophesize(PropertyInterface::class);
        $roleProperty1->getName()->willReturn('sec:role-1');
        $roleProperty1->getValue()->willReturn(['view', 'add', 'edit']);

        /** @var PropertyInterface $roleProperty2 */
        $roleProperty2 = $this->prophesize(PropertyInterface::class);
        $roleProperty2->getName()->willReturn('sec:role-2');
        $roleProperty2->getValue()->willReturn(['view', 'edit']);

        $node->getProperties('sec:*')->willReturn([$roleProperty1->reveal(), $roleProperty2->reveal()]);

        $this->hydrateEvent->getDocument()->willReturn($document);
        $this->hydrateEvent->getNode()->willReturn($node);

        $document->setPermissions([
            1 => [
                'view' => true,
                'add' => true,
                'edit' => true,
                'delete' => false,
            ],
            2 => [
                'view' => true,
                'add' => false,
                'edit' => true,
                'delete' => false,
            ],
        ])->shouldBeCalled();

        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }
}
