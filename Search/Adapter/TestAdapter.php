<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Massive\Bundle\SearchBundle\Search\Adapter;

use Massive\Bundle\SearchBundle\Search\Document;
use Massive\Bundle\SearchBundle\Search\AdapterInterface;
use Massive\Bundle\SearchBundle\Search\SearchQuery;
use Massive\Bundle\SearchBundle\Search\Factory;

/**
 * Test adapter for testing scenarios
 */
class TestAdapter implements AdapterInterface
{
    protected $documents = array();
    protected $factory;

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritDoc}
     */
    public function index(Document $document, $indexName)
    {
        $this->documents[$indexName][$document->getId()] = $document;
    }

    /**
     * {@inheritDoc}
     */
    public function deindex(Document $document, $indexName)
    {
        if (!$indexName) {
            return;
        }

        foreach ($this->documents[$indexName] as $i => $selfDocument) {
            if ($document->getId() === $selfDocument->getId()) {
                unset($this->documents[$indexName][$i]);
            }
        }

        $this->documents[$indexName] = array_values($this->documents[$indexName]);
    }

    /**
     * {@inheritDoc}
     */
    public function purge($indexName)
    {
        unset($this->documents[$indexName]);
    }

    /**
     * Return all the indexed documents
     *
     * NOTE: Not part of the API
     *
     * @return Document[]
     */
    public function getDocuments()
    {
        $documents = array();
        foreach ($this->documents as $localizedDocuments) {
            foreach ($localizedDocuments as $document) {
                $documents[] = $document;
            }
        }

        return $documents;
    }

    /**
     * {@inheritDoc}
     */
    public function search(SearchQuery $searchQuery)
    {
        $hits = array();
        $indexes = $searchQuery->getIndexes();

        foreach ($indexes as $index) {
            if (!isset($this->documents[$index])) {
                continue;
            }

            foreach ($this->documents[$index] as $document) {
                $hit = $this->factory->createQueryHit();

                $isHit = false;

                foreach ($document->getFields() as $field) {
                    $fieldValue = $field->getValue();
                    if (is_array($fieldValue)) {
                        $fieldValue = implode(' ', $fieldValue);
                    }

                    if (preg_match('{' . trim(preg_quote($searchQuery->getQueryString())) .'}i', $fieldValue)) {
                        $isHit = true;
                        break;
                    }
                }

                if ($isHit) {
                    $hit->setDocument($document);
                    $hit->setScore(-1);
                    $hits[] = $hit;
                }
            }
        }

        return $hits;
    }

    /**
     * {@inheritDoc}
     */
    public function listIndexes()
    {
        return array_keys($this->documents);
    }

    /**
     * {@inheritDoc}
     */
    public function flush(array $indexNames)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getStatus()
    {
        return array();
    }
}