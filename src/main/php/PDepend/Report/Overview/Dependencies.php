<?php
/**
 * This file is part of PDepend.
 *
 * PHP Version 5
 *
 * Copyright (c) 2008-2013, Manuel Pichler <mapi@pdepend.org>.
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
 * @copyright 2008-2013 Manuel Pichler. All rights reserved.
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 */

namespace PDepend\Report\Overview;

use PDepend\Report\CodeAwareGenerator;
use PDepend\Report\FileAwareGenerator;
use PDepend\Report\NoLogOutputException;
use PDepend\Metrics\Analyzer;
use PDepend\Metrics\Analyzer\CodeRankAnalyzer;
use PDepend\Source\AST\ASTArtifactList;

/**
 * This logger generates a XML representation of the dependency graph used by
 * the CodeRank algorithm, to be used for further processing or visualization.
 *
 * @copyright 2014 Manuel Pichler. All rights reserved.
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 */
class Dependencies implements FileAwareGenerator, CodeAwareGenerator
{
    /**
     * @var string
     */
    private $logFile;

    /**
     * @var array(string=>array)
     */
    private $nodes;

    /**
     * @var \PDepend\Source\AST\ASTArtifactList $artifacts
     */
    private $artifacts;

    /**
     * @var array(string=>\PDepend\Source\AST\ASTArtifact)
     */
    private $artifactMap = array();

    private $typeNameMap = array(
        '\\PDepend\\Source\\AST\\ASTClass' => 'class',
        '\\PDepend\\Source\\AST\\ASTInterface' => 'interface',
        '\\PDepend\\Source\\AST\\ASTNamespace' => 'namespace',
    );

    /**
     * Adds an analyzer to log. If this logger accepts the given analyzer it
     * with return <b>true</b>, otherwise the return value is <b>false</b>.
     *
     * @param \PDepend\Metrics\Analyzer $analyzer The analyzer to log.
     * @return boolean
     */
    public function log(Analyzer $analyzer)
    {
        if ($analyzer instanceof CodeRankAnalyzer) {
            $this->nodes = $analyzer->getNodes();
        }
    }

    /**
     * Closes the logger process and writes the output file.
     *
     * @return void
     * @throws \PDepend\Report\NoLogOutputException If the no log target exists.
     */
    public function close()
    {
        if ($this->logFile === null) {
            throw new NoLogOutputException($this);
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');

        $dom->formatOutput = true;

        $artifactsElement = $dom->appendChild(
            $dom->createElement('artifacts')
        );

        foreach ($this->nodes as $nodeId => $node) {
            $artifactElement = $artifactsElement->appendChild(
                $this->renderArtifact($nodeId, $dom)
            );

            $inNode = $artifactElement->appendChild(
                $dom->createElement('in')
            );
            foreach (array_unique($node['in']) as $incomingId) {
                $inNode->appendChild($this->renderArtifact($incomingId, $dom));
            }

            $outNode = $artifactElement->appendChild(
                $dom->createElement('out')
            );
            foreach (array_unique($node['out']) as $outgoungId) {
                $outNode->appendChild($this->renderArtifact($outgoungId, $dom));
            }
        }

        $dom->save($this->logFile);
    }

    /**
     * @param mixed $artifactId
     * @param \DOMDocument $dom
     * @return \DOMElement
     */
    private function renderArtifact($artifactId, \DOMDocument $dom)
    {
        $artifactElement = $dom->createElement('artifact');
        $artifactElement->setAttribute('name', $this->getArtifactName($artifactId));
        $artifactElement->setAttribute('type', $this->getArtifactType($artifactId));
        $artifactElement->setAttribute('namespace', $this->getArtifactNamespace($artifactId));
        return $artifactElement;
    }

    /**
     * @param string $artifactId
     * @return string
     */
    private function getArtifactName($artifactId)
    {
        if (!isset($this->artifactMap[$artifactId])) {
            return "unresolved_" . $artifactId;
        }
        return $this->artifactMap[$artifactId]->getName();
    }

    /**
     * @param string $artifactId
     * @return string
     */
    private function getArtifactType($artifactId)
    {
        if (!isset($this->artifactMap[$artifactId])) {
            return "unknown";
        }
        $artifact = $this->artifactMap[$artifactId];

        foreach ($this->typeNameMap as $class => $typeName) {
            if ($artifact instanceof $class) {
                return $typeName;
            }
        }
        return "unknown";
    }

    private function getArtifactNamespace($artifactId)
    {
        if (!isset($this->artifactMap[$artifactId])) {
            return "";
        }

        if ($this->artifactMap[$artifactId] instanceof \PDepend\Source\AST\ASTNamespace) {
            return "";
        }

        return $this->artifactMap[$artifactId]->getNamespace()->getName();
    }

    /**
     * Returns an <b>array</b> with accepted analyzer types. These types can be
     * concrete analyzer classes or one of the descriptive analyzer interfaces. 
     *
     * @return array(string)
     */
    public function getAcceptedAnalyzers()
    {
        return array(
            'pdepend.analyzer.code_rank',
        );
    }

    /**
     * Sets the output log file.
     *
     * @param string $logFile The output log file.
     *
     * @return void
     */
    public function setLogFile($logFile)
    {
        $this->logFile = $logFile;
    }

    /**
     * Sets the context code nodes.
     *
     * @param \PDepend\Source\AST\ASTArtifactList $artifacts
     * @return void
     */
    public function setArtifacts(ASTArtifactList $artifacts)
    {
        $this->addToArtifactMap($artifacts);
    }

    private function addToArtifactMap($artifacts)
    {
        foreach ($artifacts as $artifact) {
            $this->artifactMap[$artifact->getId()] = $artifact;
            $this->addChildrenToArtifactMap($artifact);
        }
    }

    private function addChildrenToArtifactMap($artifact)
    {
        switch (true) {
            case ($artifact instanceof \PDepend\Source\AST\ASTNamespace):
                $this->addToArtifactMap($artifact->getTypes());
                break;

            case ($artifact instanceof \PDepend\Source\AST\AbstractClassOrInterface):
                $this->addToArtifactMap($artifact->getDependencies());
                break;
        }
    }
}
