<?php
/**
 * This file is part of PDepend.
 *
 * PHP Version 5
 *
 * Copyright (c) 2008-2014, Manuel Pichler <mapi@pdepend.org>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Manuel Pichler nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @copyright 2014 Manuel Pichler. All rights reserved.
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 */

namespace PDepend\Report\Summary;

use PDepend\AbstractTest;
use PDepend\Source\AST\ASTArtifactList;

use PDepend\Metrics\Analyzer\CodeRankAnalyzer;

/**
 * Test case for the xml summary log.
 *
 * @copyright 2014 Manuel Pichler. All rights reserved.
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 *
 * @covers \PDepend\Report\Summary\Xml
 * @group unittest
 */
class DependenciesTest extends AbstractTest
{
    /**
     * The temporary file name for the logger result.
     *
     * @var string
     */
    protected $resultFile = null;

    /**
     * Creates the package structure from a test source file.
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $this->resultFile = self::createRunResourceURI('dependencies.xml');
    }

    /**
     * Removes the temporary log files.
     *
     * @return void
     */
    protected function tearDown()
    {
        @unlink($this->resultFile);

        parent::tearDown();
    }

    /**
     * Tests that the logger returns the expected set of analyzers.
     *
     * @return void
     */
    public function testReturnsExceptedAnalyzers()
    {
        $logger    = new Dependencies();
        $actual    = $logger->getAcceptedAnalyzers();
        $expected = array(
            'pdepend.analyzer.code_rank',
        );

        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests that the logger throws an exception if the log target wasn't
     * configured.
     *
     * @return void
     */
    public function testThrowsExceptionForInvalidLogTarget()
    {
        $this->setExpectedException(
            '\\PDepend\\Report\\NoLogOutputException',
            "The log target is not configured for 'PDepend\\Report\\Summary\\Dependencies'."
        );

        $logger = new Dependencies();
        $logger->close();
    }

    /**
     * testNodeAwareAnalyzer
     *
     * @return void
     */
    public function testReportDependencies()
    {
        $this->namespaces = self::parseCodeResourceForTest();

        $codeRank = new CodeRankAnalyzer();
        $codeRank->analyze($this->namespaces);

        $log = new Dependencies();
        $log->setLogFile($this->resultFile);
        $log->setArtifacts($this->namespaces);
        $log->log($codeRank);

        $log->close();

        $fileName = 'dependencies.xml';
        $this->assertXmlStringEqualsXmlString(
            $this->getNormalizedPathXml(dirname(__FILE__) . "/_expected/{$fileName}"),
            $this->getNormalizedPathXml($this->resultFile)
        );
    }

    protected function getNormalizedPathXml($fileName)
    {
        return preg_replace(
            array('(name="unresolved_[^"]+")'),
            array('name="unresolved__"'),
             file_get_contents($fileName)
        );
    }
}
