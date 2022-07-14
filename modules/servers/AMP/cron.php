<?php

use WHMCS\Database\Capsule;

require_once dirname(__FILE__, 4).'/'.'init.php';

$tasks =  Capsule::table('ampTasks')->where('tried', 1)->get();
foreach($tasks as $t)
{
    if (strtotime('+5 minutes', strtotime($t->updated_at))  < time() )
    {
        localAPI('ModuleCreate', [ 'serviceid' => $t->serviceId ]);
        Capsule::table('ampTasks')->where('id', $t->id)->update(['tried' => 2]);
    }
}

die;
