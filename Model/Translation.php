<?php

namespace QBT\TranslationBundle\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * This class represent a translation for a given locale of a TransUnit object.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
abstract class Translation
{
    /**
     * @var string
     *
     * @Assert\NotBlank()
     */
    protected $locale;

    /**
     * @var string
     *
     * @Assert\NotBlank(groups={"contentNotBlank"})
     */
    protected $content;

    /**
     * @var QBT\TranslationBundle\Model\File
     */
    protected $file;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var \DateTime
     */
    protected $updatedAt;

    /**
     * Things to do on prePersist
     */
    abstract public function prePersist();

    /**
     * Things to do on preUpdate
     */
    abstract public function preUpdate();

    /**
     * Set locale
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        $this->content = '';
    }

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set content
     *
     * @param text $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Get content
     *
     * @return text
     */
    public function getContent()
    {
        return $this->content;
    }


    /**
     * Set file
     *
     * @param \QBT\TranslationBundle\Model\File $file
     */
    public function setFile(\QBT\TranslationBundle\Model\File $file)
    {
        $this->file = $file;
    }

    /**
     * Get file
     *
     * @return \QBT\TranslationBundle\Model\File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Get createdAt
     *
     * @return datetime $createdAt
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Get updatedAt
     *
     * @return datetime $updatedAt
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}