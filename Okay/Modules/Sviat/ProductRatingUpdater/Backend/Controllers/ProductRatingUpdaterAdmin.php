<?php

namespace Okay\Modules\Sviat\ProductRatingUpdater\Backend\Controllers;

use Okay\Admin\Controllers\IndexAdmin;
use Okay\Modules\Sviat\ProductRatingUpdater\Backend\Helpers\BackendProductRatingUpdaterHelper;

class ProductRatingUpdaterAdmin extends IndexAdmin
{
    public function fetch(BackendProductRatingUpdaterHelper $helper)
    {
        $message = '';
        $status = null;
        $count = 0;
        $countMissing = $helper->countMissingRatings();

        if ($this->request->method('post')) {
            $updateType = $this->request->post('update_type');

            $ratingMin = (float)$this->request->post('rating_min');
            $ratingMax = (float)$this->request->post('rating_max');
            $votesMin  = (int)$this->request->post('votes_min');
            $votesMax  = (int)$this->request->post('votes_max');

            $count = 0;
            $count = $helper->recalc($updateType === 'missing', $ratingMin, $ratingMax, $votesMin, $votesMax);

            $message = sprintf($this->design->getVar('btr')->product_rating_updater_updated_count, $count);
            $status = $count > 0 ? 'success' : 'error';
        }

        
        $this->design->assign('message', $message ?? '');
        $this->design->assign('status', $status);
        $this->design->assign('count', $count);
        $this->design->assign('countMissing', $countMissing);

        $this->response->setContent($this->design->fetch('product_rating_updater_admin.tpl'));
    }
}
