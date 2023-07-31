<?php

Route::post('/paymentIPN', [\Dqburst\Laravel_clickpay\Controllers\ClickpayLaravelListenerApi::class, 'paymentIPN'])->name('payment_ipn');
