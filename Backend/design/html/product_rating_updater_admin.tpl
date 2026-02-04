{$meta_title = $btr->product_rating_updater_title|escape scope=global}

<div class="row">
    <div class="col-lg-12 col-md-12">
        <div class="wrap_heading">
            <div class="box_heading heading_page">
                {$btr->product_rating_updater_title|escape}
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-9 col-lg-6">
        <div class="boxed">
            <div class="heading_box">
                {$btr->product_rating_updater_heading|escape}
            </div>
            <div class="boxed_body">
                {if $message}
                    <div class="alert alert--icon alert--{if $status == 'success'}success{else}error{/if}">
                        <div class="alert__content">
                            <div class="alert__title">
                                {if $status == 'success'}{$btr->product_rating_updater_success|escape}{else}{$btr->product_rating_updater_nothing_updated|escape}{/if}
                            </div>
                            <p>{$btr->product_rating_updater_updated_count|sprintf:$count|escape}</p>
                        </div>
                    </div>
                {/if}
                <form method="post">
                    <input type="hidden" name="session_id" value="{$smarty.session.id}">

                    <div class="row">
                        <div class="col-md-6 col-lg-6">
                            <div class="okay_type_radio_wrap">
                                <input id="radio1" type="radio" class="hidden_check" name="update_type" value="all">
                                <label for="radio1" class="okay_type_radio">
                                    <span>{$btr->product_rating_updater_all_products|escape}</span>
                                </label>

                                <input id="radio2" type="radio" class="hidden_check" name="update_type" value="missing"
                                    checked>
                                <label for="radio2" class="okay_type_radio">
                                    <span>{$btr->product_rating_updater_missing_products|escape}
                                        {if $countMissing}
                                            ({$countMissing})
                                        {/if}
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-1">

                        <div class="col-md-6 col-lg-6">
                            <div class="permission_block">
                                <div class="mb-1 text_dark">{$btr->product_rating_updater_rating|escape}</div>
                                <div class="row">
                                    <div class="col-md-6 col-lg-6">
                                        <div class="heading_label">
                                            <span>{$btr->product_rating_updater_from|escape}</span>
                                        </div>
                                        <input class="form-control" type="number" name="rating_min" min="1" max="5"
                                            step="0.1" value="4">
                                    </div>
                                    <div class="col-md-6 col-lg-6">
                                        <div class="heading_label">
                                            <span>{$btr->product_rating_updater_to|escape}</span>
                                        </div>
                                        <input class="form-control" type="number" name="rating_max" min="1" max="5"
                                            step="0.1" value="5">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-6">
                            <div class="permission_block">
                                <div class="mb-1 text_dark">{$btr->product_rating_updater_votes|escape}</div>
                                <div class="row">
                                    <div class="col-md-6 col-lg-6">
                                        <div class="heading_label">
                                            <span>{$btr->product_rating_updater_from|escape}</span>
                                        </div>
                                        <input class="form-control" type="number" name="votes_min" min="1" step="1"
                                            value="30">
                                    </div>
                                    <div class="col-md-6 col-lg-6">
                                        <div class="heading_label">
                                            <span>{$btr->product_rating_updater_to|escape}</span>
                                        </div>
                                        <input class="form-control" type="number" name="votes_max" min="1" step="1"
                                            value="250">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <button type="submit" class="btn btn_blue">{$btr->product_rating_updater_update_btn|escape}</button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>