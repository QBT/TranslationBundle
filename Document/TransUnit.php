<?php

namespace QBT\TranslationBundle\Document;

use QBT\TranslationBundle\Model\TransUnit as TransUnitModel;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnit extends TransUnitModel
{
    /**
     * Convert all MongoTimestamp object to time.
     *
     */
    public function convertMongoTimestamp()
    {
        $this->createdAt = ($this->createdAt instanceof \MongoTimestamp) ? $this->createdAt->sec : $this->createdAt;;
        $this->updatedAt = ($this->updatedAt instanceof \MongoTimestamp) ? $this->updatedAt->sec : $this->updatedAt;

        foreach ($this->getTranslations() as $translation) {
            $translation->convertMongoTimestamp();
        }
    }

    /**
     * (non-PHPdoc)
     * @see QBT\TranslationBundle\Model.TransUnit::prePersist()
     */
    public function prePersist()
    {
        $now = new \DateTime("now");

        $this->createdAt = $now->format('U');
        $this->updatedAt = $now->format('U');
    }

    /**
     * (non-PHPdoc)
     * @see QBT\TranslationBundle\Model.TransUnit::preUpdate()
     */
    public function preUpdate()
    {
        $now = new \DateTime("now");

        $this->updatedAt = $now->format('U');
    }
}