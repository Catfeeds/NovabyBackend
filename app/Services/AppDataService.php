<?php

  namespace App\Services;

use App\Models\PdaData;

class AppDataService
{

  public static function save( $data)
  {
    $dataArray = self::fetchData( $data);
    $pdaData = new PdaData();
    $pdaData->app_bundle_id = $dataArray[0][0];
    $pdaData->app_version = $dataArray[0][1];
    $pdaData->app_platform = $dataArray[0][2];
    $pdaData->device_duid = $dataArray[1][0];
    $pdaData->device_model = $dataArray[1][1];
    $pdaData->device_type = $dataArray[1][2];
    $pdaData->mld_target_id = $dataArray[2][0];
    $pdaData->mld_media_type = $dataArray[2][1];
    $pdaData->mld_media_name = $dataArray[2][2];
    $pdaData->app_time = $dataArray[3][0];
    $pdaData->mld_time = $dataArray[3][1];
    $pdaData->device_hash = md5( $dataArray[1][0].$dataArray[1][1].$dataArray[1][2]);
    $pdaData->save();
  }

  private static function fetchData( $data)
  {
    $catDataArray = explode( '$@$', $data);
    foreach( $catDataArray as &$value)
    {
      $value = explode( '$#$', $value);
    }
    return $catDataArray;
  }

}
