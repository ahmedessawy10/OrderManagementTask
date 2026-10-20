<?php

namespace App\Http\Traits;

use Illuminate\Database\Eloquent\Relations\Relation;

trait CascadesSoftDeletes
{
    protected static function bootCascadesSoftDeletes()
    {
        static::deleting(function ($model) {
            if ($model->isForceDeleting()) {
                return;
            }

            foreach ($model->getCascadeDeletes() as $relation) {
                // Check if relation method exists
                if (! method_exists($model, $relation)) {
                    continue;
                }

                // Get relation query and check if it exists
                $relationQuery = $model->{$relation}();

                // Skip if not a valid relation
                if (! $relationQuery instanceof Relation) {
                    continue;
                }

                // Get all related models
                $relatedModels = $relationQuery->get();

                // Delete each related model to trigger their observers and cascades
                foreach ($relatedModels as $relatedModel) {
                    if (! $relatedModel->trashed()) {
                        $relatedModel->delete();
                    }

                }
            }
        });

        static::restoring(function ($model) {
            foreach ($model->getCascadeDeletes() as $relation) {
                // Check if relation method exists
                if (! method_exists($model, $relation)) {
                    continue;
                }

                // Get relation query with trashed models
                $relationQuery = $model->{$relation}();

                // Skip if not a valid relation
                if (! $relationQuery instanceof Relation) {
                    continue;
                }

                // Get all trashed related models
                $relatedModels = $relationQuery->onlyTrashed()->get();

                // Restore each related model
                foreach ($relatedModels as $relatedModel) {
                    $relatedModel->restore();
                }
            }
        });
    }
}
