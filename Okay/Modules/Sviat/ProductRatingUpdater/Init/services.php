<?php

namespace Okay\Modules\Sviat\ProductRatingUpdater\Init;

use Okay\Core\Database;
use Okay\Core\QueryFactory;
use Okay\Core\EntityFactory;
use Okay\Core\OkayContainer\Reference\ServiceReference as SR;
use Okay\Modules\Sviat\ProductRatingUpdater\Backend\Controllers\ProductRatingUpdaterAdmin;
use Okay\Modules\Sviat\ProductRatingUpdater\Backend\Helpers\BackendProductRatingUpdaterHelper;

return [

    BackendProductRatingUpdaterHelper::class => [
        'class' => BackendProductRatingUpdaterHelper::class,
        'arguments' => [
            new SR(Database::class),
            new SR(QueryFactory::class),
            new SR(EntityFactory::class),
        ],
    ],

    ProductRatingUpdaterAdmin::class => [
        'class' => ProductRatingUpdaterAdmin::class,
        'arguments' => [
            new SR(BackendProductRatingUpdaterHelper::class),
        ],
    ],

];
