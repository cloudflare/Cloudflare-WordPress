<?php

namespace Cloudflare\APO\Tests\Integration;

/**
 * Comments that become visible, or stop being visible, purge their post.
 */
class PurgeOnCommentTest extends PurgeTestCase
{
    /**
     * @var int
     */
    private $postId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->postId = $this->createPost(array('post_status' => 'publish', 'comment_status' => 'open'));
        $this->http->clearRequests();
    }

    public function testApprovedNewCommentPurgesThePost()
    {
        wp_set_current_user($this->createUser('administrator'));
        $this->http->clearRequests();

        $commentId = $this->newComment('Approved straight away');

        $this->assertSame('1', get_comment($commentId)->comment_approved);
        $this->assertContains(get_permalink($this->postId), $this->purgedUrls());
    }

    public function testHeldNewCommentDoesNotPurge()
    {
        $commentId = $this->newComment('Waiting for moderation');

        $this->assertSame('0', get_comment($commentId)->comment_approved);
        $this->assertSame(array(), $this->purgedUrls());
    }

    public function testApprovingAHeldCommentPurgesThePost()
    {
        $commentId = $this->newComment('Approve me later');
        $this->http->clearRequests();

        wp_set_comment_status($commentId, 'approve');

        $this->assertContains(get_permalink($this->postId), $this->purgedUrls());
    }

    public function testUnapprovingACommentPurgesThePost()
    {
        wp_set_current_user($this->createUser('administrator'));
        $commentId = $this->newComment('Unapprove me');
        $this->http->clearRequests();

        wp_set_comment_status($commentId, 'hold');

        $this->assertContains(get_permalink($this->postId), $this->purgedUrls());
    }

    /**
     * Submit a comment the way the comment form does. Visitors' first comments
     * are held for moderation; administrators' comments are approved.
     *
     * @param string $content
     *
     * @return int
     */
    private function newComment($content)
    {
        $user = wp_get_current_user();
        $commentId = wp_new_comment(array(
            'comment_post_ID' => $this->postId,
            'comment_content' => $content . ' ' . wp_generate_password(8, false),
            'comment_author' => $user->exists() ? $user->display_name : 'Visitor',
            'comment_author_email' => $user->exists() ? $user->user_email : 'visitor-' . wp_generate_password(8, false) . '@example.com',
            'comment_author_url' => '',
            'comment_type' => 'comment',
            'comment_author_IP' => '192.0.2.10',
            'comment_agent' => 'Cloudflare integration tests',
            'user_id' => $user->ID,
        ), true);
        $this->assertIsInt($commentId);

        return $commentId;
    }
}
