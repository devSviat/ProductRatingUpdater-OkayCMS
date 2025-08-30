<?php

namespace Okay\Modules\Sviat\ProductRatingUpdater\Init;

use Okay\Core\Modules\AbstractInit;

class Init extends AbstractInit
{
    public function install() {
        $this->setBackendMainController('ProductRatingUpdaterAdmin');
    }

    public function init()
    {
        $this->registerBackendController('ProductRatingUpdaterAdmin');
        $this->addBackendControllerPermission('ProductRatingUpdaterAdmin', 'products');
        $this->extendBackendMenu('left_catalog', [
            'left_product_rating_updater' => ['ProductRatingUpdaterAdmin'],
        ]);
    }
}
