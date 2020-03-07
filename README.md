`CommentMan`
============

Simple PHP class to manage comments and store them in a SQLite3
database.  It uses the class `Comment` for storing the comment object,
but all the conversation thread may be retrieved by using `fetch_thread`
method, that will return a pure array hierarchically sorted by comment
parent and children.

Example:

```php
    <?php
    include_once('CommentMan.php');

    // Start a connection with the database
    $comments_db = new CommentMan('comments.db', true);

    // Create first comment
    $ca = new Comment();
    $ca->parent_id = 0;
    $ca->post_id = 2;
    $ca->ip = "$_SERVER[REMOTE_ADDR]";
    $ca->username = 'Somebody';
    $ca->message = 'First message';

    // Create second comment
    $cb = new Comment();
    $cb->parent_id = 1;
    $cb->post_id = 2;
    $cb->ip = "$_SERVER[REMOTE_ADDR]";
    $cb->username = 'Somebody Else';
    $cb->message = 'Reply to first message';

    // Add the comments to the database
    $comments_db->add($ca);
    $comments_db->add($cb);

    // Get the tree-like thread array
    $thread = $comments_db->fetch_thread($post_id=0);
    print_r($thread);
```


Copyright (c) 2020, J. A. Corbal

