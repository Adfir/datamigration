<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Action\Exception\WrongContextException;
use Maketok\DataMigration\Storage\ResourceInterface;
use Maketok\DataMigration\Unit\AbstractUnit;
use Maketok\DataMigration\Unit\UnitBagInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class DeleteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $root;

    /**
     * setup
     */
    public function setUp()
    {
        $this->root = vfsStream::setup();
    }

    public function testGetCode()
    {
        $action = new Delete($this->getUnitBag(), $this->getConfig(), $this->getResource());
        $this->assertEquals('delete', $action->getCode());
    }

    /**
     * @return ConfigInterface
     */
    protected function getConfig()
    {
        $config = $this->getMockBuilder('\Maketok\DataMigration\Action\ConfigInterface')->getMock();
        return $config;
    }

    /**
     * @return AbstractUnit
     */
    protected function getUnit()
    {
        /** @var AbstractUnit $unit */
        $unit = $this->getMockBuilder('\Maketok\DataMigration\Unit\AbstractUnit')
            ->getMockForAbstractClass();
        $unit->setTable('test_table1');
        $unit->setTmpFileName('test_table1.csv');
        return $unit;
    }

    /**
     * @return AbstractUnit
     */
    protected function getWrongUnit()
    {
        /** @var AbstractUnit $unit */
        $unit = $this->getMockBuilder('\Maketok\DataMigration\Unit\AbstractUnit')
            ->getMockForAbstractClass();
        $unit->setTable('test_table1');
        return $unit;
    }

    /**
     * @return UnitBagInterface
     */
    protected function getUnitBag()
    {
        $unitBag = $this->getMockBuilder('\Maketok\DataMigration\Unit\UnitBagInterface')->getMock();
        $unitBag->expects($this->any())->method('add')->willReturnSelf();
        $unitBag->expects($this->any())->method('getIterator')->willReturn(new \ArrayIterator([$this->getUnit()]));
        return $unitBag;
    }

    /**
     * @return UnitBagInterface
     */
    protected function getWrongUnitBag()
    {
        $unitBag = $this->getMockBuilder('\Maketok\DataMigration\Unit\UnitBagInterface')->getMock();
        $unitBag->expects($this->any())->method('add')->willReturnSelf();
        $unitBag->expects($this->any())->method('getIterator')->willReturn(new \ArrayIterator([$this->getWrongUnit()]));
        return $unitBag;
    }

    /**
     * @param bool $expects
     * @return ResourceInterface
     */
    protected function getResource($expects = false)
    {
        $resource = $this->getMockBuilder('\Maketok\DataMigration\Storage\ResourceInterface')->getMock();
        if ($expects) {
            $resource->expects($this->atLeastOnce())->method('delete');
        }
        return $resource;
    }

    public function testProcess()
    {
        $action = new Delete($this->getUnitBag(), $this->getConfig(), $this->getResource(true));
        $action->process();
    }

    /**
     * @expectedException WrongContextException
     */
    public function testWrongProcess()
    {
        $action = new Delete($this->getWrongUnitBag(), $this->getConfig(), $this->getResource());
        $action->process();
    }
}
