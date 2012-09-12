<?php

namespace QBT\TranslationBundle\Repository\Entity;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityRepository;

use QBT\TranslationBundle\Model\File;
use QBT\TranslationBundle\Repository\TransUnitRepositoryInterface;

/**
 * Repository for TransUnit entity.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnitRepository extends EntityRepository implements TransUnitRepositoryInterface
{
    /**
     * (non-PHPdoc)
     * @see QBT\TranslationBundle\Repository.TransUnitRepositoryInterface::getAllDomainsByLocale()
     */
    public function getAllDomainsByLocale()
    {
        return $this->createQueryBuilder('tu')
            ->select('te.locale, tu.domain')
            ->leftJoin('tu.translations', 'te')
            ->addGroupBy('te.locale')
            ->addGroupBy('tu.domain')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * (non-PHPdoc)
     * @see QBT\TranslationBundle\Repository.TransUnitRepositoryInterface::getAllByLocaleAndDomain()
     */
    public function getAllByLocaleAndDomain($locale, $domain)
    {
        return $this->createQueryBuilder('tu')
            ->select('tu, te')
            ->leftJoin('tu.translations', 'te')
            ->where('tu.domain = :domain')
            ->andWhere('te.locale = :locale')
            ->setParameter('domain', $domain)
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * (non-PHPdoc)
     * @see QBT\TranslationBundle\Repository.TransUnitRepositoryInterface::getAllDomains()
     */
    public function getAllDomains()
    {
        $this->loadCustomHydrator();

        return $this->createQueryBuilder('tu')
            ->select('DISTINCT tu.domain')
            ->orderBy('tu.domain', 'ASC')
            ->getQuery()
            ->getResult('SingleColumnArrayHydrator');
    }

    /**
     * (non-PHPdoc)
     * @see QBT\TranslationBundle\Repository.TransUnitRepositoryInterface::getTransUnitList()
     */
    public function getTransUnitList(array $locales = null, $rows = 20, $page = 1, array $filters = null)
    {
        $this->loadCustomHydrator();

        $sortColumn = isset($filters['sidx']) ? $filters['sidx'] : 'id';
        $order = isset($filters['sord']) ? $filters['sord'] : 'ASC';

        $builder = $this->createQueryBuilder('tu')
            ->select('tu.id');

        $this->addTransUnitFilters($builder, $locales, $filters);
        $this->addTranslationFilter($builder, $locales, $filters);

        $ids = $builder->orderBy(sprintf('tu.%s', $sortColumn), $order)
            ->setFirstResult($rows * ($page-1))
            ->setMaxResults($rows)
            ->getQuery()
            ->getResult('SingleColumnArrayHydrator');

        $transUnits = array();

        if (count($ids) > 0) {
            $qb = $this->createQueryBuilder('tu');

            $transUnits = $qb->select('tu, te')
                ->leftJoin('tu.translations', 'te')
                ->andWhere($qb->expr()->in('tu.id', $ids))
                ->andWhere($qb->expr()->in('te.locale', $locales))
                ->orderBy(sprintf('tu.%s', $sortColumn), $order)
                ->getQuery()
                ->getArrayResult();
        }

        return $transUnits;
    }

    /**
     * (non-PHPdoc)
     * @see QBT\TranslationBundle\Repository.TransUnitRepositoryInterface::count()
     */
    public function count(array $locales = null,  array $filters = null)
    {
        $this->loadCustomHydrator();

        $builder = $this->createQueryBuilder('tu')
            ->select('COUNT(DISTINCT tu.id) AS number');

        $this->addTransUnitFilters($builder, $locales, $filters);
        $this->addTranslationFilter($builder, $locales, $filters);

        return (int) $builder->getQuery()
            ->getResult(Query::HYDRATE_SINGLE_SCALAR);
    }

    /**
     * (non-PHPdoc)
     * @see QBT\TranslationBundle\Repository.TransUnitRepositoryInterface::getTranslationsForFile()
     */
    public function getTranslationsForFile(File $file, $onlyUpdated)
    {
        $builder = $this->createQueryBuilder('tu')
            ->select('tu.key, te.content')
            ->leftJoin('tu.translations', 'te')
            ->where('te.file = :file')
            ->setParameter('file', $file->getId())
            ->orderBy('te.id', 'asc');

        if ($onlyUpdated) {
            $builder->andWhere($builder->expr()->gt('te.updatedAt', 'te.createdAt'));
        }

        $results = $builder->getQuery()->getArrayResult();

        $translations = array();
        foreach ($results as $result) {
            $translations[$result['key']] = $result['content'];
        }

        return $translations;
    }

    /**
     * Add conditions according to given filters.
     *
     * @param QueryBuilder $builder
     * @param array $locales
     * @param array $filters
     */
    protected function addTransUnitFilters(QueryBuilder $builder, array $locales = null,  array $filters = null)
    {
        if (isset($filters['_search']) && $filters['_search']) {
            if (!empty($filters['domain'])) {
                $builder->andWhere($builder->expr()->like('tu.domain', ':domain'))
                    ->setParameter('domain', sprintf('%%%s%%', $filters['domain']));
            }

            if (!empty($filters['key'])) {
                $builder->andWhere($builder->expr()->like('tu.key', ':key'))
                    ->setParameter('key', sprintf('%%%s%%', $filters['key']));
            }
        }
    }

    /**
     * Add conditions according to given filters.
     *
     * @param QueryBuilder $builder
     * @param array $locales
     * @param array $filters
     */
    protected function addTranslationFilter(QueryBuilder $builder, array $locales = null,  array $filters = null)
    {
        if (null != $locales) {
            $qb = $this->createQueryBuilder('tu');
            $qb->select('DISTINCT tu.id')
                ->leftJoin('tu.translations', 't')
                ->where($qb->expr()->in('t.locale', $locales));

            foreach ($locales as $locale) {
                if (!empty($filters[$locale])) {
                    $qb->andWhere($qb->expr()->like('t.content', sprintf("'%%%s%%'", $filters[$locale])));
                    $qb->andWhere($qb->expr()->eq('t.locale', sprintf("'%s'", $locale)));
                }
            }

            $ids = $qb->getQuery()->getResult('SingleColumnArrayHydrator');

            if (count($ids) > 0) {
                $builder->andWhere($builder->expr()->in('tu.id', $ids));
            }
        }
    }

    /**
     * Load custom hydrator.
     */
    protected function loadCustomHydrator()
    {
        $config = $this->getEntityManager()->getConfiguration();
        $config->addCustomHydrationMode('SingleColumnArrayHydrator', 'QBT\TranslationBundle\Hydrators\SingleColumnArrayHydrator');
    }
}