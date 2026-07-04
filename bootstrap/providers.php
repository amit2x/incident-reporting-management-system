<?php

use App\Providers\AppServiceProvider;
use App\Providers\ImageHelperServiceProvider;
use Mews\Captcha\CaptchaServiceProvider;

return [
    AppServiceProvider::class,
    CaptchaServiceProvider::class,
    ImageHelperServiceProvider::class,

];
