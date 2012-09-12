<?php

namespace QBT\TranslationBundle\Entity;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use QBT\TranslationBundle\Model\TransUnit as TransUnitModel;

/**
 * @UniqueEntity(fields={"key", "domain"})
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnit extends TransUnitModel
{
    /**
     * Add translations
     *
     * @param QBT\TranslationBundle\Entity\Translation $translations
     */
    public function addTranslation(\QBT\TranslationBundle\Model\Translation $translation)
    {
        $translation->setTransUnit($this);

        $this->translations[] = $translation;
    }

    /**
     * (non-PHPdoc)
     * @see QBT\TranslationBundle\Model.TransUnit::prePersist()
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime("now");
        $this->updatedAt = new \DateTime("now");
    }

    /**
     * (non-PHPdoc)
     * @see QBT\TranslationBundle\Model.TransUnit::preUpdate()
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime("now");
    }
}