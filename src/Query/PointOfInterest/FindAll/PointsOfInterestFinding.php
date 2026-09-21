<?php

declare(strict_types=1);

namespace PointsOfInterest\Query\PointOfInterest\FindAll;

use TinyBlocks\HttpQuery\Comparison;
use TinyBlocks\HttpQuery\Cursor\Keyset;
use TinyBlocks\HttpQuery\Cursor\Page;

/**
 * Finding of a forward-only cursor page of points of interest.
 */
interface PointsOfInterestFinding
{
    /**
     * Finds the next cursor page of points of interest matching the keyset, the reference point the
     * search is centered on, and the filter comparisons.
     *
     * @param Keyset $keyset The keyset carrying the page size, the orders, and the incoming cursor.
     * @param Proximity|null $proximity The reference point and the maximum distance, absent when the search is open.
     * @param list<Comparison> $comparisons The validated filter comparisons.
     * @return Page The cursor page carrying the point of interest views and the next cursor.
     */
    public function findAll(Keyset $keyset, ?Proximity $proximity, array $comparisons): Page;
}
