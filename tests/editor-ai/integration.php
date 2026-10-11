<?php
/** Real WordPress storage/permissions/queue/provider regression checks. */
use Manacost\EditorAi\Jobs;
use Manacost\EditorAi\Provider;
use Manacost\EditorAi\Catalog;
function ai_check( $condition, $name ) {
	if ( ! $condition ) { throw new RuntimeException( $name ); }
	echo 'PASS ' . $name . "\n";
}
wp_set_current_user( 1 );
$post = wp_insert_post( array( 'post_title' => 'Disposable editor AI regression', 'post_content' => '<p>Эта карта стоит 3 мнаны. Лирой Дженкинс — финишер.</p>', 'post_status' => 'draft', 'post_author' => 1 ), true );
ai_check( is_int( $post ), 'disposable draft' );
update_option( 'hs_editor_ai_test_post', $post, false );
function ai_start( $post, $mode, $source, $context = '' ) {
	$request = new WP_REST_Request( 'POST', '/hs-editor-ai/v1/jobs' );
	$request->set_body_params( array( 'post_id' => $post, 'mode' => $mode, 'source' => $source, 'context' => $context ?: $source ) );
	return rest_do_request( $request );
}
$original = get_post_field( 'post_content', $post );
$first = ai_start( $post, 'proofread', $original );
$id = $first->get_data()['id'];
ai_check( 200 === $first->get_status(), 'authorized request queues' );
ai_check( wp_next_scheduled( 'hs_editor_ai_process', array( $id ) ) > 0, 'background event exists' );
ai_check( $id === ai_start( $post, 'proofread', $original )->get_data()['id'], 'inflight deduplication' );
ai_check( 429 === ai_start( $post, 'proofread', 'Другая статья.' )->get_status(), 'one pending operation per editor' );
Jobs::process( $id );
$result = get_transient( 'hs_editor_ai_' . $id );
ai_check( 'ready' === $result['state'] && str_contains( $result['result'], '3 маны' ), 'queued typo correction' );
ai_check( ! isset( $result['source'], $result['context'] ), 'source removed after completion' );
ai_check( $original === get_post_field( 'post_content', $post ), 'worker never mutates stored post' );
$calls = (int) get_option( 'hs_editor_ai_test_calls' );
Jobs::process( $id );
ai_check( $id === ai_start( $post, 'proofread', $original )->get_data()['id'] && $calls === (int) get_option( 'hs_editor_ai_test_calls' ), 'replay and ready deduplication avoid billing' );
$existing_subscriber = get_user_by( 'login', 'ai-subscriber' );
$subscriber = $existing_subscriber ? $existing_subscriber->ID : wp_create_user( 'ai-subscriber', wp_generate_password(), 'ai-subscriber@example.invalid' );
wp_set_current_user( $subscriber );
ai_check( 403 === ai_start( $post, 'proofread', $original )->get_status(), 'subscriber denied' );
$lookup = new WP_REST_Request( 'GET', '/hs-editor-ai/v1/jobs/' . $id );
ai_check( 403 === rest_do_request( $lookup )->get_status(), 'unauthorized lookup denied' );
wp_set_current_user( 1 );
foreach ( array( 'cards', 'editor' ) as $mode ) {
	$job = ai_start( $post, $mode, $original )->get_data()['id'];
	Jobs::process( $job );
	ai_check( 'ready' === get_transient( 'hs_editor_ai_' . $job )['state'], $mode . ' workflow' );
}
update_option( 'hs_editor_ai_test_response', 'reject-review' );
$job = ai_start( $post, 'editor', 'Она стоит 3 мнаны.' )->get_data()['id'];
Jobs::process( $job );
ai_check( 'failed' === get_transient( 'hs_editor_ai_' . $job )['state'], 'independent review rejects changes' );
foreach ( array( 'timeout', '401', '429', '500', 'malformed', 'truncated' ) as $variant ) {
	delete_transient( 'hs_editor_ai_api_cooldown' );
	update_option( 'hs_editor_ai_test_response', $variant );
	$value = Provider::complete( 'fixture', array() );
	ai_check( is_wp_error( $value ) && ! str_contains( $value->get_error_message(), 'PRIVATE' ), 'redacted failure ' . $variant );
	if ( '429' === $variant ) {
		$calls = (int) get_option( 'hs_editor_ai_test_calls' );
		Provider::complete( 'fixture', array() );
		ai_check( $calls === (int) get_option( 'hs_editor_ai_test_calls' ), '429 cooldown without retry' );
	}
}
delete_transient( 'hs_editor_ai_api_cooldown' );
update_option( 'hs_editor_ai_test_response', 'normal' );
$boundary_first = ai_start( $post, 'proofread', 'Первый', 'Второй' )->get_data()['id'];
Jobs::process( $boundary_first );
$boundary_second = ai_start( $post, 'proofread', 'ПервыйВ', 'торой' )->get_data()['id'];
ai_check( $boundary_first !== $boundary_second, 'source/context boundaries do not collide in cache identity' );
Jobs::process( $boundary_second );
putenv( 'HS_EDITOR_AI_API_KEY' );
ai_check( ! Provider::enabled() && is_wp_error( Provider::complete( 'fixture', array() ) ), 'missing credential disables provider' );
putenv( 'HS_EDITOR_AI_API_KEY=integration-fixture' );
$job = ai_start( $post, 'proofread', 'Тест срока.' )->get_data()['id'];
Jobs::expire( $job );
ai_check( 'failed' === get_transient( 'hs_editor_ai_' . $job )['state'] && ! get_option( 'hs_editor_ai_lock_1' ), 'abandoned job expires and releases slot' );
$same = array();
for ( $i = 0; $i < 250; ++$i ) { $same[] = array( 'tag' => 'hs_card', 'id' => 'TEST_' . $i, 'name' => 'Лирой тест ' . $i ); }
$same[] = array( 'tag' => 'hs_bg', 'id' => 'BG_001', 'name' => 'Лирой Дженкинс' );
ai_check( in_array( 'BG_001', array_column( Catalog::candidates( 'Лирой Дженкинс', $same ), 'id' ), true ), 'ordinary matches cannot starve BG candidates' );
update_option( 'hs_editor_ai_budget_1', array( 'hour' => (int) floor( time() / HOUR_IN_SECONDS ), 'count' => 20 ), false );
ai_check( 429 === ai_start( $post, 'proofread', 'Лимит часа.' )->get_status(), 'hourly billing limit' );
delete_option( 'hs_editor_ai_budget_1' );
echo "Editor AI integration: OK\n";
