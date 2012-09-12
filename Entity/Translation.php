<?php

namespace QBT\TranslationBundle\Entity;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use QBT\TranslationBundle\Model\Translation as TranslationModel;

/**
 * @UniqueEntity(fields={"transUnit", "locale"})
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class Translation extends TranslationModel
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var QBT\TranslationBundle\Entity\TransUnit
     */
    protected $transUnit;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set transUnit
     *
     * @param QBT\TranslationBundle\Entity\TransUnit $transUnit
     */
    public function setTransUnit(\QBT\TranslationBundle\Model\TransUnit $transUnit)
    {
        $this->transUnit = $transUnit;
    }

    /**
     * Get transUnit
     *
     * @return QBT\TranslationBundle\Entity\TransUnit
     */
    public function getTransUnit()
    {
        return $this->transUnit;
    }

    /**
     * (non-PHPdoc)
     * @see QBT\TranslationBundle\Model.Translation::prePersist()
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime("now");
        $this->updatedAt = new \DateTime("now");
    }

    /**
     * (non-PHPdoc)
     * @see QBT\TranslationBundle\Model.Translation::preUpdate()
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime("now");
    }
}