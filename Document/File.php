<?php

namespace QBT\TranslationBundle\Document;

use QBT\TranslationBundle\Model\File as FileModel;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class File extends FileModel
{
    /**
     * (non-PHPdoc)
     * @see QBT\TranslationBundle\Model.File::prePersist()
     */
    public function prePersist()
    {
        $now = new \DateTime("now");

        $this->createdAt = $now->format('U');
        $this->updatedAt = $now->format('U');
    }

    /**
     * (non-PHPdoc)
     * @see QBT\TranslationBundle\Model.File::preUpdate()
     */
    public function preUpdate()
    {
        $now = new \DateTime("now");

        $this->updatedAt = $now->format('U');
    }
}