<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

$loader = require __DIR__.'/../vendor/autoload.php';

// intl
if (!function_exists('intl_get_error_code')) {
    require_once __DIR__.'/../vendor/symfony/symfony/src/Symfony/Component/Locale/Resources/stubs/functions.php';

    $loader->add('', __DIR__.'/../vendor/symfony/symfony/src/Symfony/Component/Locale/Resources/stubs');
}
$loader->add('SimpleImage', __DIR__.'/../vendor/simpleimage');
$loader->add('Services_Soundcloud', __DIR__.'/../vendor/soundcloud');
$loader->add('getid3', __DIR__.'/../vendor/getid3');
$loader->add('mPDF', __DIR__.'/../vendor/mpdf');

set_include_path(__DIR__.'/../vendor/simpleimage/lib'.PATH_SEPARATOR.
        __DIR__.'/../vendor/soundcloud'.PATH_SEPARATOR.
        __DIR__.'/../vendor/getid3'.PATH_SEPARATOR.
        __DIR__.'/../vendor/mpdf'.PATH_SEPARATOR.
        get_include_path()
);


AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
AnnotationDriver::registerAnnotationClasses();

return $loader;
