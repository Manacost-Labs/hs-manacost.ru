<!-- Angular Material Dependencies -->

<style>
    body {
        font-size: 1rem;
    }

    #wpbody-content > div.error {
        display: none;
    }

    #wpwrap {
        background-color: #FFFFFF;

    }

    md-checkbox {
        margin: 0 !important;
    }

    md-list-item.disable-padding .md-no-style {
        padding: 0px !important;
        padding-left: 2px !important;
    }

    md-toolbar.cackle-errors, md-toolbar.cackle-errors .md-toolbar-tools {
        min-height: 36px !important;
        max-height: 36px !important;
    }

    md-toolbar.cackle-errors, md-toolbar.cackle-errors .md-toolbar-tools h1 span {
        font-size: 16px;
    }

    md-content.cackle-errors {
        font-size: 14px;
    }

    spinner svg {
        height: 32px;
        width: 32px;
    }
    .success{
        color:#008000;
    }
    .warn{
        color:#ff0000;
    }
    md-switch div.md-container{
        margin-right: 0px !important;
    }


</style>
<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.3.15/angular.min.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/angularjs/1.3.15/angular-messages.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/angularjs/1.3.15/angular-sanitize.js"></script>

<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.3.15/angular-animate.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.3.15/angular-aria.min.js"></script>

<script src="https://ajax.googleapis.com/ajax/libs/angular_material/0.10.0/angular-material.min.js"></script>

<?php

wp_enqueue_style('dashboard_style', 'https://ajax.googleapis.com/ajax/libs/angular_material/0.10.0/angular-material.min.css');


wp_enqueue_script('dashboard_script6', plugins_url('cackle_min_v1.js', __FILE__));

//wp_enqueue_script('dashboard_script1', plugins_url('/cackle-admin/dev/settings-module/index.js', __FILE__));
//wp_enqueue_script('dashboard_script2', plugins_url('/cackle-admin/dev/settings-module/controllers/index.js', __FILE__));
//wp_enqueue_script('dashboard_script3', plugins_url('/cackle-admin/dev/settings-module/controllers/settings.ctrl.js', __FILE__));
//wp_enqueue_script('dashboard_script4', plugins_url('/cackle-admin/dev/settings-module/services/index.js', __FILE__));
//wp_enqueue_script('dashboard_script5', plugins_url('/cackle-admin/dev/settings-module/services/cackle_api.js', __FILE__));
//wp_enqueue_script('dashboard_script6', plugins_url('/cackle-admin/dev/app.js', __FILE__));



wp_localize_script('dashboard_script6', 'cackle_locale', array(
    'Cackle plugin installation' => __('Cackle plugin installation', 'cackle'),
    'Check status' => __('Check status', 'cackle'),
    'Export comments' => __('Export comments', 'cackle'),
    'Sync comments' => __('Sync comments', 'cackle'),
    'Cackle plugin status and account availiable options' => __('Cackle plugin status and account availiable options', 'cackle'),
    'Warnings and errors' => __('Warnings and errors', 'cackle'),
    'Enable SSO' => __('Enable SSO', 'cackle'),
    'Enable sync(SEO)' => __('Enable sync(SEO)', 'cackle'),
    'Counter' => __('Comments counter in post number or word', 'cackle'),
    'Activate' => __('Activate', 'cackle'),
    'Plugin activated' => __('Plugin activated', 'cackle'),
    'Single sign on' => __('Single sign on', 'cackle'),
    'This will export your existing WordPress comments to Cackle' => __('This will export your existing WordPress comments to Cackle', 'cackle'),
    'This will download your Cackle comments and store them locally in WordPress' => __('This will download your Cackle comments and store them locally in WordPress', 'cackle'),
    'Start' => __('Start', 'cackle'),
    'Continue' => __('Continue', 'cackle'),
    'Stop' => __('Stop', 'cackle'),
    'Cackle widget language' => __('Cackle widget language', 'cackle'),
    'Export process' => __('Export process', 'cackle'),
    'Sync process' => __('Sync process', 'cackle'),
    'Stop' => __('Stop', 'cackle'),

    'export_Processed comments for post_id = ' => __('Processed export comments for post_id = ', 'cackle'),
    'export_Processed comments was stopped for post_id = ' => __('Processed export comments was stopped for post_id = ', 'cackle'),
    'export_All comments were transfer successfully to Cackle!' => __('All comments were exported successfully to Cackle!', 'cackle'),

    'sync_Processed comments for post_id = ' => __('Processed sync comments for post_id = ', 'cackle'),
    'sync_Processed comments was stopped for post_id = ' => __('Processed sync comments was stopped for post_id = ', 'cackle'),
    'sync_All comments were transfer successfully to Cackle!' => __('All comments were synchronized successfully to Cackle!', 'cackle'),

    'curl_exist_error' => __('You need to enable curl extension in your hosting server, and then click to Activate button again.', 'cackle'),
    'curl_openbase_error' => __('Open_basedir have some value and sync cannot work with it. Go to the php.ini and set ; before it to disable, and then click to Activate button again.', 'cackle'),
    'curl_safemode_error' => __('Safe mode is enabled and sync cannot work with it. Go to the php.ini and set safe_mode = off to disable, and then click to Activate button again.', 'cackle'),
    'php_error' => __('You should use php 5.3 at least for proper working', 'cackle'),
    'Warning' => __('Warning', 'cackle'),
    'Success' => __('Success', 'cackle'),
    'Plugin was successfully activated!' => __('Plugin was successfully activated!', 'cackle'),
    'The entered keys are wrong. Please check it again. Plugin was not activated' => __('The entered keys are wrong. Please check it again. Plugin was not activated', 'cackle'),
    'Plugin was successfully activated' => __('Plugin was successfully activated', 'cackle'),
    'Plugin was not activated, check keys' => __('Plugin was not activated, check keys', 'cackle'),
    'Cackle widget language' => __('Cackle widget language', 'cackle'),
    'Paid Single Sign On option' => __('Paid Single Sign On option', 'cackle'),
    'Paid white label option' => __('Paid white label option', 'cackle'),
    'Posts prepared for import' =>__('Posts prepared for import', 'cackle'),
    'with specified error: ' => __('with specified error: ','cackle'),
    'Error 500. Unable to connect server. Check server or internet' => __('Error 500. Unable to connect server. Check server or internet','cackle'),
    'Unable to connect with Cackle'=>__('Unable to connect with Cackle','cackle'),
    'Last successfull exported comments was for post_id = '=>__('Last successfull exported comments was for post_id = ','cackle'),
    'Last successfull synced comments was for post_id = '=>__('Last successfull synced comments was for post_id = ','cackle'),
    '(comments)' => __('(comments)','cackle'),
    'Counter_rubrics' => __('Comments counter in preview number or word','cackle')

));

$settings[] = Array(
    'siteId' => get_option('cackle_apiId', ''),
    'siteApiKey' => get_option('cackle_siteApiKey', ''),
    'accountApiKey' => get_option('cackle_accountApiKey', ''),
    'sso' => (get_option('cackle_sso', '') == 1) ? true : false,
    'sync' => (get_option('cackle_sync', '') == 1) ? true : false,
    'counter' => (get_option('cackle_counter', '') == 1) ? true : false,
    'counter_rubrics' => (get_option('cackle_counter_rubrics', 1) == 1) ? true : false,
    'manual_sync' => get_option('cackle_manual_sync'),
    'manual_export' => get_option('cackle_manual_export'),
    'curl_exist_error' => !function_exists('curl_version') ? true : false,
    'curl_openbase_error' => (ini_get('open_basedir') != '') ? true : false,
    'curl_safemode_error' => (ini_get('safe_mode') == true) ? true : false,
    'php_error' => (version_compare(PHP_VERSION, '5.3.0', '<=')) ? true : false

);
$settings = json_encode($settings);

$status[] = Array(
    __('Paid white label option', 'cackle') => get_option("cackle_whitelabel", ''),
    __('Cackle widget language', 'cackle') => get_option("cackle_lang", ''),
    __('Paid Single Sign On option', 'cackle') => get_option("cackle_sso", ''),
    __('Plugin activated', 'cackle') => get_option('cackle_correctKey', '')
);

$status = json_encode($status);
?>

<script type="application/javascript">
    cackle_admin = {};
    cackle_admin.settings = JSON.parse('<?php echo($settings)?>')[0];
    cackle_admin.url = '<?php print_r(admin_url('index.php')) ?>';
    cackle_admin.status = JSON.parse('<?php echo($status)?>')[0];


</script>
<div ng-app="cackle-admin.Angular">
    <div ng-controller="settings.ctrl">
        <div ng-include src="'main.html'"></div>
    </div>
    <script type="text/ng-template" id="main.html">
        <div layout-margin="10" layout="row" layout-sm="column" layout-md="column" layout-wrap>
            <div class="md-whiteframe-z1" flex-sm flex-md flex>
                <md-toolbar class="md-primary md-default-theme">
                    <div class="md-toolbar-tools">
                        <h1>
                            <span>{{ locale['Cackle plugin installation']}}</span>
                        </h1>
                    </div>
                </md-toolbar>
                <md-content layout-padding md-default-theme>
                    <form name="userForm">
                        <div layout-sm="column">
                            <md-input-container>
                                <label for="input-1">Widget ID</label>
                                <input type="text" id="input-1" ng-model="initData.siteId">
                            </md-input-container>
                        </div>
                        <div layout-sm="column">
                            <md-input-container>
                                <label for="inputId">accountApiKey</label>
                                <input name="accountApiKey" type="text" ng-model="initData.accountApiKey" required
                                       md-maxlength="64" minlength="4">

                                <div ng-messages="userForm.accountApiKey.$error"
                                     ng-show="userForm.accountApiKey.$dirty">
                                    <div ng-message="required">This is required!</div>
                                    <div ng-message="md-maxlength">That's too long!</div>
                                    <div ng-message="minlength">That's too short!</div>
                                </div>
                            </md-input-container>
                        </div>
                        <div layout-sm="column">
                            <md-input-container>
                                <label for="inputId">siteApiKey</label>
                                <input name="siteApiKey" type="text" ng-model="initData.siteApiKey" required
                                       md-maxlength="64" minlength="4">

                                <div ng-messages="userForm.siteApiKey.$error" ng-show="userForm.siteApiKey.$dirty">
                                    <div ng-message="required">This is required!</div>
                                    <div ng-message="md-maxlength">That's too long!</div>
                                    <div ng-message="minlength">That's too short!</div>
                                </div>
                            </md-input-container>
                            <md-list>
                                <md-list-item class="">
                                    <p>{{locale['Enable SSO']}}</p>
                                    <md-checkbox ng-model="initData.sso" class="md-primary"></md-checkbox>
                                </md-list-item>
                                <md-list-item class="">
                                    <p>{{locale['Enable sync(SEO)']}}</p>
                                    <md-checkbox ng-model="initData.sync" class="md-primary"></md-checkbox>
                                </md-list-item>
                                <md-list-item class="">
                                    <p>{{locale['Counter']}}</p>
                                    <md-switch class="md-primary" style="margin:0px !important;padding-left: 10px;" ng-model="initData.counter" aria-label="">

                                    </md-switch>
                                </md-list-item>
                                <md-list-item class="">
                                    <p>{{locale['Counter_rubrics']}}</p>
                                    <md-switch class="md-primary" style="margin:0px !important;padding-left: 10px;" ng-model="initData.counter_rubrics" aria-label="">

                                    </md-switch>
                                </md-list-item>
                            </md-list>
                            <md-button ng-click="activate()" class="md-raised md-primary">{{locale['Activate']}}
                            </md-button>
                            <md-subheader ng-if="object_keys(messages).length>0" class="md-no-sticky">{{locale['Warnings
                                and errors']}}
                            </md-subheader>
                            <div ng-repeat="message in messages">
                                <div error-message error="message.text" header="message.header"
                                     errorclass="message.class"></div>
                            </div>
                        </div>
                    </form>
                </md-content>
            </div>
            <div class="md-whiteframe-z1" flex-sm flex>
                <md-toolbar class="md-primary md-default-theme">
                    <div class="md-toolbar-tools">
                        <h1>
                            <span ng-bind="locale['Check status']"></span>
                        </h1>
                    </div>
                </md-toolbar>
                <md-content layout-padding md-default-theme>
                    <md-list>
                        <md-list-item>
                            <h2 ng-if="status[locale['Plugin activated']]||status['correctKey']==true">
                                {{locale['Plugin was successfully activated']}}</h2>

                            <h2 ng-if="status[locale['Plugin activated']]==false || status['correctKey']==false">
                                {{locale['Plugin was not activated, check keys']}}</h2>
                        </md-list-item>
                    </md-list>
                    <md-list>
                        <md-subheader class="md-no-sticky">
                            {{locale['Cackle plugin status and account availiable options']}}
                        </md-subheader>

                        <md-list-item
                            ng-repeat="(k,v) in (filtered = (status|objToArray:locale['Cackle widget language']))">

                            <p ng-hide="k=='correctKey'||k=='whitelabel'||k=='sso'||k=='lang'"> {{ k }} </p>
                            <span ng-show="k==locale['Cackle widget language']">{{v}}</span>
                            <p ng-show="k=='correctKey'">{{locale['Plugin activated']}}</p>
                            <p ng-show="k=='whitelabel'">{{locale['Paid white label option']}}</p>
                            <p ng-show="k=='sso'">{{locale['Paid Single Sign On option']}}</p>
                            <p ng-show="k=='lang'">{{locale['Cackle widget language']}}</p>
                            <span ng-show="k=='lang'">{{v}}</span>
                            <md-checkbox ng-disabled="true" ng-hide="k==locale['Cackle widget language']||k=='lang'"
                                         class="md-primary" ng-model="v"></md-checkbox>
                        </md-list-item>
                        <md-divider></md-divider>
                    </md-list>
                </md-content>
            </div>
        </div>
        <div layout-margin="10" layout="row" layout-wrap layout-sm="column"  layout-md="column">
            <div class="md-whiteframe-z1" flex-sm flex flex-md>
                <md-toolbar class="md-primary md-default-theme">
                    <div class="md-toolbar-tools">
                        <h1>
                            <span>{{locale['Export comments']}}</span>
                        </h1>
                    </div>
                </md-toolbar>
                <md-content layout-padding>
                    <p>{{locale['This will export your existing WordPress comments to Cackle']}}</p>
                    <md-button  ng-disabled="transfer['export'].status" ng-click="transferStart('export','start')" class="md-raised md-primary">{{locale['Start']}}
                    </md-button>
                    <md-button  ng-disabled="transfer['export'].status" ng-click="transferStart('export','continue')" class="md-raised md-primary">{{locale['Continue']}}
                    </md-button>
                    <md-button ng-click="transferStop('export')" class="md-raised md-primary">{{locale['Stop']}}
                    </md-button>

                    <md-list>
                        <md-subheader ng-show="transfer['export']" class="md-no-sticky">{{locale['Export process']}}</md-subheader>
                        <md-list-item ng-show="transfer['export'].spinner"><spinner></spinner></md-list-item>
                        <md-list-item ng-repeat="mess in ((transfer['export'].messages|limitTo:-5)) track by $index">
                            <span ng-bind-html="mess"></span>
                        </md-list-item>
                    </md-list>
                </md-content>

            </div>
            <div class="md-whiteframe-z1" flex-sm flex>
                <md-toolbar class="md-primary md-default-theme">
                    <div class="md-toolbar-tools">
                        <h1>
                            <span>{{locale['Sync comments']}}</span>
                        </h1>
                    </div>
                </md-toolbar>
                <md-content layout-padding>
                    <p>{{locale['This will download your Cackle comments and store them locally in WordPress']}}</p>
                    <md-button ng-disabled="transfer['sync'].status || commentLoading" ng-click="initCommentsPrepare()" class="md-raised md-primary">{{locale['Start']}}
                    </md-button>
                    <md-button  ng-disabled="transfer['sync'].status || commentLoading" ng-click="transferStart('sync','continue')" class="md-raised md-primary">{{locale['Continue']}}
                    </md-button>
                    <md-button ng-click="transferStop('sync')" class="md-raised md-primary">{{locale['Stop']}}
                    </md-button>
                    <p ng-show="commentLoading">{{locale['Posts prepared for import']}} <b>{{comments_prepared_counter}}</b></p>
                    <md-list>
                        <md-subheader ng-show="transfer['sync']" class="md-no-sticky">{{locale['Sync process']}}</md-subheader>
                        <md-list-item ng-show="transfer['sync'].spinner"><spinner></spinner></md-list-item>
                        <md-list-item ng-repeat="mess in ((transfer['sync'].messages|limitTo:-5)) track by $index">
                            <span ng-bind-html="mess"></span>
                        </md-list-item>
                    </md-list>
                </md-content>
            </div>
        </div>


    </script>

    <script type="text/ng-template" id="scopeTemplate">
        <div layout="row">
            <div flex-sm flex class="md-whiteframe-z5" layout-margin>

                <md-toolbar class="cackle-errors {{errorClass}}">
                    <div class="md-toolbar-tools">
                        <h1>
                            <span>{{header}}</span>
                        </h1>
                    </div>
                </md-toolbar>
                <md-content class="cackle-errors" layout-padding ng-bind-html="error"></md-content>


            </div>
        </div>
    </script>
</div>


