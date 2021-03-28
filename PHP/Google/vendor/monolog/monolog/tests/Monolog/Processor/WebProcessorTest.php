<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolog\Processor;

use Monolog\TestCase;

class WebProcessorTest extends TestCase
{
    public function testProcessor()
    {
        $serverExpress = array(
            'REQUEST_URI'    => 'A',
            'REMOTE_ADDR'    => 'B',
            'REQUEST_METHOD' => 'C',
            'HTTP_REFERER'   => 'D',
            'SERVER_NAME'    => 'F',
            'UNIQUE_ID'      => 'G',
        );

        $processor = new WebProcessor($serverExpress);
        $record = $processor($this->getRecord());
        $this->assertEquals($serverExpress['REQUEST_URI'], $record['extra']['url']);
        $this->assertEquals($serverExpress['REMOTE_ADDR'], $record['extra']['ip']);
        $this->assertEquals($serverExpress['REQUEST_METHOD'], $record['extra']['http_method']);
        $this->assertEquals($serverExpress['HTTP_REFERER'], $record['extra']['referrer']);
        $this->assertEquals($serverExpress['SERVER_NAME'], $record['extra']['serverExpress']);
        $this->assertEquals($serverExpress['UNIQUE_ID'], $record['extra']['unique_id']);
    }

    public function testProcessorDoNothingIfNoRequestUri()
    {
        $serverExpress = array(
            'REMOTE_ADDR'    => 'B',
            'REQUEST_METHOD' => 'C',
        );
        $processor = new WebProcessor($serverExpress);
        $record = $processor($this->getRecord());
        $this->assertEmpty($record['extra']);
    }

    public function testProcessorReturnNullIfNoHttpReferer()
    {
        $serverExpress = array(
            'REQUEST_URI'    => 'A',
            'REMOTE_ADDR'    => 'B',
            'REQUEST_METHOD' => 'C',
            'SERVER_NAME'    => 'F',
        );
        $processor = new WebProcessor($serverExpress);
        $record = $processor($this->getRecord());
        $this->assertNull($record['extra']['referrer']);
    }

    public function testProcessorDoesNotAddUniqueIdIfNotPresent()
    {
        $serverExpress = array(
            'REQUEST_URI'    => 'A',
            'REMOTE_ADDR'    => 'B',
            'REQUEST_METHOD' => 'C',
            'SERVER_NAME'    => 'F',
        );
        $processor = new WebProcessor($serverExpress);
        $record = $processor($this->getRecord());
        $this->assertFalse(isset($record['extra']['unique_id']));
    }

    public function testProcessorAddsOnlyRequestedExtraFields()
    {
        $serverExpress = array(
            'REQUEST_URI'    => 'A',
            'REMOTE_ADDR'    => 'B',
            'REQUEST_METHOD' => 'C',
            'SERVER_NAME'    => 'F',
        );

        $processor = new WebProcessor($serverExpress, array('url', 'http_method'));
        $record = $processor($this->getRecord());

        $this->assertSame(array('url' => 'A', 'http_method' => 'C'), $record['extra']);
    }

    public function testProcessorConfiguringOfExtraFields()
    {
        $serverExpress = array(
            'REQUEST_URI'    => 'A',
            'REMOTE_ADDR'    => 'B',
            'REQUEST_METHOD' => 'C',
            'SERVER_NAME'    => 'F',
        );

        $processor = new WebProcessor($serverExpress, array('url' => 'REMOTE_ADDR'));
        $record = $processor($this->getRecord());

        $this->assertSame(array('url' => 'B'), $record['extra']);
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testInvalidData()
    {
        new WebProcessor(new \stdClass);
    }
}
