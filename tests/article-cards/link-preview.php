<?php
/** Real WordPress checks; all remote traffic is intercepted in this fixture. */
function preview_check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
preview_check(class_exists('HS_Article_Link_Preview'), 'Link preview boundary must be registered');
$id = wp_insert_post(['post_title'=>'Link preview fixture','post_status'=>'publish','post_content'=>'<p>Original body</p>']);
$post = get_post($id);
$url = 'https://example.org/guide';
$original = '<p><a href="'.$url.'">Guide</a> <a href="#section">Anchor</a> <a class="hs-card-tooltip" href="https://example.org/card">Card</a></p>';
$markup = HS_Article_Link_Preview::markup($original, $post);
preview_check(substr_count($markup, 'data-hs-preview=')===1, 'Only article page links receive previews');
preview_check(get_post($id)->post_content==='<p>Original body</p>', 'Decorating never rewrites stored content');
$request = new WP_REST_Request('GET', '/hs-article-cards/v1/preview');
$request->set_param('post', $id); $request->set_param('url', $url);
$request->set_param('signature', HS_Article_Link_Preview::signature($post, $url));
preview_check(HS_Article_Link_Preview::permission($request)===true, 'Public signed URL is allowed');
$request->set_param('url', 'https://example.org/other');
preview_check(is_wp_error(HS_Article_Link_Preview::permission($request)), 'Cannot fetch an arbitrary unsigned URL');
$request->set_param('url', $url);
$calls = 0;
delete_transient('hs_link_preview_' . hash('sha256', $url));
$transport = function($pre, $args, $requested) use (&$calls, $url) {
 if ($requested !== $url) return $pre;
 ++$calls;
 preview_check($args['redirection']===0 && $args['timeout']<=4, 'Bound network calls');
 return ['response'=>['code'=>200],'headers'=>['content-type'=>'text/html; charset=utf-8'],'body'=>'<title>Fallback</title><meta property="og:title" content="Test &amp; guide"><meta property="og:description" content="Public summary"><meta property="og:image" content="https://example.org/cover.jpg">'];
};
add_filter('pre_http_request', $transport, 10, 3);
$data = HS_Article_Link_Preview::metadata($request);
preview_check($data['title']==='Test & guide' && $data['description']==='Public summary', 'OG metadata is plain text');
HS_Article_Link_Preview::metadata($request);
preview_check($calls===1, 'Repeated hover reuses cache');
remove_filter('pre_http_request', $transport, 10);
foreach ([401, 429, 500, 'timeout', 'invalid'] as $failure) {
 $failed_url = 'https://example.org/failure-' . $failure;
 delete_transient('hs_link_preview_' . hash('sha256', $failed_url));
 $request->set_param('url', $failed_url);
 $request->set_param('signature', HS_Article_Link_Preview::signature($post, $failed_url));
 $fail = function($pre, $args, $requested) use ($failure, $failed_url) {
  if ($requested !== $failed_url) return $pre;
  if ($failure==='timeout') return new WP_Error('http_request_failed','Timeout');
  return ['response'=>['code'=>is_int($failure)?$failure:200],'headers'=>['content-type'=>'application/json'],'body'=>'invalid'];
 };
 add_filter('pre_http_request', $fail, 10, 3);
 $failed = HS_Article_Link_Preview::metadata($request);
 preview_check($failed['title']==='example.org' && $failed['image']==='', 'Failure must retain a usable text preview');
 remove_filter('pre_http_request', $fail, 10);
}
wp_update_post(['ID'=>$id, 'post_password'=>'private-fixture']);
$request->set_param('signature', HS_Article_Link_Preview::signature(get_post($id), $request->get_param('url')));
preview_check(is_wp_error(HS_Article_Link_Preview::permission($request)), 'Protected posts cannot authorize previews');
wp_update_post(['ID'=>$id, 'post_status'=>'draft']);
$request->set_param('signature', HS_Article_Link_Preview::signature(get_post($id), $request->get_param('url')));
preview_check(is_wp_error(HS_Article_Link_Preview::permission($request)), 'Unpublished posts cannot authorize previews');
wp_delete_post($id, true);
echo "Link preview boundary: PASS\n";
