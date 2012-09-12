<?php

namespace QBT\TranslationBundle\Repository;

use QBT\TranslationBundle\Model\File;

/**
 * Defines all method document and entity repositories have to implement.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
interface FileRepositoryInterface
{

    /**
     * Returns all files matching a given locale and a given domains.
     *
     * @param array $locales
     * @param array $domains
     * @return array
     */
    public function findForLoalesAndDomains(array $locales, array $domains);
}