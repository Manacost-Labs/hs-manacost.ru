/* global jQuery, window, document, td_ajax_url, get_param_by_name, td_panel_navigate, show_content_panel, tdConfirm, tb_remove */
/* jshint esversion: 6 */
let tdTwitterAccount;

jQuery(document).ready( function($) {
    'use strict';

    tdTwitterAccount = {

        init: function() {

            const oauth_token = get_param_by_name('oauth_token'),
                oauth_token_secret = get_param_by_name('oauth_token_secret'),
                user_id = get_param_by_name('user_id'),
                screen_name = get_param_by_name('screen_name');

            // if we have oauth_token/oauth_token_secret tokens set in url save the twitter account data
            if ( oauth_token.length && oauth_token_secret.length ) {

                // navigate to the social networks section in theme panel
                td_panel_navigate('td-panel-social-networks');

                let twitter_account_panel_box = $('#' + $('.td_panel_box_twitter_account').prop('id'));

                // open the twitter_account panel box
                show_content_panel( twitter_account_panel_box, true, undefined, true );

                // process and save account data
                tdTwitterAccount.save({
                    oauth_token: oauth_token,
                    oauth_token_secret: oauth_token_secret,
                    user_id: user_id,
                    screen_name: screen_name,
                });

                // remove url query params ( oauth_token, oauth_token_secret, user_id, screen_name .. etc. )
                let clean_uri = window.location.protocol + "//" + window.location.host + window.location.pathname + '?page=td_theme_panel';
                window.history.replaceState({}, document.title, clean_uri );

            }

            // bind td twitter account ui events
            $(document).on('click', '.td-tw-remove-account' , function(event) {
                event.preventDefault();

                // remove connected twitter account data modal confirmation
                tdConfirm.showModal( 'Remove Connected Twitter Account Data',
                    window,
                    function() {

                        let tw_account_box = $('.td-box-control-tw-account');

                        // set loading state
                        tw_account_box.addClass('td-box-loading');

                        $.ajax({
                            url: td_ajax_url,
                            type: 'post',
                            data: {
                                action: 'td_remove_twitter_account'
                            },
                            success: function (data) {
                                let reply = JSON.parse(data);

                                if ( reply.status.includes('success') ) {
                                    // console.log( '%c' + reply.status, 'color: #008000c2' );

                                    tw_account_box.find('.about-wrap').addClass('td-no-tw-account-message').html('' +
                                        '<div class="td-tw-user-wrap">\n' +
                                        '   <p>No Twitter account connected!</p>\n' +
                                        '</div>\n'
                                    );

                                    $('.td-tw-connect-account').show();

                                }

                                if ( reply.status.includes('error') ) {
                                    let twErrorsWrap = $('#td-tw-error');

                                    reply.errors.forEach( error => {
                                        twErrorsWrap.append( '<div>' + error + '</div>' );
                                    });

                                    twErrorsWrap.show();
                                }

                                // remove loading state
                                tw_account_box.removeClass('td-box-loading');

                            },
                            error: function ( jqXHR, textStatus, errorThrown ) {
                                console.log( '%c' + errorThrown, 'color: #dc2121c7' );

                                // remove loading state
                                tw_account_box.removeClass('td-box-loading');

                            }
                        });
                        tb_remove();
                    },
                    [],
                    'Are you sure you want to remove this twitter account?<br>' +
                    'This action will remove your twitter account and all associated data.<br><br>'
                );

            });

        },

        save: function( accountData ) {

            let tw_account_box = $('.td-box-control-tw-account');

            // set loading state on tw-account-user td-box-control
            tw_account_box.addClass('td-box-loading');

            $.ajax({
                url: td_ajax_url,
                type: 'post',
                data: {
                    action: 'td_save_twitter_account',
                    accountData: accountData,
                },
                success: function (data) {

                    let reply = JSON.parse(data);

                    if ( reply.status.includes('success') ) {

                        // console.log( '%c' + reply.status, 'color: #008000c2' );
                        // console.group('saved twitter account data' );
                        // console.log( reply.twitter_account_data );
                        // console.groupEnd();

                        // $('.td-tw-connect-account').text('Reconnect Twitter Account');
                        $('.td-tw-connect-account').hide();

                        tw_account_box.empty();

                        tw_account_box.append(
                            '<div class="td-box-description">\n' +
                                '<span class="td-box-title">Twitter Account</span>\n' +
                                '<p>This is your connected twitter account.</p>\n' +
                            '</div>\n' +
                            '<div class="about-wrap">\n' +
                                '<div class="td-tw-user-wrap">\n' +
                                    '<div class="td-tw-account-user-name">' + accountData.screen_name + '</div>\n' +
                                    '<div class="td-tw-account-remove">\n' +
                                        '<span class="td-tw-remove-account dashicons-before dashicons-dismiss" title="Remove Connected Twitter Account"></span>\n' +
                                    '</div>\n' +
                                '</div>\n' +
                            '</div>\n'
                        );

                        tw_account_box.removeClass('td-box-loading');

                    }

                    if ( reply.status.includes('error') ) {

                        // log errors
                        // console.log( '%c' + reply.errors, 'color: #dc2121c7' );

                        let twErrorsWrap = $('#td-tw-error');

                        reply.errors.forEach( error => {
                            // append error
                            twErrorsWrap.append( '<div>' + error + '</div>' );
                        });

                        // show errors
                        twErrorsWrap.show();

                        tw_account_box.removeClass('td-box-loading');

                    }

                },
                error: function ( jqXHR, textStatus, errorThrown ) {
                    console.log( '%c' + errorThrown, 'color: #dc2121c7' );

                    // remove loading state
                    tw_account_box.removeClass('td-box-loading');

                }
            });

        }

    };

    tdTwitterAccount.init();

});
