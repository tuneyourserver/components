<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
namespace Spiral\Tests\Cases\Database;

use Spiral\Database\Entities\Database;
use Spiral\Database\Entities\Driver;
use Spiral\Database\TableInterface;

class DatabaseTest extends \PHPUnit_Framework_TestCase
{
    public function testDatabase()
    {
        /**
         * @var Driver|\PHPUnit_Framework_MockObject_MockObject $driver
         */
        $driver = $this->getMockBuilder(Driver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $driver->method('getType')->will($this->returnValue('test-driver'));

        $database = new Database($driver, 'test', 'prefix_');

        $this->assertEquals('test', $database->getName());
        $this->assertEquals($driver, $database->driver());
        $this->assertEquals('prefix_', $database->getPrefix());
        $this->assertEquals('test-driver', $database->getType());
    }

    public function testQuery()
    {
        /**
         * @var Driver|\PHPUnit_Framework_MockObject_MockObject $driver
         */
        $driver = $this->getMockBuilder(Driver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $driver->expects($this->once())->method('query')->with('test query');

        $database = new Database($driver, 'test', 'prefix_');
        $database->query('test query');
    }

    public function testStatement()
    {
        /**
         * @var Driver|\PHPUnit_Framework_MockObject_MockObject $driver
         */
        $driver = $this->getMockBuilder(Driver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $driver->expects($this->once())->method('statement')->with('test statement', [1, 2, 3]);

        $database = new Database($driver, 'test', 'prefix_');
        $database->statement('test statement', [1, 2, 3]);
    }

    public function testHasTable()
    {
        /**
         * @var Driver|\PHPUnit_Framework_MockObject_MockObject $driver
         */
        $driver = $this->getMockBuilder(Driver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $driver->expects($this->once())->method('hasTable')->with('prefix_table')->will(
            $this->returnValue(true)
        );

        $database = new Database($driver, 'test', 'prefix_');
        $this->assertTrue($database->hasTable('table'));
    }

    public function testTable()
    {
        /**
         * @var Driver|\PHPUnit_Framework_MockObject_MockObject $driver
         */
        $driver = $this->getMockBuilder(Driver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $database = new Database($driver, 'test', 'prefix_');

        $driver->expects($this->once())->method('hasTable')->with('prefix_table')->will(
            $this->returnValue(true)
        );

        $this->assertInstanceOf(TableInterface::class, $table = $database->table('table'));
        $this->assertEquals('table', $table->getName());
        $this->assertEquals('prefix_table', $table->realName());

        $this->assertTrue($table->exists());
    }
}