<?php

namespace Frakt24\LaravelPHPFirestore\Support\Facades;

use Illuminate\Support\Facades\Facade;

class Firestore extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'firestore';
    }
}
