<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright �2009-2015
 */
namespace Spiral\ORM\Exceptions;

use Spiral\Database\Entities\Schemas\AbstractTable;
use Spiral\ORM\Entities\Schemas\ModelSchema;

/**
 * Raised when user or relation edits/creates columns in table associated to model with ACTIVE_SCHEMA
 * constant set to false. Tables like that counted as passive and their schema must not be altered
 * by ORM schema synchronizer.
 */
class ForbiddenChangeException extends SchemaException
{
    /**
     * @param AbstractTable $table
     * @param ModelSchema   $model
     */
    public function __construct(AbstractTable $table, ModelSchema $model)
    {
        $altered = [];
        foreach ($table->alteredColumns() as $column) {
            $altered[] = $column->getName();
        }

        parent::__construct(\Spiral\interpolate(
            'Passive table "{database}"."{table}" ({model}), were altered! Altered columns: {columns}',
            [
                'database' => $model->getDatabase(),
                'table'    => $table->getName(),
                'columns'  => join(', ', $altered)
            ]
        ));
    }
}