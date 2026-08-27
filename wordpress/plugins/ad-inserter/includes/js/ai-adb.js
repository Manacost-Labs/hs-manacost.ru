if (typeof ai_adb_action !== 'undefined') {

function ai_adb_process_content () {

    var ai_adb_debugging = typeof ai_debugging !== 'undefined'; // 1
//    var ai_adb_debugging = false;

    if (ai_adb_debugging) console.log ('');
    if (ai_adb_debugging) console.log ("AI AD BLOCKING CONTENT PROCESSING", ai_adb_active);

    document.querySelectorAll ('.' + ai_adb_content_css_begin_class).forEach ((el, index) => {

      var ai_adb_parent = el.parentElement;

      if (ai_adb_parent.closest ('.ai-debug-block') != null) {
        ai_adb_parent = ai_adb_parent.parentElement.parentElement;
      }

      if (ai_adb_parent.closest ('.' + ai_block_class) != null) {
        ai_adb_parent = ai_adb_parent.parentElement;
      }

      if (ai_adb_debugging) console.log ("AI AD BLOCKING parent", ai_adb_parent.tagName, ai_adb_parent.getAttribute ("id") != null ? "id=\""+ ai_adb_parent.getAttribute ("id")+"\"" :'', ai_adb_parent.getAttribute ("class") != null ? "class=\""+ ai_adb_parent.getAttribute ("class")+"\"":'');

      var ai_adb_css = "display: none !important;";
      if (typeof el.getAttribute ("data-css") != "undefined") {
        ai_adb_css = el.dataset.css;
      }

      var ai_adb_selectors = "p";
      if (el.getAttribute ("data-selectors") != null) {
        var el_selectors = el.dataset.selectors;
        if (el_selectors == '') el_selectors = "p";
        ai_adb_selectors = el_selectors;
      }

      if (ai_adb_debugging) console.log ('AI AD BLOCKING CSS, css=\'' + ai_adb_css +'\'', "selectors='" + ai_adb_selectors + "'");

      var ai_adb_action = false;
      ai_adb_parent.querySelectorAll ('.' + ai_adb_content_css_begin_class +', .' + ai_adb_content_css_end_class + ', ' + ai_adb_selectors).forEach ((element, index) => {
        if (element.classList.contains (ai_adb_content_css_begin_class)) {element.remove (); ai_adb_action = true;}
        else if (element.classList.contains (ai_adb_content_css_end_class)) {element.remove (); ai_adb_action = false;}
        else if (ai_adb_action) {
          var ai_adb_style = element.getAttribute ("style");
          if (ai_adb_style == null) ai_adb_style = "";
            else {
              ai_adb_style = ai_adb_style.trim ();
              if (ai_adb_style != '' && ai_adb_style [ai_adb_style.length - 1] != ';') {
                ai_adb_style = ai_adb_style + ';';
              }
            }
          if (ai_adb_css != '') {
            ai_adb_css = ' ' + ai_adb_css;
          }

          if (ai_adb_debugging) console.log ("AI AD BLOCKING CSS:", element.tagName, element.getAttribute ("id") != null ? "id=\""+ element.getAttribute ("id")+"\"":'', element.getAttribute ("class") != null ? "class=\""+ element.getAttribute ("class")+"\"":'');

          element.setAttribute ("style", ai_adb_style + ai_adb_css);
        }
      });
    });

    document.querySelectorAll ('.' + ai_adb_content_delete_begin_class).forEach ((el, index) => {
      var ai_adb_parent = el.parentElement;

      if (ai_adb_parent.closest ('.ai-debug-block') != null) {
        ai_adb_parent = ai_adb_parent.parentElement.parentElement;
      }

      if (ai_adb_parent.closest ('.' + ai_block_class) != null) {
        ai_adb_parent = ai_adb_parent.parentElement;
      }

      if (ai_adb_debugging) console.log ("AI AD BLOCKING DELETE, parent", ai_adb_parent.tagName, ai_adb_parent.getAttribute ("id") != null ? "id=\""+ ai_adb_parent.getAttribute ("id")+"\"":'', ai_adb_parent.getAttribute ("class") != null ? "class=\""+ ai_adb_parent.getAttribute ("class")+"\"":'');

      var ai_adb_selectors = "p";
      if (el.getAttribute ("data-selectors") != null) {
        var el_selectors = el.dataset.selectors;
        if (el_selectors == '') el_selectors = "p";
        ai_adb_selectors = el_selectors;
      }

      if (ai_adb_debugging) console.log ("AI AD BLOCKING DELETE, selectors='" + ai_adb_selectors + "'");

      var ai_adb_action = false;

      ai_adb_parent.querySelectorAll ('.' + ai_adb_content_delete_begin_class + ', .' + ai_adb_content_delete_end_class + ', ' + ai_adb_selectors).forEach ((element, index) => {
        if (element.classList.contains (ai_adb_content_delete_begin_class)) {element.remove (); ai_adb_action = true;}
        else if (element.classList.contains (ai_adb_content_delete_end_class)) {element.remove (); ai_adb_action = false;}
        else if (ai_adb_action) {
          if (ai_adb_debugging) console.log ("AI AD BLOCKING DELETE:", element.tagName, element.getAttribute ("id") != null ? "id=\""+ element.getAttribute ("id")+"\"":'', element.getAttribute ("class") != null ? "class=\""+ element.getAttribute ("class")+"\"":'');

          element.remove ();
        }
      });

    });

    document.querySelectorAll ('.' + ai_adb_content_replace_begin_class).forEach ((el, index) => {
      var ai_adb_parent = el.parentElement;

      if (ai_adb_parent.closest ('.ai-debug-block') != null) {
        ai_adb_parent = ai_adb_parent.parentElement.parentElement;
      }

      if (ai_adb_parent.closest ('.' + ai_block_class) != null) {
        ai_adb_parent = ai_adb_parent.parentElement;
      }

      if (ai_adb_debugging) console.log ("AI AD BLOCKING REPLACE, parent", ai_adb_parent.tagName, "id=\""+ ai_adb_parent.getAttribute ("id") != null ? ai_adb_parent.getAttribute ("id")+"\"":'', ai_adb_parent.getAttribute ("class") != null ? "class=\""+ ai_adb_parent.getAttribute ("class")+"\"":'');

      var ai_adb_text = "";
      if (el.getAttribute ("data-text") != null) {
        ai_adb_text = el.dataset.text;
      }

      var ai_adb_css = "";
      if (el.getAttribute ("data-css") != null) {
        ai_adb_css = el.dataset.css;
      }

      var ai_adb_selectors = "p";
      if (el.getAttribute ("data-selectors") != null) {
        var el_selectors = el.dataset.selectors;
        if (el_selectors == '') el_selectors = "p";
        ai_adb_selectors = el_selectors;
      }

      if (ai_adb_debugging) console.log ("AI AD BLOCKING REPLACE, text=\'" + ai_adb_text + '\'', 'css=\'' + ai_adb_css +'\'', "selectors='" + ai_adb_selectors + "'");

      var ai_adb_action = false;
      ai_adb_parent.querySelectorAll ('.' + ai_adb_content_replace_begin_class + ', .' + ai_adb_content_replace_end_class + ', ' + ai_adb_selectors).forEach ((element, index) => {

        if (element.classList.contains (ai_adb_content_replace_begin_class)) {element.remove (); ai_adb_action = true;}
        else if (element.classList.contains (ai_adb_content_replace_end_class)) {element.remove (); ai_adb_action = false;}
        else if (ai_adb_action) {
          if (ai_adb_text.length != 0) {
            var n = Math.round (element.innerText.length / (ai_adb_text.length + 1));
            element.innerText = Array(n + 1).join(ai_adb_text + ' ').trim ();
          } else element.innerText = '';

          if (ai_adb_css != '') {
            var ai_adb_style = element.getAttribute ("style");
            if (ai_adb_style == null) ai_adb_style = "";
              else {
                ai_adb_style = ai_adb_style.trim ();
                if (ai_adb_style != '' && ai_adb_style [ai_adb_style.length - 1] != ';') {
                  ai_adb_style = ai_adb_style + ';';
                }
              }
            if (ai_adb_css != '') {
              ai_adb_css = ' ' + ai_adb_css;
            }
            element.setAttribute ("style", ai_adb_style + ai_adb_css);
          }

          if (ai_adb_debugging) console.log ("AI AD BLOCKING REPLACE:", element.tagName, element.getAttribute ("id") != null ? "id=\""+ element.getAttribute ("id")+"\"":'', element.getAttribute ("class") != null ? "class=\""+ element.getAttribute ("class")+"\"":'');
        }
      });
    });
}

function ai_adb_process_blocks (element) {
    var ai_adb_debugging = typeof ai_debugging !== 'undefined'; // 2
//    var ai_adb_debugging = false;

    if (typeof element == 'undefined') {
      element = document.querySelector ('body');
      if (ai_adb_debugging) console.log ('');
    }

    // Temp fix for jQuery elements
    // ***
    if (window.jQuery && window.jQuery.fn && element instanceof jQuery) {
      if (element.hasOwnProperty ('0')) {
        element = element [0];
      } else element = [];
    }

    var ai_adb_data = document.querySelector (b64d ("Ym9keQ==")).getAttribute (b64d (ai_adb_attribute));
    if (typeof ai_adb_data === "string") {
      var ai_adb_active = ai_adb_data == b64d ("bWFzaw==");
    } else {
        var ai_adb_active = null;
      }

    if (ai_adb_debugging) console.log ("AI AD BLOCKING block actions:", ai_adb_active, element.tagName + element.getAttribute ('class') != null ? ('.' + element.getAttribute ('class')) : '');

    if (typeof ai_adb_data === "string" && typeof ai_adb_active === "boolean") {

      if (ai_adb_debugging) console.log ("AI AD BLOCKING block actions checking");

      if (ai_adb_active) {

        var code_inserted = false;

        do {
          var code_insertion = false;

          // Don't use data () as the value will be cached - wrong value for tracking
          element.querySelectorAll (".ai-adb-hide").forEach ((el, i) => {
            el.style.display = 'none';
            el.style.visibility = 'hidden';

//            el.classList.remove ('ai-adb-hide'); // Should not be removed as some delayed action might be triggered after ad blocking is detected

            // Disable tracking
            var wrapping_div = el.closest ('div[data-ai]');
            if (wrapping_div != null && el.hasAttribute ("data-ai")) {
              var data = JSON.parse (b64d (wrapping_div.getAttribute ("data-ai")));
              if (typeof data !== "undefined" && data.constructor === Array) {
                data [1] = "";

                if (ai_adb_debugging) console.log ("AI AD BLOCKING TRACKING ", b64d (wrapping_div.getAttribute ("data-ai")), ' <= ', JSON.stringify (data));

                wrapping_div.setAttribute ("data-ai", b64e (JSON.stringify (data)));
              }
            }

            ai_disable_processing (el);

            if (ai_adb_debugging) {
              console.log ("AI AD BLOCKING HIDE", 'aiDebug' in el.dataset ? el.dataset.aiDebug : "");
            }
          });

          // after hide to update tracking data on replace
          // Don't use data () as the value will be cached - wrong value for tracking
          element.querySelectorAll (".ai-adb-show").forEach ((el, i) => {
            el.style.display = 'block';
            el.style.visibility = 'visible';

//            el.classList.remove ('ai-adb-show'); // Should not be removed as some delayed action might be triggered after ad blocking is detected

            if ('code' in el.dataset) {
              var adb_code = b64d (el.dataset.code);

              if (ai_adb_debugging) console.log ('AI AD BLOCKING SHOW INSERT CODE');
              if (ai_adb_debugging) console.log ('');

              el.innerHTML += adb_code;

              code_insertion = true;
              code_inserted = true;

              // Process rotations to set versions before tracking data is set
              if (typeof ai_process_elements == 'function') {
                ai_process_elements ();
              }
            }

            if (el.hasAttribute ('data-ai-tracking')) {
              var tracking_data = el.getAttribute ('data-ai-tracking');
              var wrapping_div = el.closest ('div[data-ai]');
              if (wrapping_div != null && wrapping_div.hasAttribute ("data-ai")) {
                if (el.classList.contains ('ai-no-tracking')) {
                  var data = JSON.parse (b64d (wrapping_div.getAttribute ("data-ai")));
                  if (typeof data !== "undefined" && data.constructor === Array) {
                    data [1] = "";
                    tracking_data = b64e (JSON.stringify (data));
                  }
                }

                if (ai_adb_debugging) console.log ("AI AD BLOCKING TRACKING ", b64d (wrapping_div.getAttribute ("data-ai")), ' <= ', b64d (tracking_data));

                wrapping_div.setAttribute ("data-ai", tracking_data);
              }
            }
            if (ai_adb_debugging) {
              console.log ("AI AD BLOCKING SHOW", 'aiDebug' in el.dataset ? el.dataset.aiDebug : "");
            }
          });
        } while (code_insertion);

        setTimeout (function() {
          if (typeof ai_process_impressions == 'function' && ai_tracking_finished == true) {
            ai_process_impressions ();
          }
          if (typeof ai_install_click_trackers == 'function' && ai_tracking_finished == true) {
            ai_install_click_trackers ();
          }
        }, 15);

        setTimeout (ai_adb_process_content, 10);
    } else {
        // Prevent tracking if block was not displayed because of cookie
        element.querySelectorAll ('.ai-adb-hide').forEach ((el, index) => {

          if (ai_adb_debugging) console.log ('AI ai-adb-hide', el, el.offsetHeight, el.closest ('.ai-adb-show') != null);

//          el.classList.remove ('ai-adb-hide'); // Should not be removed as some delayed action might be triggered after ad blocking is detected

          if (el.offsetHeight == 0 && el.closest ('.ai-adb-show') != null) {
            // Top level (not nested) block
            var wrapper = el.closest ('div[data-ai]');
            if (wrapper.hetAttribute ("data-ai")) {
              var data = JSON.parse (b64d (wrapper.getAttribute ("data-ai")));
              if (typeof data !== "undefined" && data.constructor === Array) {
                data [1] = "";

                if (ai_adb_debugging) console.log ("AI AD BLOCKING TRACKING DISABLED: ", b64d (wrapper.getAttribute ("data-ai")), ' <= ', JSON.stringify (data));

                wrapper.setAttribute ("data-ai", b64e (JSON.stringify (data)));

                // Hide block (wrapping div with margin)
                wrapper.classList.add ('ai-viewport-0');
                wrapper.style.display = 'none';
              }
            }

          }
        });

        element.querySelectorAll ('.ai-adb-show').forEach ((el, index) => {
          ai_disable_processing (el);

//          el.classList.remove ('ai-adb-show'); // Should not be removed as some delayed action might be triggered after ad blocking is detected

          if (ai_adb_debugging) console.log ('AI AD BLOCKING SHOW disable processing', el.tagName + el.getAttribute ('class') != null ? ('.' + el.getAttribute ('class')) : '');
        });
      }
    }

    if (ai_adb_debugging) console.log ("AI AD BLOCKING block actions END");
}

ai_adb_detection_type_log = function (n) {
  var type = ai_adb_detection_type (n);
  var ai_adb_events = document.querySelector ('#ai-adb-events');
  if (ai_adb_events != null) {
    var message = ai_adb_events.innerText;
    if (message != '') message = message + ', '; else message = message + ', EVENTS: ';
    message = message + n;
    ai_adb_events.innerText = message;
  }
  return type;
}

ai_adb_detection_type = function (n) {

  var ai_adb_debugging = typeof ai_debugging !== 'undefined'; // 3
//  var ai_adb_debugging = false;

  if (ai_adb_debugging) {
    switch (n) {
      case 0:
        return "0 debugging";
        break;
      case 1:
        return "1 ads create element";
        break;
      case 2:
        return "2 sponsors window var";
        break;
      case 3:
        return "3 banner element";
        break;
      case 4:
        return "4 custom selectors";
        break;
      case 5:
        return "5 ga";
        break;
      case 6:
        return "6 media.net";
        break;
      case 7:
        return "7 adsense";
        break;
      case 8:
        return "8 doubleclick.net";
        break;
      case 9:
        return "9 fun adblock 3";
        break;
      case 10:
        return "10 fun adblock 4";
        break;
      case 11:
        return "11 banner js";
        break;
      case 12:
        return "12 300x250 js";
        break;
      case 13:
        return "13 googlesyndication";
        break;
      case 14:
        return "14 quantserve.com";
        break;
      case 15:
        return "15 ezodn.com";         // disabled
        break;
      case 16:
        return "16 googletagmanager.com";
        break;
      case 17:
        return "17 dummyimage.com";
        break;
      default:
        return n;
        break;
    }
  } else return '';
}

var ai_adb_detected = function (n) {
  function waitForScript () {
      // AiCookies might be defined in an external script loaded after adb code runs
      if (typeof AiCookies !== "undefined"){
        setTimeout (function () {
          ai_adb_detected_actions (n);
        }, 2);
      } else {
          setTimeout (waitForScript, 250);
        }
  }

  waitForScript ();
}

var ai_disable_processing = function (element) {
  document.querySelectorAll ('.ai-lazy').forEach ((el, index) => {el.classList.remove ('ai-lazy');});                                            // Disable lazy loading
  document.querySelectorAll ('.ai-manual').forEach ((el, index) => {el.classList.remove ('ai-manual');});                                        // Disable manual loading
  document.querySelectorAll ('.ai-rotate').forEach ((el, index) => {el.classList.remove ('ai-unprocessed'); el.removeAttribute ('data-info');}); // Disable rotations
  document.querySelectorAll ('.ai-list-data').forEach ((el, index) => {el.classList.remove ('ai-list-data');});                                  // Disable lists
  document.querySelectorAll ('.ai-ip-data').forEach ((el, index) => {el.classList.remove ('ai-ip-data');});                                      // Disable IP lists
  document.querySelectorAll ('[data-code]').forEach ((el, index) => {el.removeAttribute ('data-code');});                                        // Disable insertions
}

var ai_adb_detected_actions = function (n) {

  var ai_adb_debugging = typeof ai_debugging !== 'undefined'; // 4
//  var ai_adb_debugging = false;


  // Temp fix for jQuery elements
  // ***
  if (window.jQuery && window.jQuery.fn && ai_adb_overlay instanceof jQuery) {
    if (ai_adb_overlay.hasOwnProperty ('0')) {
      ai_adb_overlay = ai_adb_overlay [0];
    } else ai_adb_overlay = [];
  }
  if (ai_adb_message_window.hasOwnProperty ('0')) {
    ai_adb_message_window = ai_adb_message_window [0];
  }

  if (ai_adb_debugging && n == 0) console.log ('');
  if (ai_adb_debugging) console.log ("AI AD BLOCKING DETECTED", ai_adb_detection_type_log (n));

  if (!ai_adb_active) {
    ai_adb_active = true;

    document.querySelector (b64d ("Ym9keQ==")).setAttribute (b64d (ai_adb_attribute), b64d ("bWFzaw=="));

      function ai_ready_ProcessElements () {
        ai_adb_process_blocks ();

//        if (code_inserted && typeof ai_process_elements == 'function') {
//          setTimeout (ai_process_elements, 20);
//        }
      }

      ai_ready (ai_ready_ProcessElements);

      if (ai_adb_debugging) console.log ("AI AD BLOCKING action check");
//        AiCookies.remove (ai_adb_pgv_cookie_name, {path: "/"});

      // Disable action for bots
      if (typeof MobileDetect !== "undefined") {
        var md = new MobileDetect (window.navigator.userAgent);

        if (ai_adb_debugging) console.log ('AI AD BLOCKING IS BOT:', md.is ('bot'));

        if (md.is ('bot')) {
          ai_adb_action = 0;
        }
      }

      if (ai_adb_page_views != '') {
        if (ai_adb_debugging) console.log ("AI AD BLOCKING page views delay:", ai_adb_page_views);
        if (ai_adb_page_views.includes (',')) {
          var ai_adb_page_view_parts = ai_adb_page_views.split (',');

          var ai_adb_page_view_delay = parseInt (ai_adb_page_view_parts [0]);
          var ai_adb_page_view_repeat = parseInt (ai_adb_page_view_parts [1]);

          if (ai_adb_debugging) console.log ("AI AD BLOCKING page views delay:", ai_adb_page_view_delay, "repeat:", ai_adb_page_view_repeat);
        } else {
            var ai_adb_page_view_delay = parseInt (ai_adb_page_views);
            var ai_adb_page_view_repeat = 0

            if (ai_adb_debugging) console.log ("AI AD BLOCKING page views delay:", ai_adb_page_view_delay);
          }

        var ai_adb_page_view_counter = 1;
        var cookie = AiCookies.get (ai_adb_pgv_cookie_name);
        if (typeof cookie != "undefined") ai_adb_page_view_counter = parseInt (cookie) + 1;
        if (ai_adb_debugging) console.log ("AI AD BLOCKING page views cookie:", cookie, "- page view:", ai_adb_page_view_counter);
        if (ai_adb_page_view_counter <= ai_adb_page_view_delay) {
          if (ai_adb_debugging) console.log ("AI AD BLOCKING", ai_adb_page_view_delay, "page views not reached, no action");
          AiCookies.set (ai_adb_pgv_cookie_name, ai_adb_page_view_counter, {expires: 365, path: "/"});
          window.ai_d1 = ai_adb_page_view_counter;
          ai_adb_message_code_1 ();
          return;
        }
        if (ai_adb_page_view_repeat != 0) {
          AiCookies.set (ai_adb_pgv_cookie_name, ai_adb_page_view_counter, {expires: 365, path: "/"});
          if ((ai_adb_page_view_counter - ai_adb_page_view_delay - 1) % ai_adb_page_view_repeat != 0) {
            if (ai_adb_debugging) console.log ("AI AD BLOCKING every", ai_adb_page_view_repeat, "page views, no action");
            window.ai_d1 = ai_adb_page_view_counter;
            ai_adb_message_code_1 ();
            return;
          }
        }
      }

      if (ai_adb_message_cookie_lifetime != 0 && (ai_adb_action != 1 || !ai_adb_message_undismissible)) {

        var cookie = AiCookies.get (ai_adb_act_cookie_name);
        if (ai_adb_debugging) console.log ("AI AD BLOCKING cookie:", cookie);
        if (typeof cookie != "undefined" && cookie == ai_adb_cookie_value) {
          if (ai_adb_debugging) console.log ("AI AD BLOCKING valid cookie detected, no action");
          ai_adb_message_code_2 ();
          return;
        }

        else if (ai_adb_debugging) console.log ("AI AD BLOCKING invalid cookie");
        AiCookies.set (ai_adb_act_cookie_name, ai_adb_cookie_value, {expires: ai_adb_message_cookie_lifetime, path: "/"});
      } else
          AiCookies.remove (ai_adb_act_cookie_name, {path: "/"});

      if (ai_adb_debugging) console.log ("AI AD BLOCKING action", ai_adb_action);

      if (ai_adb_action == 0) {
        ai_dummy = 16; // Do not remove - to prevent optimization
        ai_adb_message_code_6 ();
        ai_dummy ++;   // Do not remove - to prevent optimization
      } else {
          ai_adb_message_code_3 ();
          ai_dummy = 13; // Do not remove - to prevent optimization
        }

      switch (ai_adb_action) {
        case 1:
          if (!ai_adb_message_undismissible) {
            ai_adb_overlay.addEventListener ('click', (event) => {
              ai_adb_overlay.remove ();
              ai_adb_message_window.remove ();
            });
            ai_adb_message_window.addEventListener ('click', (event) => {
              ai_adb_message_window.remove ();
              ai_adb_overlay.remove ();
            });
            window.addEventListener ('keydown', (event) => {
              if (event.keyCode === 27 ) {
                ai_adb_overlay.click ();
                ai_adb_message_window.click ();
              }
            });

            if (ai_adb_debugging) console.log ("AI AD BLOCKING MESSAGE click detection installed");

          } else {
              ai_adb_overlay.querySelectorAll ('[style*="cursor"]').forEach ((el, index) => {
                el.style.cursor = 'no-drop';
              });
              ai_adb_message_window.querySelectorAll ('[style*="cursor"]').forEach ((el, index) => {
                el.style.cursor = 'no-drop';
              });

            }

          if (ai_adb_debugging) console.log ("AI AD BLOCKING MESSAGE");

          var body_children = document.querySelector (b64d ("Ym9keQ==")).children;
          insertAfter (ai_adb_overlay, body_children.item (Math.floor (Math.random () * body_children.length)));
          insertAfter (ai_adb_message_window, body_children.item (Math.floor (Math.random () * body_children.length)));

          break;
        case 2:
          if (ai_adb_redirection_url != "") {
            if (ai_adb_debugging) console.log ("AI AD BLOCKING REDIRECTION to", ai_adb_redirection_url);

            var redirect = true;
            if (ai_adb_redirection_url.toLowerCase().substring (0, 4) == "http") {
              if (window.location.href == ai_adb_redirection_url) var redirect = false;
            } else {
                if (window.location.pathname == ai_adb_redirection_url) var redirect = false;
              }

            if (redirect) {
              var cookie = AiCookies.get (ai_adb_page_redirection_cookie_name);
              if (typeof cookie == "undefined") {
                var date = new Date();
                date.setTime (date.getTime() + (10 * 1000));
                AiCookies.set (ai_adb_page_redirection_cookie_name, window.location.href, {expires: date, path: "/"});

                if (ai_adb_redirection_url.substr (ai_adb_redirection_url.length - 1) == "?") {
                  ai_adb_redirection_url = ai_adb_redirection_url.slice (0, - 1);
                  ai_adb_redirection_url = ai_adb_redirection_url + location.search;

                  if (ai_adb_debugging) console.log ("AI AD BLOCKING redirection using query parameters:", location.search);
                }

                window.location.replace (ai_adb_redirection_url)
              } else {
                  if (ai_adb_debugging) console.log ("AI AD BLOCKING no redirection, cookie:", cookie);

                }
            } else {
                if (ai_adb_debugging) console.log ("AI AD BLOCKING already on page", window.location.href);
                AiCookies.remove (ai_adb_page_redirection_cookie_name, {path: "/"});
              }
          }
          break;
      }

//    }(jQuery));
    // ***
  }
}


var ai_adb_undetected = function (n) {
  setTimeout (function() {
    if (!ai_adb_active) {
      ai_adb_undetected_actions (n);
    }
  }, 200);
}


var ai_adb_undetected_actions = function (n) {
  ai_adb_counter ++;

  var ai_adb_debugging = typeof ai_debugging !== 'undefined'; // 5
//  var ai_adb_debugging = false;

  if (ai_adb_debugging) console.log ("AI AD BLOCKING not detected:", '(' + ai_adb_counter + ')', ai_adb_detection_type (n));

  if (!ai_adb_active && ai_adb_counter == 4) {
    if (ai_adb_debugging) console.log ("AI AD BLOCKING NOT DETECTED");

      document.querySelector (b64d ("Ym9keQ==")).setAttribute (b64d (ai_adb_attribute), b64d ("Y2xlYXI="));

      ai_dummy = 11; // Do not remove - to prevent optimization
      ai_adb_message_code_4 ();
      ai_dummy = 14; // Do not remove - to prevent optimization

      ai_adb_process_blocks ();
  }
}

if (AI_DBG_AI_DEBUG_AD_BLOCKING) ai_ready (function () {ai_adb_detected (0);});

function ai_adb_checks () {

    var ai_adb_debugging = typeof ai_debugging !== 'undefined'; // 6
//    var ai_adb_debugging = false;

    ai_debugging_active = typeof ai_adb_fe_dbg !== 'undefined';

    setTimeout (function () {
      if (document.querySelector ('#ai-adb-bar') != null)
        document.querySelector ('#ai-adb-bar').addEventListener ('click', (event) => {
          AiCookies.remove (ai_adb_act_cookie_name, {path: "/"});
          AiCookies.remove (ai_adb_pgv_cookie_name, {path: "/"});
          ai_adb_message_code_5 ();
          ai_dummy = 15; // Do not remove - to prevent optimization
        });
    }, 5);


    if ((!ai_adb_active || ai_debugging_active) && ai_adb_selectors != "") {
      var ai_adb_el_counter = 0;
      var ai_adb_el_zero = 0;
      var ai_adb_selector = ai_adb_selectors.split (",");
      ai_adb_selector.forEach ((el, i) => {
        ai_adb_selector [i] = ai_adb_selector [i].trim ();

        if (ai_adb_debugging) console.log ("AI AD BLOCKING selector", ai_adb_selector [i]);

        if (document.querySelector (ai_adb_selector [i]) != null) {

          document.querySelectorAll (ai_adb_selector [i]).forEach ((el, index) => {

            var outer_height = el.offsetHeight;

            if (ai_adb_debugging) console.log ("AI AD BLOCKING element", el.getAttribute ("id") != null ? (" id=\"" + el.getAttribute ("id") + "\"") : '', el.getAttribute ("class") != null ? (" class=\"" + el.getAttribute ("class") + "\"") : '', "heights:", el.offsetHeight, el.clientHeight);

            el.querySelectorAll ('.ai-attributes').forEach ((element, index) => {
              if (ai_adb_debugging) console.log ("AI AD BLOCKING attributes height:", element.offsetHeight);
              if (outer_height >= element.offsetHeight) {
                outer_height -= element.offsetHeight;
              }
            });

            if (ai_adb_debugging) console.log ("AI AD BLOCKING effective height:", outer_height);

            ai_adb_el_counter ++;
            if (outer_height === 0) {
              ai_ready (function () {if (!ai_adb_active || ai_debugging_active) ai_adb_detected (4)});
              ai_adb_el_zero ++;
              if (!ai_debugging_active) return false;
            }

          });

        }
      });
      if (ai_adb_el_counter != 0 && ai_adb_el_zero == 0) ai_ready (function () {ai_adb_undetected (4)});
    }

}

function ai_adb_get_script (ai_adb_script, ai_adb_action) {
  var ai_adb_debugging = typeof ai_debugging !== 'undefined'; // 7
//  var ai_adb_debugging = false;

  if (ai_adb_debugging) console.log ("AI AD BLOCKING loading script", ai_adb_script);

  var script = document.createElement ('script');
  var date = new Date();
  script.src = 'ai-adb-url' + ai_adb_script + '.js?ver=' + date.getTime();

  var head = document.getElementsByTagName ('head')[0],
      done = false;

  // Attach handlers for all browsers

  script.onerror = function () {
    if (ai_adb_debugging) console.log ("AI AD BLOCKING error loading script", ai_adb_script);

    if (ai_adb_action) {
      ai_adb_action ();
    }
    script.onerror = null;
    head.removeChild (script);
  }

  script.onload = script.onreadystatechange = function () {
    if (!done && (!this.readyState || this.readyState == 'loaded' || this.readyState == 'complete')) {
      done = true;

      if (ai_adb_debugging) console.log ("AI AD BLOCKING script loaded ", ai_adb_script);

      if (ai_adb_action) {
        ai_adb_action ();
      }

      script.onload = script.onreadystatechange = null;
      head.removeChild (script);
    };
  };

  head.appendChild (script);
};

const ai_fetch_adb = async () => {
  try {
      const response = await fetch (b64d ('aHR0cHM6Ly9wYWdlYWQyLmdvb2dsZXN5bmRpY2F0aW9uLmNvbS9wYWdlYWQvanMvYWRzYnlnb29nbGUuanM='), {
          method: 'HEAD',
          cache: 'no-cache',
          credentials: 'same-origin'
      });

      if (!response.ok) {
          return false;
      }

      const contentLength = response.headers.get ('Content-Length');
      if (!contentLength) {
          return false;
      }

      return true;
  } catch (error) {
      return false;
  }
};

if (document.querySelector (b64d ("I2FpLWFkYi1ncw==")) != null) {
  window.addEventListener ('load', async () => {
     const fetchStatus = await ai_fetch_adb ();
     if (!fetchStatus) {
       ai_adb_detected (13);
     } else ai_adb_undetected (13);
  });
}

window.addEventListener ('load', (event) => {

  var ai_adb_debugging = typeof ai_debugging !== 'undefined'; // 8
//  var ai_adb_debugging = false;

  if (typeof MobileDetect !== "undefined") {
    var md = new MobileDetect (window.navigator.userAgent);

    // ENABLED FOR_ALL_DEVICES
    if (ai_adb_devices != 6) {

      if (ai_adb_debugging) console.log ('AI AD BLOCKING DEVICES:', ai_adb_devices);
      if (ai_adb_debugging) console.log ('AI AD BLOCKING DEVICE desktop',  !md.mobile ());
      if (ai_adb_debugging) console.log ('AI AD BLOCKING DEVICE  mobile', !!md.mobile ());
      if (ai_adb_debugging) console.log ('AI AD BLOCKING DEVICE   phone', !!md.phone ());
      if (ai_adb_debugging) console.log ('AI AD BLOCKING DEVICE  tablet', !!md.tablet ());

      switch (ai_adb_devices) {
        // ENABLED FOR DESKTOP_DEVICES
        case 0:
          if (!!md.mobile ()) return false;
          break;
        // ENABLED FOR MOBILE_DEVICES
        case 1:
          if (!md.mobile ()) return false;
          break;
        // ENABLED FOR TABLET_DEVICES
        case 2:
          if (!md.tablet ()) return false;
          break;
        // ENABLED FOR PHONE_DEVICES
        case 3:
          if (!md.phone ()) return false;
          break;
        // ENABLED FOR DESKTOP_TABLET_DEVICES
        case 4:
          if (!!md.phone ()) return false;
          break;
        // ENABLED FOR DESKTOP_PHONE_DEVICES
        case 5:
          if (!!md.tablet ()) return false;
          break;
      }
    }
  }

  if (ai_adb_debugging) console.log ("AI AD BLOCKING window load");

  function ai_adb_1 () {
    if (!document.getElementById (ai_adb_name_1)){
      if (!ai_adb_active || ai_debugging_active) ai_adb_detected (1);
    } else {
        ai_adb_undetected (1);
    }
  }

  function ai_adb_2 () {
    if (typeof window [ai_adb_name_2] == "undefined") {

      if (!ai_adb_active || ai_debugging_active) ai_adb_detected (2);
    } else {
        ai_adb_undetected (2);
      }
  }

  function ai_adb_11 () {
    if (typeof window.ad_banner == "undefined") {
      if (!ai_adb_active || ai_debugging_active) ai_adb_detected (11);
    } else {
        ai_adb_undetected (11);
      }
  }

  function ai_adb_12 () {
    if (typeof window.ad_300x250 == "undefined") {
      if (!ai_adb_active || ai_debugging_active) ai_adb_detected (12);
    } else {
        ai_adb_undetected (12);
      }
  }

  function ai_adb_external_scripts () {
    if (ai_adb_debugging) console.log ("AI AD BLOCKING check external scripts");

    var element = document.querySelector (b64d ("I2FpLWFkYi1nYQ=="));
    if (element != null) {
      if (!!(element.clientWidth * element.clientHeight)) {
        ai_adb_undetected (5);
      } else {
          if (!ai_adb_active || ai_debugging_active) ai_adb_detected (5);
        }
    }

    var element = document.querySelector (b64d ("I2FpLWFkYi1tbg=="));
    if (element != null) {
      if (!!(element.clientWidth * element.clientHeight)) {
        ai_adb_undetected (6);
      } else {
          if (!ai_adb_active || ai_debugging_active) ai_adb_detected (6);
        }
    }

    var element = document.querySelector (b64d ("I2FpLWFkYi1kYmxjbGs="));
    if (element != null) {
      if (!!(element.clientWidth * element.clientHeight)) {
        ai_adb_undetected (8);
      } else {
          if (!ai_adb_active || ai_debugging_active) ai_adb_detected (8);
        }
    }

    var element = document.querySelector (b64d ("I2FpLWFkYi1xdQ=="));
    if (element != null) {
      if (!!(element.clientWidth * element.clientHeight)) {
        ai_adb_undetected (14);
      } else {
          if (!ai_adb_active || ai_debugging_active) ai_adb_detected (14);
        }
    }

    var element = document.querySelector (b64d ("I2FpLWFkYi1leg=="));
    if (element != null) {
      if (!!(element.clientWidth * element.clientHeight)) {
        ai_adb_undetected (15);
      } else {
          if (!ai_adb_active || ai_debugging_active) ai_adb_detected (15);
        }
    }

    var element = document.querySelector (b64d ("I2FpLWFkYi1ndG0="));
    if (element != null) {
      if (!!(element.clientWidth * element.clientHeight)) {
        ai_adb_undetected (16);
      } else {
          if (!ai_adb_active || ai_debugging_active) ai_adb_detected (16);
        }
    }

    var element = document.querySelector (b64d ("I2FpLWFkYi1kaQ=="));
    if (element != null) {
      if (!!(element.clientWidth * element.clientHeight)) {
        ai_adb_undetected (17);
      } else {
          if (!ai_adb_active || ai_debugging_active) ai_adb_detected (17);
        }
    }
  }

  setTimeout (function() {
    if (ai_adb_debugging) console.log ("AI AD BLOCKING delayed checks external scripts");

    ai_adb_external_scripts ();

    // Check again, result is delayed
    setTimeout (function() {
      if (!ai_adb_active) {
        setTimeout (function() {
          ai_adb_external_scripts ();
        }, 400);
      }
    }, 5);
  }, 1050);

  setTimeout (function() {
    var ai_debugging_active = typeof ai_adb_fe_dbg !== 'undefined';

    if (ai_adb_debugging) console.log ("AI AD BLOCKING delayed checks 1, 2, 3, 11, 12");

    if (document.querySelector (b64d ("I2FpLWFkYi1hZHM=")) != null) {
      if (!document.getElementById (ai_adb_name_1)) {
        ai_adb_get_script ('ads', ai_adb_1);
      } else ai_adb_1 ();
    }

    if (document.querySelector (b64d ("I2FpLWFkYi1zcG9uc29ycw==")) != null) {
      if (typeof window [ai_adb_name_2] == "undefined") {
        ai_adb_get_script ('sponsors', ai_adb_2);
      } else ai_adb_2 ();
    }

    var banner_advert_container = b64d ("I2Jhbm5lci1hZHZlcnQtY29udGFpbmVy");
    var banner_advert_container_img = b64d ("I2Jhbm5lci1hZHZlcnQtY29udGFpbmVyIGltZw==");
    if (document.querySelector (banner_advert_container) != null) {

      if (document.querySelector (banner_advert_container_img) != null) {
        if (document.querySelector (banner_advert_container_img).offsetHeight === 0) {
          if (!ai_adb_active || ai_debugging_active) ai_adb_detected (3);
        } else ai_adb_undetected (3);
        document.querySelector (banner_advert_container_img).remove ();
      }
    }

    if (document.querySelector (b64d ("I2FpLWFkYi1iYW5uZXI=")) != null) {
      ai_adb_11 ();
    }

    if (document.querySelector (b64d ("I2FpLWFkYi0zMDB4MjUw")) != null) {
      ai_adb_12 ();
    }
  }, 1150);
});

function ai_ready (fn) {
  if (document.readyState === 'complete' || (document.readyState !== 'loading' && !document.documentElement.doScroll)) {
    fn ();
  } else {
     document.addEventListener ('DOMContentLoaded', fn);
  }
}
function insertAfter (newNode, referenceNode) {
  referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
}

ai_ready (ai_adb_checks);

}
