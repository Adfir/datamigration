<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Storage\Db\ResourceInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface as FsResourceInterface;
use Maketok\DataMigration\Unit\AbstractUnit;
use Maketok\DataMigration\Unit\UnitBagInterface;

class DeleteTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCode()
    {
        $action = new Delete(
            $this->getUnitBag(),
            $this->getConfig(),
            $this->getFS(),
            $this->getResource()
        );
        $this->assertEquals('delete', $action->getCode());
    }

    /**
     * @return ConfigInterface
     */
    protected function getConfig()
    {
        $config = $this->getMockBuilder('\Maketok\DataMigration\Action\ConfigInterface')
            ->getMock();
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
        $unit->setTmpTable('test_table1_tmp');
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
        $unitBag = $this->getMockBuilder('\Maketok\DataMigration\Unit\UnitBagInterface')
            ->getMock();
        $unitBag->expects($this->any())->method('add')->willReturnSelf();
        $unitBag->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->getUnit()]));
        return $unitBag;
    }

    /**
     * @return UnitBagInterface
     */
    protected function getWrongUnitBag()
    {
        $unitBag = $this->getMockBuilder('\Maketok\DataMigration\Unit\UnitBagInterface')
            ->getMock();
        $unitBag->expects($this->any())->method('add')->willReturnSelf();
        $unitBag->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->getWrongUnit()]));
        return $unitBag;
    }

    /**
     * @param bool $expects
     * @return ResourceInterface
     */
    protected function getResource($expects = false)
    {
        $resource = $this->getMockBuilder('\Maketok\DataMigration\Storage\Db\ResourceInterface')
            ->getMock();
        if ($expects) {
            $resource->expects($this->atLeastOnce())->method('deleteUsingTempPK');
        }
        return $resource;
    }

    /**
     * @return FsResourceInterface
     */
    protected function getFS()
    {
        $filesystem = $this->getMockBuilder('\Maketok\DataMigration\Storage\Filesystem\ResourceInterface')
            ->getMock();
        return $filesystem;
    }

    public function testProcess()
    {
        $action = new Delete(
            $this->getUnitBag(),
            $this->getConfig(),
            $this->getFS(),
            $this->getResource(true)
        );
        $action->process();
    }

    /**
     * @expectedException \Maketok\DataMigration\Action\Exception\WrongContextException
     */
    public function testWrongProcess()
    {
        $action = new Delete(
            $this->getWrongUnitBag(),
            $this->getConfig(),
            $this->getFS(),
            $this->getResource()
        );
        $action->process();
    }
}
