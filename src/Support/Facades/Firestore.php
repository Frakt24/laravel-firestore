<?php

namespace Frakt24\LaravelPHPFirestore\Facades;

use Illuminate\Support\Facades\Facade;

class Firestore extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'firestore';
    }
}
