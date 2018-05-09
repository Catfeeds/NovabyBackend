<?php

  namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Services\AppDataService;

class AppDataController extends Controller
{
  /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

  public function store( Request $request)
  {
    $data = $request->input('data');
    return AppDataService::save( $data);
  }

}
