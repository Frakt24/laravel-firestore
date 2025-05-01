<?php

namespace Frakt24\LaravelPHPFirestore\Models\Events;

use Frakt24\LaravelPHPFirestore\Models\FirestoreModel;

class ModelUpdating
{
    public function __construct(public FirestoreModel $model)
    {
    }
}
