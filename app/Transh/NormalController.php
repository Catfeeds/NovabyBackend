<?php

namespace App\Http\Controllers;

use App\libs\Tools;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesResources;
use DB;
use Session;

class NormalController extends BaseController
{use AuthorizesRequests, AuthorizesResources, DispatchesJobs, ValidatesRequests;


}
