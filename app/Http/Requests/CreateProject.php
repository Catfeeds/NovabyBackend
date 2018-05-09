<?php

namespace App\Http\Requests;
use Illuminate\Contracts\Validation\Validator;

class MyException{
    private $msg;
    public function __construct($msg)
    {
        $this->msg=$msg;
    }
    public function __toString()
    {
        return $this->msg;
    }
}
class MyRequest extends \Dingo\Api\Http\FormRequest{

    public function failedValidation(Validator $validator){
        //dd($validator->failed());
        if($validator->failed()){
            foreach($validator->getMessageBag()->toArray() AS $v){
                echo json_encode(['code'=>-1,'msg'=>$v[0]]);
                throw new \Exception($v[0]);
            }
        }
    }
}
class CreateProject extends MyRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title'=>'required|max:255|min:10',
            'industry' => 'required',
            'category' => 'required',
            'format' => 'required',
            'accuracy' => 'required',
            'texture' => 'required',
            'rigged' => 'required',
            'nums' => 'required',
            'photos'=>'required',
            'time_day'=>'required',
            'time_hour'=>'required',
        ];
    }
}

