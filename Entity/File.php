<?php

namespace QBT\TranslationBundle\Entity;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use QBT\TranslationBundle\Model\File as FileModel;

/**
 * @UniqueEntity(fields={"hash"})
 *
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
        $this->createdAt = new \DateTime("now");
        $this->updatedAt = new \DateTime("now");
    }

    /**
     * (non-PHPdoc)
     * @see QBT\TranslationBundle\Model.File::preUpdate()
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime("now");
    }
}