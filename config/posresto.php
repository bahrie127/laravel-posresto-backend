<?php

return [

    /*
    | PIN supervisor untuk membatalkan pesanan dari app kasir. Dulu tertulis mati di
    | controller; default 8888 dipertahankan supaya instalasi lama tidak berubah.
    */
    'cancel_pin' => env('POS_CANCEL_PIN', '8888'),

];
