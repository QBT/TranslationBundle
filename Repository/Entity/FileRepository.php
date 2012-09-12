<?php

namespace QBT\TranslationBundle\Repository\Entity;

use Doctrine\ORM\EntityRepository;

use QBT\TranslationBundle\Repository\FileRepositoryInterface;

/**
 * Repository for TransUnit entity.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class FileRepository extends EntityRepository implements FileRepositoryInterface
{
    /**
     * (non-PHPdoc)
     * @see QBT\TranslationBundle\Repository.FileRepositoryInterface::findForLoalesAndDomains()
     */
    public function findForLoalesAndDomains(array $locales, array $domains)
    {
        $builder = $this->createQueryBuilder('f');

        if (count($locales) > 0) {
            $builder->andWhere($builder->expr()->in('f.locale', $locales));
        }

        if (count($domains) > 0) {
            $builder->andWhere($builder->expr()->in('f.domain', $domains));
        }

        return $builder->getQuery()->getResult();
    }
}