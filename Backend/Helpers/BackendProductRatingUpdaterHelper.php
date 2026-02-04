<?php

namespace Okay\Modules\Sviat\ProductRatingUpdater\Backend\Helpers;

use Okay\Core\Database;
use Okay\Core\QueryFactory;
use Okay\Core\EntityFactory;
use Okay\Entities\ProductsEntity;

class BackendProductRatingUpdaterHelper
{
    private $db;
    private $queryFactory;
    private $productsEntity;

    public function __construct(
        Database $db,
        QueryFactory $queryFactory,
        EntityFactory $entityFactory
    ) {
        $this->db = $db;
        $this->queryFactory = $queryFactory;
        $this->productsEntity = $entityFactory->get(ProductsEntity::class);
    }

    /**
     * Оновлення товарів з можливістю вказати діапазон рейтингу та голосів
     *
     * @param bool $onlyMissing
     * @param float|null $ratingMin
     * @param float|null $ratingMax
     * @param int|null $votesMin
     * @param int|null $votesMax
     * @return int - кількість оновлених рядків
     */
    public function recalc(
        bool $onlyMissing = false,
        ?float $ratingMin = null,
        ?float $ratingMax = null,
        ?int $votesMin = null,
        ?int $votesMax = null
    ): int {

        $sql = $this->queryFactory->newSqlQuery();

        $ratingExpr = '5.0';
        if ($ratingMin !== null && $ratingMax !== null) {
            $ratingExpr = "({$ratingMin} + RAND() * ({$ratingMax} - {$ratingMin}))";
        }

        $votesExpr = '1';
        if ($votesMin !== null && $votesMax !== null) {
            $votesExpr = "FLOOR({$votesMin} + RAND() * ({$votesMax} - {$votesMin} + 1))";
        }

        $sqlStatement = "UPDATE __products SET rating = {$ratingExpr}, votes = {$votesExpr}";

        if ($onlyMissing) {
            $sqlStatement .= " WHERE rating IS NULL OR rating = 0 OR votes IS NULL OR votes = 0";
        }

        $sql->setStatement($sqlStatement);

        $this->db->query($sql);

        return $this->db->affectedRows();
    }

    /**
     * Повертає кількість товарів без рейтингу або без голосів
     *
     * @return int
     */
    public function countMissingRatings(): int
    {
        $filter = [
            'votes'  => null,
        ];

        return count($this->productsEntity->find($filter));
    }
}
