<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2015
 */
namespace Spiral\ORM\Relations;

use Spiral\ORM\Model;
use Spiral\ORM\ORMException;
use Spiral\ORM\Relation;
use Spiral\ORM\Selector;

class HasOne extends Relation
{
    /**
     * Relation type.
     */
    const RELATION_TYPE = Model::HAS_ONE;

    /**
     * {@inheritdoc}
     */
    protected function createSelector()
    {
        $selector = parent::createSelector();

        if (isset($this->definition[Model::MORPH_KEY]))
        {
            $selector->where(
                $selector->getPrimaryAlias() . '.' . $this->definition[Model::MORPH_KEY],
                $this->parent->getRoleName()
            );
        }

        $selector->where(
            $selector->getPrimaryAlias() . '.' . $this->definition[Model::OUTER_KEY],
            $this->parent->getField($this->definition[Model::INNER_KEY], false)
        );

        return $selector;
    }

    /**
     * {@inheritdoc}
     */
    public function setInstance(Model $instance = null)
    {
        parent::setInstance($instance);
        $this->mountRelation($instance);
    }

    /**
     * {@inheritdoc}
     */
    protected function mountRelation(Model $model)
    {
        //Key in child model
        $outerKey = $this->definition[Model::OUTER_KEY];

        //Key in parent model
        $innerKey = $this->definition[Model::INNER_KEY];

        if ($model->getField($outerKey, false) != $this->parent->getField($innerKey, false))
        {
            $model->setField($outerKey, $this->parent->getField($innerKey, false), false);
        }

        if (!isset($this->definition[Model::MORPH_KEY]))
        {
            //No morph key presented
            return $model;
        }

        $morphKey = $this->definition[Model::MORPH_KEY];

        if ($model->getField($morphKey) != $this->parent->getRoleName())
        {
            $model->setField($morphKey, $this->parent->getRoleName());
        }

        return $model;
    }

    /**
     * Create model and configure it's fields with relation data. Attention, you have to validate and
     * save record by your own.
     *
     * @param mixed $fields
     * @return Model
     */
    public function create($fields = [])
    {
        $model = call_user_func([$this->getClass(), 'create'], $fields);

        return $this->mountRelation($model);
    }
}