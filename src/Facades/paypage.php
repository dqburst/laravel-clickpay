<?php


namespace Dqburst\Laravel_clickpay\Facades;


use Illuminate\Support\Facades\Facade;

class paypage extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'paypage';
    }

}
