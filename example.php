<!DOCTYPE html>
<head>
    <title>CommentMan.php (Test page)</title>
    <meta charset="utf-8">
</head>

<html>

<?php

include_once("CommentMan.php");

/* Generate HTML code for comments thread */
function html_thread(array $thread, $date_format='%c',
        $locale='en_US.UTF-8', $parent_id=0) {
    setlocale(LC_TIME, $locale);
    foreach ($thread as $subthread) {
        if ($subthread['parent_id'] == $parent_id) {
            echo '<div class="comment">' . PHP_EOL;
            echo '<div class="comment_message"><p>'.
                $subthread['message'] . '</div>' . PHP_EOL;
            echo '<div class="comment_signature">' . 
                '<span class="comment_username">' .
                $subthread['username'] . '</span>;' . PHP_EOL;
            echo '<span>' .
                '<time datetime=' .
                strftime('%Y-%m-%dT%H:%M:%S',
                    strtotime($subthread['timestamp'])) . '>' .
                strftime($date_format,
                    strtotime($subthread['timestamp'])) .
                        '</time></span>' . PHP_EOL;
            echo ' &bull; ';
            echo '<span class="comment-reply">'. PHP_EOL;
            echo '<a href="#">Reply</a></span>' . PHP_EOL;
            echo '</div>' . PHP_EOL;

            if ($subthread['children']) {
                html_thread($subthread['children'], $date_format,
                        $locale, $subthread['id']);
            }
            echo '</div>' . PHP_EOL;
        }
    }
}
    // Start a connection with the database
    $comments_db = new CommentMan('comments.db', true);

    // Post identifier
    $post_id = 2;

    // Create first comment
    $ca = new Comment();
    $ca->parent_id = 0;
    $ca->post_id = $post_id;
    $ca->ip = $_SERVER['REMOTE_ADDR'];
    $ca->username = 'Ipsum of Lorem';
    $ca->message = 'First message';
    $ca->prepare();

    // Create second comment
    $cb = new Comment();
    $cb->parent_id = 1;
    $cb->post_id = $post_id;
    $cb->ip = $_SERVER['REMOTE_ADDR'];
    $cb->username = 'Lorem of Ipsum';
    $cb->message = 'Reply to first message';
    $cb->prepare();

    // Add the comments to the database
    $comments_db->add($ca);
    $comments_db->add($cb);

    // Get the tree-like thread array
    $thread = $comments_db->fetch_thread($post_id);

    // Generate HTML
    html_thread($thread, $date_format="on %B, %Y");
?>

</html>

