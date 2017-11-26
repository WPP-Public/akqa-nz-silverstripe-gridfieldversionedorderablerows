<?php

namespace Heyday\GridFieldVersionedOrderableRows;

use Symbiote\GridFieldExtensions\GridFieldOrderableRows;


/**
 * Class GridFieldVersionedOrderableRows
 */
class GridFieldVersionedOrderableRows extends GridFieldOrderableRows
{
    /**
     * @param       $list
     * @param array $values
     * @param array $order
     */
    protected function reorderItems($list, array $values, array $order)
    {
        // Get a list of sort values that can be used.
        $pool = array_values($values);
        sort($pool);

        $table = $this->getSortTable($list);
        $sortField = $this->getSortField();

        // Loop through each item, and update the sort values which do not
        // match to order the objects.
        foreach (array_values($order) as $pos => $id) {
            if ($values[$id] != $pool[$pos]) {
                $where = $this->getSortTableClauseForIds($list, $id);
                DB::query(
                    sprintf(
                        'UPDATE "%s" SET "%s" = %d WHERE %s',
                        $table,
                        $sortField,
                        $pool[$pos],
                        $where
                    )
                );

                DB::query(
                    sprintf(
                        'UPDATE "%s_Live" SET "%s" = %d WHERE %s',
                        $table,
                        $sortField,
                        $pool[$pos],
                        $where
                    )
                );
            }
        }
        
        $this->extend('onAfterReorderItems', $list);
        
    }
    /**
     * @param DataList $list
     */
    protected function populateSortValues(DataList $list)
    {
        $list = clone $list;
        $field = $this->getSortField();
        $table = $this->getSortTable($list);
        $clause = sprintf('"%s"."%s" = 0', $table, $this->getSortField());

        foreach ($list->where($clause)->column('ID') as $id) {
            $max = DB::query(sprintf('SELECT MAX("%s") + 1 FROM "%s"', $field, $table));
            $max = $max->value();

            DB::query(
                sprintf(
                    'UPDATE "%s" SET "%s" = %d WHERE %s',
                    $table,
                    $field,
                    $max,
                    $this->getSortTableClauseForIds($list, $id)
                )
            );

            DB::query(
                sprintf(
                    'UPDATE "%s_Live" SET "%s" = %d WHERE %s',
                    $table,
                    $field,
                    $max,
                    $this->getSortTableClauseForIds($list, $id)
                )
            );
        }
    }
}
