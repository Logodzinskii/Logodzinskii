<?php

class report{
    public function __construct($arr){
        $this-> arr = $arr;
    }

   public function sortByMonth($month){
       $this->month = strtolower($month);

       $a=[];

       if( count($this->arr) > 0 ){
           foreach ($this->arr as $item){

               $date = date_create($item['date']);
               $newDateFormat = ['date' => strtolower(date_format( $date,'F'))];
               $a[] = array_replace($item,$newDateFormat);

           }
           $arr = array_replace($this->arr,$a);

           $arr = array_filter($arr, function($arr1){

                 return in_array($this->month,$arr1);

                 });

       }
       return $arr;
   }

   public function sortBySellerName($seller){
       $this->seller = $seller;
       $arr = $this->arr;
       $arr = array_filter($arr,function($arr1){
          return in_array($this->seller, $arr1);
       });
       return $arr;
   }
   public function sumArr($arrToSum){
       $sum = 0;
       foreach ($arrToSum as $item){

           $sum = $sum + $item['totalPrice'];

       }
       return $sum;
   }
}