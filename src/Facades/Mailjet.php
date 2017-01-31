<?php namespace Sboo\Laravel5MailjetFix\Facades;

use Illuminate\Support\Facades\Facade;

class Mailjet  extends Facade{
    protected static function getFacadeAccessor() {
        return 'mailjet';
    }
}